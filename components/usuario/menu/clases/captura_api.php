<?php
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');

@mkdir(__DIR__ . '/../logs', 0775, true);
ini_set('error_log', __DIR__ . '/../logs/paypal.log');

header('Content-Type: application/json; charset=utf-8');
ob_start();

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (ob_get_length()) ob_end_clean();
        http_response_code(500);
        echo json_encode([
            'ok'  => false,
            'msg' => 'Fatal: ' . $err['message'] . ' @' . basename($err['file']) . ':' . $err['line']
        ], JSON_UNESCAPED_UNICODE);
    }
});

set_error_handler(function($errno, $errstr, $errfile, $errline){
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

session_start();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ping'])) {
        if (ob_get_length()) ob_end_clean();
        echo json_encode(['ok' => true, 'pong' => true], JSON_UNESCAPED_UNICODE);
        return;
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new RuntimeException('Método no permitido');
    }

    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        throw new RuntimeException('Cuerpo de la petición vacío.');
    }

    $payload  = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    $detalles = $payload['detalles'] ?? null;
    if (!is_array($detalles)) {
        throw new InvalidArgumentException('Falta el objeto "detalles" del pago.');
    }

    // ===== Recalcular total desde carrito =====
    $productos = $_SESSION['carrito']['producto'] ?? null;
    $total = 0.0;

    $root = dirname(__DIR__);
    require_once $root . '/config/config.php';
    require_once $root . '/config/database.php';

    $con = (new Database())->conectar();

    if ($productos) {
        $stmtTotal = $con->prepare('SELECT precio, descuento FROM producto WHERE id = :id AND activo = true');
        foreach ($productos as $id => $cantidad) {
            $stmtTotal->execute([':id' => (int)$id]);
            if ($row = $stmtTotal->fetch(PDO::FETCH_ASSOC)) {
                $precio     = (float)$row['precio'];
                $descuento  = (float)$row['descuento'];
                $precioDesc = $precio - (($precio * $descuento) / 100.0);
                $total     += ((int)$cantidad) * $precioDesc;
            }
        }
    }
    $total = (float) number_format($total, 2, '.', '');

    // Validación opcional con PayPal
    $reported = null;
    if (isset($detalles['purchase_units'][0]['payments']['captures'][0]['amount']['value'])) {
        $reported = (float)$detalles['purchase_units'][0]['payments']['captures'][0]['amount']['value'];
    } elseif (isset($detalles['purchase_units'][0]['amount']['value'])) {
        $reported = (float)$detalles['purchase_units'][0]['amount']['value'];
    }
    if ($reported !== null && abs($reported - $total) > 0.01) {
        throw new RuntimeException("Monto inconsistente. Esperado {$total}, Reportado {$reported}");
    }

    $orderId = $detalles['id']     ?? null;
    $status  = $detalles['status'] ?? null;

    // ===== ELEGIR CORREO CORRECTO =====
    // Solo usar el correo registrado si fue "confirmado para pago" (flag) en ESTA visita.
    $registeredEmail = '';
    if (!empty($_SESSION['email_confirmed_for_payment']) &&
        $_SESSION['email_confirmed_for_payment'] === true &&
        !empty($_SESSION['cliente_email']) &&
        filter_var($_SESSION['cliente_email'], FILTER_VALIDATE_EMAIL)) {
        $registeredEmail = trim((string)$_SESSION['cliente_email']);
    }

    $paypalEmail = '';
    if (!empty($detalles['payer']['email_address']) &&
        filter_var($detalles['payer']['email_address'], FILTER_VALIDATE_EMAIL)) {
        $paypalEmail = trim((string)$detalles['payer']['email_address']);
    }

    // Prioridad: correo registrado (si flag == true), si no, correo PayPal
    $notifyEmail = $registeredEmail !== '' ? $registeredEmail : $paypalEmail;

    // Folio
    $pickup = 'DC-' . date('ymd') . '-' . str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    // ===== Guardar pedido + items (PostgreSQL) =====
    $guardarPedido = function() use ($con, $pickup, $orderId, $status, $total, $notifyEmail, $productos) {
        $sqlPedido = $con->prepare('
            INSERT INTO pedidos (folio, paypal_order_id, status, total, currency, email, creado_en)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
            RETURNING id
        ');
        $sqlPedido->execute([
            $pickup,
            $orderId,
            $status,
            $total,
            defined('CURRENCY') ? CURRENCY : 'MXN',
            $notifyEmail
        ]);
        $pedidoId = (int)$sqlPedido->fetchColumn();

        $sqlProd = $con->prepare('SELECT nombre, precio, descuento FROM producto WHERE id = :id AND activo = true');
        $sqlItem = $con->prepare('
            INSERT INTO pedidos_items
                (pedido_id, producto_id, nombre, precio_unit, descuento_pct, cantidad, subtotal)
            VALUES
                (:pedido_id, :producto_id, :nombre, :precio_unit, :descuento_pct, :cantidad, :subtotal)
        ');

        if ($productos) {
            foreach ($productos as $id => $cantidad) {
                $sqlProd->execute([':id' => (int)$id]);
                $row = $sqlProd->fetch(PDO::FETCH_ASSOC);
                if (!$row) throw new RuntimeException("Producto $id no disponible.");

                $nombre     = (string)$row['nombre'];
                $precio     = (float)$row['precio'];
                $descuento  = (float)$row['descuento'];
                $precioUnit = (float) number_format($precio - (($precio * $descuento) / 100.0), 2, '.', '');
                $cant       = (int)$cantidad;
                $subtotal   = (float) number_format($cant * $precioUnit, 2, '.', '');

                $sqlItem->execute([
                    ':pedido_id'     => $pedidoId,
                    ':producto_id'   => (int)$id,
                    ':nombre'        => $nombre,
                    ':precio_unit'   => $precioUnit,
                    ':descuento_pct' => $descuento,
                    ':cantidad'      => $cant,
                    ':subtotal'      => $subtotal
                ]);
            }
        }

        return $pedidoId;
    };

    $con->beginTransaction();
    try {
        $pedidoId = $guardarPedido();
        $con->commit();
    } catch (Throwable $e) {
        $con->rollBack();
        $isDuplicate = ($e instanceof PDOException) && $e->getCode() === '23505';
        if ($isDuplicate) {
            $pickup = 'DC-' . date('ymd') . '-' . str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $con->beginTransaction();
            try {
                $pedidoId = $guardarPedido();
                $con->commit();
            } catch (Throwable $e2) {
                $con->rollBack();
                throw $e2;
            }
        } else {
            throw $e;
        }
    }

    // Limpiar carrito Y el correo/flag de esta compra para no “persistir” a futuras compras
    unset($_SESSION['carrito'], $_SESSION['cliente_email'], $_SESSION['email_confirmed_for_payment']);
    $_SESSION['num_cart'] = 0;

    // Datos para gracias.php
    $_SESSION['ultimo_pedido'] = [
        'order_id' => $orderId,
        'status'   => $status,
        'total'    => number_format($total, 2, '.', ''),
        'email'    => $notifyEmail,
        'folio'    => $pickup,
        'ts'       => time(),
    ];

    if (ob_get_length()) ob_end_clean();
    echo json_encode([
        'ok'          => true,
        'order_id'    => $orderId,
        'status'      => $status,
        'total'       => number_format($total, 2, '.', ''),
        'email'       => $notifyEmail,
        'pickup_code' => $pickup
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if (ob_get_length()) ob_end_clean();
    http_response_code(400);
    error_log('[captura_api] ' . $e->getMessage());
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
