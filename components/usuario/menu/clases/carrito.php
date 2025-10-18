<?php
// clases/carrito.php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

function money_fmt(float $n): string {
  return number_format($n, 2, '.', ',');
}



$DC_IMG_CAT_ALIASES = [
  'camarones'        => ['camarones','camaron'],
  'pescados'         => ['pescados','pescado'],
  'pulpos_calamares' => ['pulpos_calamares','pulpos','calamares','pulpo','calamar'],
  'conchas'          => ['conchas','moluscos'],
  'ahumados'         => ['ahumados','ahumado','smoked'],
  'premium'          => ['premium','premiun'],
];

// Raíces físicas y públicas (desde este archivo)
$DC_IMG_BASES = [
  [
    'dir' => realpath(__DIR__ . '/../images/productos'),
    'url' => '/components/usuario/menu/images/productos',
  ],
  [
    'dir' => realpath(__DIR__ . '/../images'),
    'url' => '/components/usuario/menu/images',
  ],
];

$DC_IMG_NOIMG = '/components/usuario/menu/images/no-photo.jpeg';

if (!function_exists('dc_str_starts_with')) {
  function dc_str_starts_with(string $h, string $n): bool { return $n === '' || strpos($h, $n) === 0; }
}

/**
 * Devuelve URL absoluta para el navegador de una imagen de producto
 */
function dc_resolve_img_url(?string $img): string {
  global $DC_IMG_CAT_ALIASES, $DC_IMG_BASES, $DC_IMG_NOIMG;

  $img = trim((string)$img);
  if ($img === '') return $DC_IMG_NOIMG;

  // Ya es http(s)
  if (preg_match('~^https?://~i', $img)) return $img;

  // Trae subcarpeta "cat/archivo"
  if (strpos($img, '/') !== false) {
    foreach ($DC_IMG_BASES as $b) {
      if (!$b['dir']) continue;
      $abs = $b['dir'] . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $img);
      if (is_file($abs)) return rtrim($b['url'],'/') . '/' . ltrim($img,'/');
    }
    return $DC_IMG_NOIMG;
  }

  // Solo nombre -> probar en cada alias y base
  foreach ($DC_IMG_CAT_ALIASES as $aliases) {
    foreach ($aliases as $cat) {
      foreach ($DC_IMG_BASES as $b) {
        if (!$b['dir']) continue;
        $rel = $cat . '/' . $img;
        $abs = $b['dir'] . DIRECTORY_SEPARATOR . $rel;
        if (is_file($abs)) return rtrim($b['url'],'/') . '/' . $rel;
      }
    }
  }

  // Último intento: suelto en la raíz de cada base
  foreach ($DC_IMG_BASES as $b) {
    if (!$b['dir']) continue;
    $abs = $b['dir'] . DIRECTORY_SEPARATOR . $img;
    if (is_file($abs)) return rtrim($b['url'],'/') . '/' . $img;
  }

  return $DC_IMG_NOIMG;
}

/** Opcional: agregar cache-buster por mtime (para evitar cache viejo) */
function dc_add_cache_buster(string $url): string {
  global $DC_IMG_BASES;
  foreach ($DC_IMG_BASES as $b) {
    $prefix = rtrim($b['url'],'/') . '/';
    if (dc_str_starts_with($url, $prefix) && $b['dir']) {
      $rel = substr($url, strlen($prefix));
      $abs = $b['dir'] . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
      $v = @filemtime($abs);
      if ($v) return $url . (strpos($url,'?')===false ? '?v=' : '&v=') . $v;
    }
  }
  return $url;
}

/* ========================================================= */

function buildMiniCart(): array {
  $itemsSesion = $_SESSION['carrito']['producto'] ?? [];
  if (!is_array($itemsSesion) || empty($itemsSesion)) {
    return [
      'html'         => '<em class="mini-cart-empty">Tu carrito está vacío</em>',
      'numero'       => 0,
      'unidades'     => 0,
      'subtotal'     => 0.0,
      'subtotal_fmt' => money_fmt(0),
    ];
  }

  $ids = array_filter(array_map('intval', array_keys($itemsSesion)), fn($v)=>$v>0);
  if (!$ids) {
    return [
      'html'         => '<em class="mini-cart-empty">Tu carrito está vacío</em>',
      'numero'       => 0,
      'unidades'     => 0,
      'subtotal'     => 0.0,
      'subtotal_fmt' => money_fmt(0),
    ];
  }

  // Conexión a BD
  try {
    $db = new Database();
    $con = $db->conectar();
  } catch (Throwable $e) {
    $unidades = array_sum(array_map('intval', $itemsSesion));
    return [
      'html'         => '<em class="mini-cart-empty">No se pudo cargar el detalle del carrito</em>',
      'numero'       => count($itemsSesion),
      'unidades'     => $unidades,
      'subtotal'     => 0.0,
      'subtotal_fmt' => money_fmt(0),
    ];
  }

  // Traer datos de productos (incluye imagen)
  $in = implode(',', array_fill(0, count($ids), '?'));
  $stmt = $con->prepare("SELECT id, nombre, precio, imagen FROM producto WHERE id IN ($in)");
  foreach ($ids as $k=>$id) $stmt->bindValue($k+1, $id, PDO::PARAM_INT);
  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $byId = [];
  foreach ($rows as $r) $byId[(int)$r['id']] = $r;

  // Render items + subtotal
  $subtotal = 0.0;
  ob_start();
  foreach ($itemsSesion as $id => $cantidad) {
    $id = (int)$id;
    $cantidad = max(1, (int)$cantidad);

    $row    = $byId[$id] ?? null;
    $nombre = $row
      ? htmlspecialchars((string)$row['nombre'], ENT_QUOTES, 'UTF-8')
      : ('Producto ' . $id);

    $precio = $row ? (float)$row['precio'] : 0.0;
    $linea  = $precio * $cantidad;
    $subtotal += $linea;

    // URL de imagen (legacy + nuevo) con cache-buster
    $imgUrl = dc_resolve_img_url($row['imagen'] ?? '');
    $imgUrl = dc_add_cache_buster($imgUrl);
    ?>
    <div class="mini-cart-item d-flex align-items-center py-2 border-bottom">
      <img class="mini-cart-thumb"
           src="<?php echo htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8'); ?>"
           alt="<?php echo $nombre; ?>"
           loading="lazy"
           style="width:56px;height:56px;object-fit:cover;border-radius:10px;margin-right:10px;"
           onerror="this.onerror=null;this.src='/components/usuario/menu/images/no-photo.jpeg'">
      <div class="flex-grow-1">
        <p class="mini-cart-title mb-1 small fw-semibold" style="line-height:1.2"><?php echo $nombre; ?></p>

        <!-- Controles compactos de cantidad (no se desbordan) -->
        <div class="d-flex justify-content-between align-items-center">
          <div class="qty-controls" data-id="<?php echo $id; ?>"
               style="display:inline-flex;align-items:center;border:1px solid #e3e6eb;border-radius:8px;overflow:hidden;background:#fff;">
            <button type="button" class="mini-cart-dec" data-id="<?php echo $id; ?>"
                    title="Restar"
                    style="width:28px;height:28px;padding:0;border:0;background:#f4f6f8;font-weight:700;cursor:pointer;">−</button>
            <span class="qty" style="min-width:26px;text-align:center;padding:0 6px;font-weight:600;color:#333;">x<?php echo $cantidad; ?></span>
            <button type="button" class="mini-cart-inc" data-id="<?php echo $id; ?>"
                    title="Sumar"
                    style="width:28px;height:28px;padding:0;border:0;background:#f4f6f8;font-weight:700;cursor:pointer;">+</button>
          </div>

          <span class="mini-cart-price fw-bold" style="white-space:nowrap">$<?php echo money_fmt($linea); ?></span>
        </div>
      </div>

      <button type="button"
              class="btn btn-sm btn-link text-danger mini-cart-remove"
              data-id="<?php echo $id; ?>"
              title="Quitar este producto"
              aria-label="Quitar"
              style="text-decoration:none;font-size:18px;line-height:1;">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>
    <?php
  }
  $html = ob_get_clean();

  return [
    'html'         => $html,
    'numero'       => count($itemsSesion),                          // productos distintos
    'unidades'     => array_sum(array_map('intval', $itemsSesion)), // suma de cantidades
    'subtotal'     => $subtotal,
    'subtotal_fmt' => money_fmt($subtotal),
  ];
}

function jsonResponse(array $data): void {
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

/** ================= Ruteo ================= */

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $action = $_GET['action'] ?? '';
  if ($action === 'mini') {
    $mini = buildMiniCart();
    jsonResponse([
      'ok'           => true,
      'numero'       => $mini['numero'],
      'unidades'     => $mini['unidades'],
      'subtotal'     => $mini['subtotal'],
      'subtotal_fmt' => $mini['subtotal_fmt'],
      'html'         => $mini['html'],
    ]);
  }
  jsonResponse(['ok'=>false,'msg'=>'Acción no válida']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  // Vaciar carrito
  if ($action === 'empty') {
    unset($_SESSION['carrito']['producto']);
    $mini = buildMiniCart();
    jsonResponse(['ok'=>true,'numero'=>$mini['numero'],'unidades'=>$mini['unidades'],
                  'subtotal'=>$mini['subtotal'],'subtotal_fmt'=>$mini['subtotal_fmt'],'html'=>$mini['html']]);
  }

  // Quitar producto entero
  if ($action === 'remove') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0 && isset($_SESSION['carrito']['producto'][$id])) {
      unset($_SESSION['carrito']['producto'][$id]);
    }
    $mini = buildMiniCart();
    jsonResponse(['ok'=>true,'numero'=>$mini['numero'],'unidades'=>$mini['unidades'],
                  'subtotal'=>$mini['subtotal'],'subtotal_fmt'=>$mini['subtotal_fmt'],'html'=>$mini['html']]);
  }

  // Incrementar / Decrementar
  if ($action === 'increment' || $action === 'decrement') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) jsonResponse(['ok'=>false,'msg'=>'ID inválido']);

    if (!isset($_SESSION['carrito']['producto'][$id])) {
      if ($action === 'increment') {
        $_SESSION['carrito']['producto'][$id] = 1;
      }
      // si es decrement y no existe, no hace nada
    } else {
      if ($action === 'increment') {
        $_SESSION['carrito']['producto'][$id] += 1;
      } else {
        $_SESSION['carrito']['producto'][$id] -= 1;
        if ($_SESSION['carrito']['producto'][$id] <= 0) {
          unset($_SESSION['carrito']['producto'][$id]);
        }
      }
    }

    $mini = buildMiniCart();
    jsonResponse(['ok'=>true,'numero'=>$mini['numero'],'unidades'=>$mini['unidades'],
                  'subtotal'=>$mini['subtotal'],'subtotal_fmt'=>$mini['subtotal_fmt'],'html'=>$mini['html']]);
  }

  // Agregar (compat: permite sin action, con id+token)
  if ($action === 'add' || (isset($_POST['id'], $_POST['token']) && !$action)) {
    if (!isset($_POST['id'], $_POST['token'])) {
      jsonResponse(['ok'=>false,'msg'=>'Parámetros incompletos']);
    }
    $id = (int)$_POST['id'];
    $token = (string)$_POST['token'];
    if ($id <= 0) jsonResponse(['ok'=>false,'msg'=>'ID inválido']);

    if (!defined('KEY_TOKEN')) define('KEY_TOKEN', 'clave_dev'); // respaldo
    $token_tmp = hash_hmac('sha1', (string)$id, KEY_TOKEN);
    if (!hash_equals($token_tmp, $token)) {
      jsonResponse(['ok'=>false,'msg'=>'Token inválido']);
    }

    if (!isset($_SESSION['carrito']['producto']) || !is_array($_SESSION['carrito']['producto'])) {
      $_SESSION['carrito']['producto'] = [];
    }
    $_SESSION['carrito']['producto'][$id] = ($_SESSION['carrito']['producto'][$id] ?? 0) + 1;

    $mini = buildMiniCart();
    jsonResponse(['ok'=>true,'numero'=>$mini['numero'],'unidades'=>$mini['unidades'],
                  'subtotal'=>$mini['subtotal'],'subtotal_fmt'=>$mini['subtotal_fmt'],'html'=>$mini['html']]);
  }

  jsonResponse(['ok'=>false,'msg'=>'Acción no válida']);
}

// Método no soportado
jsonResponse(['ok'=>false,'msg'=>'Método no soportado']);
