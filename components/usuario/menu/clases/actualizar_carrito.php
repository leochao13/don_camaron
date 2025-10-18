<?php
require '../config/config.php';
require '../config/database.php';

$datos = ['ok' => false];

if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($action === 'agregar') {
        $cantidad  = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 0;
        $respuesta = agregar($id, $cantidad);
        if ($respuesta > 0) {
            $datos['ok']  = true;
            $datos['sub'] = MONEDA . number_format($respuesta, 2, '.', ',');
        } else {
            $datos['ok']  = false;
            $datos['sub'] = MONEDA . number_format(0, 2, '.', ',');
        }
        // Si quieres regresar también el número de artículos en el carrito:
        $datos['num'] = isset($_SESSION['carrito']['producto']) ? array_sum($_SESSION['carrito']['producto']) : 0;

    } elseif ($action === 'eliminar') {
        $datos['ok'] = eliminar($id);
        // Actualiza badge
        $datos['num'] = isset($_SESSION['carrito']['producto']) ? array_sum($_SESSION['carrito']['producto']) : 0;

    } else {
        $datos['ok'] = false;
    }
} else {
    $datos['ok'] = false;
}

echo json_encode($datos);


/**
 * Establece la cantidad de un producto en el carrito y devuelve el subtotal calculado.
 */
function agregar($id, $cantidad) {
    $res = 0.0;

    if ($id > 0 && $cantidad > 0 && is_numeric($cantidad)) {

        if (isset($_SESSION['carrito']['producto'][$id])) {
            // Actualiza la cantidad en el carrito de sesión
            $_SESSION['carrito']['producto'][$id] = $cantidad;

            $db  = new Database();
            $con = $db->conectar();

            // POSTGRES: usa booleano
            $sql = $con->prepare("
                SELECT precio, descuento
                FROM producto
                WHERE id = ? AND activo = true
                LIMIT 1
            ");
            $sql->execute([$id]);
            $row = $sql->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $precio     = (float)$row['precio'];
                $descuento  = (float)$row['descuento'];
                $precio_desc = $precio - (($precio * $descuento) / 100.0);
                $res         = $cantidad * $precio_desc;
            } else {
                // Producto no encontrado o inactivo: saca del carrito para no dejar basura
                unset($_SESSION['carrito']['producto'][$id]);
                $res = 0.0;
            }
        }
    }

    return $res;
}

/**
 * Elimina un producto del carrito (sesión).
 */
function eliminar($id) {
    if ($id > 0 && isset($_SESSION['carrito']['producto'][$id])) {
        unset($_SESSION['carrito']['producto'][$id]);
        return true;
    }
    return false;
}
