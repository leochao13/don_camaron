<?php
declare(strict_types=1);

session_start();

// ✅ Validación de usuario (antes de cualquier salida)
include("C:/xampp/htdocs/php/polices.php");

ini_set('display_errors', '1');
error_reporting(E_ALL);

// Config y DB (según tu estructura: .../components/usuario/menu/config/)
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

try {
  $con = (new Database())->conectar();
  $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
  http_response_code(500);
  echo "<pre style='color:#b00'>Error de conexión BD: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</pre>";
  exit;
}

$q    = isset($_GET['q']) ? trim($_GET['q']) : '';
$modo = isset($_GET['modo']) ? $_GET['modo'] : 'folio'; // folio | order | email

$pedido  = null;
$items   = [];
$listado = []; // últimos pedidos cuando no hay búsqueda
$moneda  = defined('MONEDA') ? MONEDA : '$';
$errMsg  = '';

try {
  if ($q !== '') {
    // Búsqueda según modo
    if ($modo === 'folio') {
      $q = preg_replace('/[^A-Z0-9\-]/i', '', $q);
      $stmt = $con->prepare('SELECT * FROM pedidos WHERE folio = ? LIMIT 1');
      $stmt->execute([$q]);
    } elseif ($modo === 'order') {
      $q = preg_replace('/[^A-Za-z0-9\-]/', '', $q);
      $stmt = $con->prepare('SELECT * FROM pedidos WHERE paypal_order_id = ? LIMIT 1');
      $stmt->execute([$q]);
    } elseif ($modo === 'email') {
      $q = filter_var($q, FILTER_SANITIZE_EMAIL);
      $stmt = $con->prepare('SELECT * FROM pedidos WHERE email = ? ORDER BY creado_en DESC LIMIT 1');
      $stmt->execute([$q]);
    } else {
      $stmt = null;
    }

    $pedido = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;

    if ($pedido) {
      $stmtI = $con->prepare('
        SELECT producto_id, nombre, precio_unit, descuento_pct, cantidad, subtotal
        FROM pedidos_items
        WHERE pedido_id = ?
        ORDER BY id ASC
      ');
      $stmtI->execute([(int)$pedido['id']]);
      $items = $stmtI->fetchAll(PDO::FETCH_ASSOC);
    }
  } else {
    // Listado de últimos pedidos
    $stmtL = $con->query("
      SELECT id, folio, total, email, status, creado_en
      FROM pedidos
      ORDER BY id DESC
      LIMIT 20
    ");
    $listado = $stmtL->fetchAll(PDO::FETCH_ASSOC);
  }
} catch (Throwable $e) {
  $errMsg = $e->getMessage();
}

// Helper para boolean (Postgres: 't'/'f', true/false, 1/0)
function pg_bool($val): bool {
  return filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool)$val;
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Ver pedido / Detalle</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .badge-soft { background: #eef4ff; color:#0a58ca; border:1px solid #cfe2ff; }
    .code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace; }
  </style>
</head>
<body class="bg-light">
<div class="container py-4">

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <h1 class="h4 mb-3">Buscar pedido</h1>
      <form class="row g-2 align-items-end mb-3" method="get">
        <div class="col-sm-3">
          <label class="form-label">Buscar por</label>
          <select name="modo" class="form-select">
            <option value="folio" <?php echo $modo==='folio'?'selected':''; ?>>Folio (DC-...)</option>
            <option value="order" <?php echo $modo==='order'?'selected':''; ?>>Order ID (PayPal)</option>
            <option value="email" <?php echo $modo==='email'?'selected':''; ?>>Email</option>
          </select>
        </div>
        <div class="col-sm-6">
          <label class="form-label">Valor</label>
          <input type="text" class="form-control" name="q" placeholder="DC-240905-123456 / 7S123... / cliente@correo.com"
                 value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-sm-3 d-grid">
          <button class="btn btn-primary">Buscar</button>
        </div>
      </form>

      <?php if ($errMsg): ?>
        <div class="alert alert-danger">
          <strong>Error:</strong> <?php echo htmlspecialchars($errMsg, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <?php if ($q !== '' && !$pedido && !$errMsg): ?>
        <div class="alert alert-warning">
          No se encontró ningún pedido para:
          <span class="code"><?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?></span>
          (modo: <?php echo htmlspecialchars($modo, ENT_QUOTES, 'UTF-8'); ?>)
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($pedido): ?>
    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="border rounded p-3 h-100">
              <h2 class="h6 text-muted mb-2">Encabezado</h2>
              <div><strong>Folio:</strong> <span class="code"><?php echo htmlspecialchars($pedido['folio'], ENT_QUOTES, 'UTF-8'); ?></span></div>
              <?php if (!empty($pedido['paypal_order_id'])): ?>
                <div><strong>Order ID (PayPal):</strong> <span class="code"><?php echo htmlspecialchars($pedido['paypal_order_id'], ENT_QUOTES, 'UTF-8'); ?></span></div>
              <?php endif; ?>
              <div><strong>Estado:</strong>
                <span class="badge bg-success"><?php echo htmlspecialchars($pedido['status'] ?? 'COMPLETED', ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
              <div><strong>Total:</strong> <?php echo $moneda . number_format((float)$pedido['total'], 2); ?>
                <?php echo htmlspecialchars($pedido['currency'] ?? 'MXN', ENT_QUOTES, 'UTF-8'); ?>
              </div>
              <?php if (!empty($pedido['email'])): ?>
                <div><strong>Email:</strong> <?php echo htmlspecialchars($pedido['email'], ENT_QUOTES, 'UTF-8'); ?></div>
              <?php endif; ?>
              <div><strong>Fecha:</strong> <?php echo htmlspecialchars($pedido['creado_en'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="border rounded p-3 h-100">
              <h2 class="h6 text-muted mb-2">Estado de entrega</h2>
              <?php if (pg_bool($pedido['recogido'] ?? false)): ?>
                <div class="alert alert-success mb-2">Entregado / Recogido</div>
                <div><strong>Recogido en:</strong> <?php echo htmlspecialchars($pedido['recogido_en'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
              <?php else: ?>
                <div class="alert alert-info mb-2">Pendiente de entrega</div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="table-responsive mt-3">
          <table class="table table-sm align-middle">
            <thead class="table-light">
              <tr>
                <th style="width:100px;">ID Prod.</th>
                <th>Producto</th>
                <th class="text-end">Precio unit.</th>
                <th class="text-end">Desc. %</th>
                <th class="text-end">Cant.</th>
                <th class="text-end">Subtotal</th>
              </tr>
            </thead>
            <tbody>
            <?php if ($items): foreach ($items as $it): ?>
              <tr>
                <td class="code"><?php echo (int)$it['producto_id']; ?></td>
                <td><?php echo htmlspecialchars($it['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td class="text-end"><?php echo $moneda . number_format((float)$it['precio_unit'], 2); ?></td>
                <td class="text-end"><?php echo number_format((float)$it['descuento_pct'], 2); ?></td>
                <td class="text-end"><?php echo (int)$it['cantidad']; ?></td>
                <td class="text-end"><?php echo $moneda . number_format((float)$it['subtotal'], 2); ?></td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="6" class="text-center text-muted">Este pedido no tiene items registrados. Verifica el endpoint de captura.</td></tr>
            <?php endif; ?>
            </tbody>
            <tfoot>
              <tr>
                <th colspan="5" class="text-end">Total</th>
                <th class="text-end"><?php echo $moneda . number_format((float)$pedido['total'], 2); ?></th>
              </tr>
            </tfoot>
          </table>
        </div>

      </div>
    </div>
  <?php elseif ($listado): ?>
    <!-- Sin búsqueda: listado de últimos pedidos -->
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="h6 mb-3 text-muted">Últimos pedidos</h2>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead class="table-light">
              <tr>
                <th>ID</th>
                <th>Folio</th>
                <th>Total</th>
                <th>Status</th>
                <th>Email</th>
                <th>Fecha</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($listado as $row): ?>
              <tr>
                <td><?php echo (int)$row['id']; ?></td>
                <td class="code"><?php echo htmlspecialchars($row['folio'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo $moneda . number_format((float)$row['total'], 2); ?></td>
                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['status'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span></td>
                <td><?php echo htmlspecialchars($row['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['creado_en'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                  <a class="btn btn-outline-primary btn-sm"
                     href="?modo=folio&q=<?php echo urlencode($row['folio']); ?>">
                     Ver detalle
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="alert alert-info">No hay pedidos para mostrar todavía.</div>
  <?php endif; ?>

</div>
</body>
</html>
