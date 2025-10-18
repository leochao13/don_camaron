<!-- validacion de usuario -->
<?php
  include("/xampp/htdocs/php/polices.php");
?>

<?php
require 'config/config.php';
require 'config/database.php';

// Si no inicias sesión en config.php, descomenta:
// if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

$db = new Database();
$con = $db->conectar();

if (!defined('KEY_TOKEN')) {
  define('KEY_TOKEN', 'clave_dev');
}

/* ============================================================
   RESOLVER DE IMÁGENES (mismo criterio que mini-carrito)
   - Tolerante a rutas antiguas en la BD (solo nombre de archivo)
   - Busca en subcarpetas por alias (pulpos / pulpos_calamares, etc.)
   - Devuelve URL navegable y añade cache-buster (?v=mtime)
   ============================================================ */

$DC_IMG_CAT_ALIASES = [
  'camarones'        => ['camarones','camaron'],
  'pescados'         => ['pescados','pescado'],
  'pulpos_calamares' => ['pulpos_calamares','pulpos','calamares','pulpo','calamar'],
  'conchas'          => ['conchas','moluscos'],
  'ahumados'         => ['ahumados','ahumado','smoked'],
  'premium'          => ['premium','premiun'],
];

// Raíces donde están tus imágenes públicas
$DC_IMG_BASES = [
  [
    'dir' => realpath(__DIR__ . '/images/productos'),
    'url' => '/components/usuario/menu/images/productos',
  ],
  [
    'dir' => realpath(__DIR__ . '/images'),
    'url' => '/components/usuario/menu/images',
  ],
];

$DC_IMG_NOIMG = '/components/usuario/menu/images/no-photo.jpeg';

if (!function_exists('dc_str_starts_with')) {
  function dc_str_starts_with(string $haystack, string $needle): bool {
    return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
  }
}

/** Devuelve URL navegable válida para cualquier valor guardado en BD */
function dc_resolve_img_url(?string $img): string {
  global $DC_IMG_CAT_ALIASES, $DC_IMG_BASES, $DC_IMG_NOIMG;

  $img = trim((string)$img);
  if ($img === '') return $DC_IMG_NOIMG;

  // Si ya viene http(s), se devuelve tal cual
  if (preg_match('~^https?://~i', $img)) return $img;

  // Si la BD trae "carpeta/archivo"
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

/** Agrega cache-buster (?v=mtime) */
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

/* ===== Carrito en sesión ===== */
$num_cart  = isset($num_cart) ? (int)$num_cart : (int)($_SESSION['num_cart'] ?? 0);
$productos = $_SESSION['carrito']['producto'] ?? null;
$lista_carrito = [];

/* ===== Traer detalle de productos del carrito (con IMAGEN) ===== */
if ($productos) {
  foreach ($productos as $clave => $cantidad) {
    $sql = $con->prepare("SELECT id, nombre, precio, descuento, imagen, :cant AS cantidad
                          FROM producto
                          WHERE id = :id AND activo = true");
    $sql->bindValue(':cant', (int)$cantidad, PDO::PARAM_INT);
    $sql->bindValue(':id', (int)$clave, PDO::PARAM_INT);
    $sql->execute();
    $row = $sql->fetch(PDO::FETCH_ASSOC);
    if ($row) $lista_carrito[] = $row;
  }
}

/* ===== Sugeridos (excluyendo los que ya están en carrito) ===== */
$idsEnCarrito = $productos ? array_map('intval', array_keys($productos)) : [];

if (!empty($idsEnCarrito)) {
  $placeholders = implode(',', array_fill(0, count($idsEnCarrito), '?'));
  $sqlSug = $con->prepare("SELECT id, nombre, precio, imagen
                           FROM producto
                           WHERE activo = true AND id NOT IN ($placeholders)
                           ORDER BY id DESC
                           LIMIT 8");
  $sqlSug->execute($idsEnCarrito);
} else {
  $sqlSug = $con->prepare("SELECT id, nombre, precio, imagen
                           FROM producto
                           WHERE activo = true
                           ORDER BY id DESC
                           LIMIT 8");
  $sqlSug->execute();
}
$sugeridos = $sqlSug->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Tienda de Mariscos - Carrito</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css"
        rel="stylesheet" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="css/estilos.css?v=<?= time(); ?>">

  <style>
    header.topbar { background-color: #0d47a1; color: #fff; }
    header.topbar .logo-header { height: 40px; width: auto; }
    header.topbar .cart-icon { font-size: 1.75rem; line-height:1; color:#fff; }
    header.topbar .btn-cart { border:1px solid rgba(255,255,255,.7); color:#fff; }
    header.topbar .btn-cart:hover { background: rgba(255,255,255,.12); }

    .section-bar { background:#fff; }
    .section-list .nav-link { font-weight:600; color:#143a4a; white-space:nowrap; }
    .section-list .nav-link:hover { color:#0d6efd; }

    .table thead th { background:#f4f6f8; border-bottom:0; }
    .table td, .table th { vertical-align: middle; }
    .qty-input { width: 90px; }

    .btn-pay { padding: .9rem 1.2rem; font-size:1.05rem; }
    .btn-gold { background:#d4af37; border-color:#c5a028; color:#1f1f1f; font-weight:700; }
    .btn-gold:hover { background:#c5a028; border-color:#b89220; color:#111; }

    .prod-thumb{ width:64px;height:64px;object-fit:cover;border-radius:10px;background:#f3f6f9; }

    .card.card-product { border:0; border-radius:1rem; overflow:hidden; box-shadow:0 8px 28px rgba(0,0,0,.08); transition:transform .2s ease, box-shadow .2s ease; }
    .card.card-product:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(0,0,0,.12); }
    .card-product img { width:100%; height:200px; object-fit:cover; }
    .price-big { font-size: 1.1rem; font-weight: 800; }
    .card-actions { gap:.5rem; }

    .footer-logo-box img { height:100% !important; width:auto !important; display:block; }
  </style>
</head>
<body>

<header class="topbar">
  <div class="container py-3">
    <div class="row g-3 align-items-center">
      <div class="col-12 col-md-3 text-center text-md-start">
        <a href="index.php" class="navbar-brand d-inline-flex align-items-center gap-2 text-decoration-none">
          <img src="images/logo.png" alt="Don Camarón" class="logo-header">
          <span class="fw-bold h4 text-white m-0">Don Camarón Online</span>
        </a>
      </div>
      <div class="col-12 col-md-6">
        <form action="index.php" method="get" class="search-form">
          <div class="input-group input-group-lg">
            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
            <input type="search" name="q" class="form-control" placeholder="Buscar producto...">
            <button class="btn btn-light text-primary fw-bold" type="submit">Buscar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</header>

<nav class="section-bar border-bottom">
  <div class="container">
    <ul class="nav justify-content-between flex-nowrap overflow-auto py-2 gap-3 section-list">
      <li class="nav-item"><a class="nav-link" href="index.php?cat=camaron"><i class="bi bi-droplet-half me-2"></i>Camarones</a></li>
      <li class="nav-item"><a class="nav-link" href="index.php?cat=pescado"><i class="bi bi-fish me-2"></i>Pescados</a></li>
      <li class="nav-item"><a class="nav-link" href="index.php?cat=pulpo"><i class="bi bi-emoji-smile me-2"></i>Pulpos y Calamares</a></li>
      <li class="nav-item"><a class="nav-link" href="index.php?cat=premium"><i class="bi bi-gem me-2"></i>Mariscos Premium</a></li>
      <li class="nav-item"><a class="nav-link" href="index.php?cat=conchas"><i class="bi bi-circle me-2"></i>Conchas y Moluscos</a></li>
      <li class="nav-item"><a class="nav-link" href="index.php?cat=ahumados"><i class="bi bi-fire me-2"></i>Ahumados</a></li>
    </ul>
  </div>
</nav>

<main class="py-4">
  <div class="container">
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>Producto</th>
            <th class="text-end">Precio</th>
            <th class="text-center">Cantidad</th>
            <th class="text-end">Subtotal</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php
        if ($lista_carrito == null) {
          echo '<tr><td colspan="5" class="text-center"><b>Lista vacía</b></td></tr>';
        } else {
          $total = 0;
          foreach ($lista_carrito as $producto) {
            $_id       = (int)$producto['id'];
            $nombre    = htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8');
            $precio    = (float)$producto['precio'];
            $descuento = (float)$producto['descuento'];
            $cantidad  = (int)$producto['cantidad'];

            // Imagen: resolver + cache-buster
            $imgUrl = dc_add_cache_buster(dc_resolve_img_url($producto['imagen'] ?? ''));

            $precio_desc = $precio - (($precio * $descuento) / 100.0);
            $subtotal    = $cantidad * $precio_desc;
            $total      += $subtotal;
        ?>
          <tr>
            <td>
              <div class="d-flex align-items-center" style="gap:10px;">
                <img src="<?php echo htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8'); ?>"
                     alt="<?php echo $nombre; ?>" class="prod-thumb"
                     onerror="this.onerror=null;this.src='/components/usuario/menu/images/no-photo.jpeg'">
                <div class="fw-semibold"><?php echo $nombre; ?></div>
              </div>
            </td>
            <td class="text-end"><?php echo MONEDA . number_format($precio_desc, 2, '.', ','); ?></td>
            <td class="text-center">
              <input
                type="number" min="1" max="10" step="1"
                value="<?php echo $cantidad; ?>"
                class="form-control qty-input mx-auto"
                id="cantidad_<?php echo $_id; ?>"
                onchange="actualizaCantidad(this.value, <?php echo $_id; ?>)"
              >
            </td>
            <td class="text-end">
              <div id="subtotal_<?php echo $_id; ?>" name="subtotal[]">
                <?php echo MONEDA . number_format($subtotal, 2, '.', ','); ?>
              </div>
            </td>
            <td class="text-center">
              <a href="#" id="eliminar" class="btn btn-warning btn-sm"
                 data-bs-id="<?php echo $_id; ?>"
                 data-bs-toggle="modal" data-bs-target="#eliminaModal">
                Eliminar
              </a>
            </td>
          </tr>
        <?php } // foreach ?>
          <tr>
            <td colspan="3"></td>
            <td colspan="2" class="text-end">
              <p class="h3 m-0" id="total"><?php echo MONEDA . number_format($total, 2, '.', ','); ?></p>
            </td>
          </tr>
        <?php } // else ?>
        </tbody>
      </table>
    </div>

    <?php if ($lista_carrito != null) { ?>
      <div class="row">
        <div class="col-md-5 offset-md-7 d-grid gap-2">
          <a href="pago.php" class="btn btn-primary btn-pay">Realizar Pago</a>
        </div>
      </div>
    <?php } ?>

    <section class="mt-5">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h4 class="m-0 fw-bold">¿Te faltó algo? Agrega más productos</h4>
        <a href="index.php" class="btn btn-outline-primary btn-sm">Ver todo</a>
      </div>

      <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-3">
        <?php if ($sugeridos): ?>
          <?php foreach ($sugeridos as $row):
            $sid     = (int)$row['id'];
            $snombre = htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8');
            $sprecio = number_format((float)$row['precio'], 2, '.', ',');
            $simg    = dc_add_cache_buster(dc_resolve_img_url($row['imagen'] ?? ''));
            $stoken  = hash_hmac('sha1', (string)$sid, KEY_TOKEN);
          ?>
          <div class="col">
            <div class="card card-product h-100">
              <img src="<?php echo htmlspecialchars($simg, ENT_QUOTES, 'UTF-8'); ?>"
                   onerror="this.onerror=null;this.src='/components/usuario/menu/images/no-photo.jpeg';"
                   alt="<?php echo $snombre; ?>">
              <div class="card-body d-flex flex-column">
                <h6 class="card-title mb-1"><?php echo $snombre; ?></h6>
                <div class="price-big mb-3"><?php echo MONEDA . $sprecio; ?></div>
                <div class="mt-auto d-flex card-actions">
                  <a href="details.php?id=<?php echo $sid; ?>" class="btn btn-outline-primary">
                    <i class="bi bi-info-circle me-1"></i>Detalles
                  </a>
                  <button class="btn btn-gold"
                          type="button"
                          onclick="addProducto(<?php echo $sid; ?>, '<?php echo $stoken; ?>')">
                    <i class="bi bi-bag-plus-fill me-1"></i> Agregar
                  </button>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="col"><div class="alert alert-info">No hay productos para sugerir.</div></div>
        <?php endif; ?>
      </div>
    </section>

  </div>
</main>

<div class="modal fade" id="eliminaModal" tabindex="-1" aria-labelledby="eliminaModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="eliminaModalLabel">Alerta</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        ¿Desea eliminar el producto de la lista?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button id="btn-elimina" type="button" class="btn btn-danger" onclick="eliminar()">Eliminar</button>
      </div>
    </div>
  </div>
</div>

<footer style="background:#0f2435; color:#ffffff;">
  <div class="container py-4">
    <div class="row align-items-center g-4">
      <div class="col-12 col-lg-3 text-center text-lg-start">
        <div class="footer-logo-box" style="width:120px;height:38px;overflow:hidden;display:inline-block;line-height:0;">
          <img src="images/logo.png?v=3" alt="Don Camarón" style="height:100%;width:auto;display:block;">
        </div>
        <p style="margin:8px 0 0 0;font-style:italic;color:#9fd1ff;">¡El mar directo hasta tu mesa!</p>
      </div>
      <div class="col-12 col-lg-6 text-center">
        <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:12px;">
          <img src="images/pagos/visa.png" alt="Visa" style="height:22px;width:auto;">
          <img src="images/pagos/mastercard.png" alt="Mastercard" style="height:22px;width:auto;">
          <img src="images/pagos/amex.png" alt="American Express" style="height:22px;width:auto;">
          <img src="images/pagos/mp.png" alt="Mercado Pago" style="height:22px;width:auto;">
          <img src="images/pagos/paypal.png" alt="PayPal" style="height:22px;width:auto;">
          <img src="images/pagos/applepay.png" alt="Apple Pay" style="height:22px;width:auto;">
          <img src="images/pagos/googlepay.png" alt="Google Pay" style="height:22px;width:auto;">
          <span style="display:inline-flex;align-items:center;gap:6px;padding:4px 8px;border-radius:6px;background:#e8f7ee;color:#1a7f3d;border:1px solid #bfe7cb;font-weight:600;">
            <i class="bi bi-shield-lock"></i> Pago seguro
          </span>
        </div>
        <div style="margin-top:12px;display:flex;justify-content:center;gap:12px;">
          <a href="#" aria-label="Facebook" style="width:40px;height:40px;display:grid;place-items:center;border-radius:50%;background:#16344a;color:#ffffff;text-decoration:none;box-shadow:0 4px 12px rgba(0,0,0,.2);"><i class="bi bi-facebook"></i></a>
          <a href="#" aria-label="Instagram" style="width:40px;height:40px;display:grid;place-items:center;border-radius:50%;background:#16344a;color:#ffffff;text-decoration:none;box-shadow:0 4px 12px rgba(0,0,0,.2);"><i class="bi bi-instagram"></i></a>
          <a href="https://wa.me/525616677657?text=Hola%20Don%20Camar%C3%BAn%2C%20quiero%20hacer%20un%20pedido" aria-label="WhatsApp" style="width:40px;height:40px;display:grid;place-items:center;border-radius:50%;background:#25D366;color:#ffffff;text-decoration:none;box-shadow:0 4px 12px rgba(0,0,0,.2);" target="_blank"><i class="bi bi-whatsapp"></i></a>
        </div>
      </div>
      <div class="col-12 col-lg-3 text-center text-lg-start">
        <div style="font-size:.95rem;">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
            <i class="bi bi-telephone"></i> <a href="tel:+525616677657" style="color:#ffffff;text-decoration:none;">561 667 7657</a>
          </div>
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
            <i class="bi bi-clock"></i> L-D: <strong>06:00–18:00</strong>
          </div>
          <div style="display:flex;align-items:flex-start;gap:8px;">
            <i class="bi bi-geo-alt" style="margin-top:3px;"></i>
            <div>
              Central de Pescados y Mariscos, La Nueva Viga<br>
              Eje 6 Sur 560, Bodega E-25, CDMX 09040
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="row mt-4 pt-3" style="border-top:1px solid #1d3b51;">
      <div class="col-12 col-lg-6 small text-center text-lg-start">
        © <?php echo date("Y"); ?> <strong>Don Camarón Online</strong>
      </div>
      <div class="col-12 col-lg-6 small">
        <ul class="list-inline m-0 d-flex justify-content-center justify-content-lg-end" style="gap:16px;">
          <li class="list-inline-item"><a href="#" style="color:#ffffff;text-decoration:none;">Términos y Condiciones</a></li>
          <li class="list-inline-item"><a href="#" style="color:#ffffff;text-decoration:none;">Privacidad</a></li>
          <li class="list-inline-item"><a href="#" style="color:#ffffff;text-decoration:none;">Reembolsos</a></li>
          <li class="list-inline-item"><a href="#" style="color:#ffffff;text-decoration:none;">Envíos</a></li>
        </ul>
      </div>
    </div>
  </div>

  <!-- WhatsApp flotante -->
  <a href="https://wa.me/525616677657?text=Hola%20Don%20Camar%C3%B3n%2C%20quiero%20hacer%20un%20pedido"
     target="_blank" aria-label="Chat WhatsApp"
     style="position:fixed;left:18px;bottom:18px;width:56px;height:56px;border-radius:50%;background:#25D366;color:#fff;display:grid;place-items:center;box-shadow:0 6px 18px rgba(37,211,102,.5);z-index:1000;text-decoration:none;font-size:1.7rem;">
    <i class="bi bi-whatsapp"></i>
  </a>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script>
let eliminaModal = document.getElementById('eliminaModal');
if (eliminaModal) {
  eliminaModal.addEventListener('show.bs.modal', function (event) {
    let button = event.relatedTarget;
    let id = button.getAttribute('data-bs-id');
    let buttonElimina = eliminaModal.querySelector('.modal-footer #btn-elimina');
    buttonElimina.value = id;
  });
}

function actualizaCantidad(cantidad, id){
  const url = "clases/actualizar_carrito.php";
  const formData = new FormData();
  formData.append('action', 'agregar');
  formData.append('id', id);
  formData.append('cantidad', cantidad);

  fetch(url, { method: 'POST', body: formData, mode: 'cors' })
  .then(r => r.json())
  .then(data => {
    if (data.ok) {
      let divsubtotal = document.getElementById('subtotal_' + id);
      if (divsubtotal) divsubtotal.innerHTML = data.sub;

      let total = 0.00;
      const list = document.getElementsByName('subtotal[]');
      for (let i = 0; i < list.length; i++) {
        total += parseFloat(list[i].innerHTML.replace(/[$,]/g, ''));
      }
      total = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(total);
      document.getElementById('total').innerHTML = '<?php echo MONEDA; ?>' + total;

      if (typeof data.num !== 'undefined') {
        const badge = document.getElementById('num_cart');
        if (badge) badge.textContent = data.num;
      }
    }
  });
}

function eliminar(){
  const botonElimina = document.getElementById('btn-elimina');
  const id = botonElimina.value;

  const url = "clases/actualizar_carrito.php";
  const formData = new FormData();
  formData.append('action', 'eliminar');
  formData.append('id', id);

  fetch(url, { method: 'POST', body: formData, mode: 'cors' })
  .then(r => r.json())
  .then(data => {
    if (data.ok) location.reload();
  });
}

function addProducto(id, token){
  const url = "clases/carrito.php";
  const formData = new FormData();
  formData.append('id', id);
  formData.append('token', token);

  fetch(url, { method: 'POST', body: formData })
    .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
    .then(data => {
      if (!data.ok) throw new Error(data.msg || 'No se pudo agregar');

      const badge1 = document.getElementById('num_cart');
      if (badge1) badge1.textContent = data.numero;

      location.reload();
    })
    .catch(e => {
      console.error(e);
      alert('No se pudo agregar el producto: ' + e.message);
    });
}
</script>
</body>
</html>
