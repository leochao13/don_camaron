<!-- validacion de usuario -->
<?php
  include("/xampp/htdocs/php/polices.php");
?>

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$err = null;
$resultado = [];

// Contador del carrito
$num_cart = isset($_SESSION['carrito']['producto']) && is_array($_SESSION['carrito']['producto'])
  ? count($_SESSION['carrito']['producto'])
  : 0;

try {
  $db  = new Database();
  $con = $db->conectar();

  // Parámetros de búsqueda
  $qRaw = $_GET['q'] ?? '';
  $q    = trim($qRaw);

  // Slug de categoría del menú (sin columna categoria_id)
  $catSlug = strtolower(trim($_GET['cat'] ?? '')); // camaron|pescado|pulpo|premium|conchas|ahumados

  // Reglas de filtrado por carpeta y keyword (fallback)
  $catRules = [
    'camaron'  => ['imgdir' => 'camarones', 'kw' => ['camar']],
    'pescado'  => ['imgdir' => 'pescados',  'kw' => ['pescad','filete']],
    'pulpo'    => ['imgdir' => 'pulpos_calamares', 'kw' => ['pulpo','calamar']],
    'premium'  => ['imgdir' => 'premium',   'kw' => ['premium','vara','paella','piña']],
    'conchas'  => ['imgdir' => 'conchas',   'kw' => ['almej','mejill','ostion','conch','molusc']],
    'ahumados' => ['imgdir' => 'ahumados',  'kw' => ['ahumad','smoked','mezquite']],
  ];

  // Construir WHERE dinámico (PostgreSQL)
  $where  = ['activo = true'];
  $params = [];

  // Filtro por categoría sin columna: imagen ILIKE '.../carpeta/%' o nombre ILIKE '%kw%'
  if ($catSlug && isset($catRules[$catSlug])) {
    $imgdir = $catRules[$catSlug]['imgdir'];
    $kws    = $catRules[$catSlug]['kw'];

    $whereCatParts = [];
    // 1) por carpeta en la ruta de imagen
    $whereCatParts[] = 'imagen ILIKE :imgdir';
    $params[':imgdir'] = '%/' . $imgdir . '/%';

    // 2) por palabras clave en nombre (fallback)
    foreach ($kws as $i => $kw) {
      $ph = ':kw' . $i;
      $whereCatParts[] = "nombre ILIKE $ph";
      $params[$ph] = '%' . $kw . '%';
    }
    $where[] = '(' . implode(' OR ', $whereCatParts) . ')';
  }

  // Filtro por buscador
  if ($q !== '') {
    $where[] = 'nombre ILIKE :q';
    $params[':q'] = '%' . $q . '%';
  }

  // Query final (agregamos imagen)
  $sqlTxt = "SELECT id, nombre, precio, imagen
             FROM producto
             WHERE " . implode(' AND ', $where) . "
             ORDER BY id DESC";

  $sql = $con->prepare($sqlTxt);
  foreach ($params as $k => $v) {
    $sql->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
  }
  $sql->execute();
  $resultado = $sql->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
  $err = $e->getMessage();
}

/* ============================
   Resolver URL de imágenes
   (compatible con registros viejos y nuevos)
   ============================ */

// categorías/carpeta existentes
$CATS = ['camarones','pescados','pulpos_calamares','conchas','ahumados','premium'];

// Ruta física donde están las imágenes del menú público
$BASE_DIR = realpath(__DIR__ . '/images/productos');

// Ruta pública (URL) a esas imágenes
$BASE_URL = '/components/usuario/menu/images/productos';

// Imagen por defecto (ajústala si usas otra)
$NO_IMG   = '/components/usuario/menu/images/no-photo.jpeg';

// Polyfill por si tu PHP no tiene str_starts_with (PHP < 8)
if (!function_exists('str_starts_with')) {
  function str_starts_with($haystack, $needle) {
    return $needle === '' || strpos($haystack, $needle) === 0;
  }
}

/**
 * Devuelve la URL pública de la imagen de un producto.
 * Acepta:
 *  - "categoria/archivo.jpg" (nuevo)
 *  - "archivo.jpg" (legacy) -> busca en carpetas conocidas
 */
function resolve_image_url_public(?string $img) {
  global $CATS, $BASE_DIR, $BASE_URL, $NO_IMG;

  $img = trim((string)$img);
  if ($img === '') return $NO_IMG;

  // Ya viene con subcarpeta: "categoria/archivo"
  if (strpos($img, '/') !== false) {
    $abs = $BASE_DIR . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $img);
    return (is_file($abs) ? ($BASE_URL . '/' . $img) : $NO_IMG);
  }

  // Legacy: probar en cada carpeta
  foreach ($CATS as $cat) {
    $rel = $cat . '/' . $img;
    $abs = $BASE_DIR . DIRECTORY_SEPARATOR . $rel;
    if (is_file($abs)) return $BASE_URL . '/' . $rel;
  }

  return $NO_IMG;
}

/** Agrega cache-buster basado en mtime para evitar cache viejo */
function add_cache_buster(string $url): string {
  global $BASE_DIR, $BASE_URL;
  if (str_starts_with($url, $BASE_URL)) {
    $rel = substr($url, strlen($BASE_URL) + 1); // quita "/"
    $abs = $BASE_DIR . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $v = @filemtime($abs);
    if ($v) return $url . '?v=' . $v;
  }
  return $url;
}

if (!defined('KEY_TOKEN')) {
  define('KEY_TOKEN', 'clave_dev');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tienda de Mariscos - Don Camarón</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3"
        crossorigin="anonymous">
   <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@48,400,0,0"
    />
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@48,400,1,0"
    />
  <link rel="stylesheet" href="css/estilos.css">
  <link rel="stylesheet" href="/css/estilo-chatbot.css">
  <style>
    :root{
      --bg: #FFFFFF;
      --bg-soft: #F2E9E4;
      --primary: #004E7C;
      --secondary: #00A6A6;
      --accent: #FF6F61;
      --aqua: #6ED3CF;
      --text: #143a4a;
      --text-light: #ffffff;
      --gold: #d4af37;
    }
    body{ background: var(--bg); color: var(--text); }

    header.topbar{ background-color: var(--primary); color: var(--text-light); }
    header.topbar .navbar-brand, header.topbar .navbar-brand span{ color: var(--text-light) !important; }
    header.topbar .logo-header{ height:40px; width:auto; }
    header.topbar .cart-icon{ font-size:1.8rem; line-height:1; color:var(--text-light) !important; vertical-align:middle; }
    header.topbar .btn-cart{ border-color: rgba(255,255,255,.7) !important; color: var(--text-light) !important; padding: .55rem 1.1rem; }
    header.topbar .btn-cart:hover{ background: rgba(255,255,255,.12) !important; }
    header.topbar .search-form .form-control{ box-shadow:none; }
    header.topbar .search-form .form-control:focus{ border-color: var(--aqua); }

    .badge-accent{ background: var(--accent); color:#fff; }

    .section-bar{ background: var(--bg-soft); }
    .section-list .nav-link{ font-weight:600; color: var(--primary); white-space:nowrap; }
    .section-list .nav-link:hover{ color: var(--secondary); }

    .hero-img{ max-height:420px; object-fit:cover; }
    .carousel-caption h2,.carousel-caption p{ text-shadow:0 2px 10px rgba(0,0,0,.4); }

    .nav-item { display: block; }
    .nav-item a {
      display: flex; align-items: center; justify-content: center;
      color: var(--primary-color); font-size: 1rem; padding: 12px 0; margin: 0 8px; border-radius: 5px;
    }
    .nav-item.active a { background: rgba(106, 109, 155, 0.4); text-decoration: none; box-shadow: 0px 1px 4px var(--shadow-color); }
    .nav-icon { width: 40px; height: 20px; font-size: 1.1rem; }
    .nav-text { display: block; width: 70px; height: 20px; letter-spacing: 0; }

    .banner-top{
      background: linear-gradient(90deg, var(--aqua) 0%, var(--accent) 100%);
      color:#fff; text-align:center; padding:2rem 1rem; margin:2rem 0 1.25rem 0;
      border-radius:.75rem; box-shadow:0 6px 20px rgba(0,0,0,.2);
    }
    .banner-top h2{ font-size:2rem; font-weight:900; text-transform:uppercase; letter-spacing:2px; margin:0; }

    .card.card-product{
      border:0; border-radius:1rem; overflow:hidden;
      box-shadow:0 8px 28px rgba(0,0,0,.08);
      transition:transform .2s ease, box-shadow .2s ease;
      border-top: 4px solid var(--aqua);
    }
    .card.card-product:hover{ transform: translateY(-2px); box-shadow:0 12px 32px rgba(0,0,0,.12); }
    .card-product img{ width:100%; height:230px; object-fit:cover; }
    .card-product .card-body{ padding:1rem; }
    .card-product .card-title{ margin-bottom:.25rem; }
    .price-big{ font-size:1.25rem; font-weight:800; color: var(--primary); }

    .btn-primary{ background: var(--primary); border-color: var(--primary); }
    .btn-primary:hover{ background:#003b5c; border-color:#003b5c; }
    .btn-outline-primary{ color: var(--primary); border-color: var(--primary); }
    .btn-outline-primary:hover{ background: var(--primary); color:#fff; border-color: var(--primary); }
    .btn-secondary{ background: var(--secondary); border-color: var(--secondary); }
    .btn-secondary:hover{ background:#008786; border-color:#008786; }
    .btn-lgx{ padding:.75rem 1.1rem; font-size:1rem; }

    .btn-aqua{ background: var(--aqua); border-color: var(--aqua); color:#024b63; font-weight:700; }
    .btn-aqua:hover,.btn-aqua:focus{ background:#57c8c4; border-color:#57c8c4; color:#013346; }

    .card-actions{ gap:.5rem; }
    @media (min-width:768px){ .card-actions .btn{ min-width:46%; } }

    .cart-dropdown .dropdown-menu{
      min-width: 420px; max-width: 460px; max-height: 70vh; overflow-y: auto;
      box-shadow: 0 16px 36px rgba(0,0,0,.24);
      border:0; border-radius: 14px;
    }
    .mini-cart-empty{ font-style:italic; color:#6b7e88; font-size:.98rem; }

    footer{ background: var(--primary); color:#ffffff; }
    footer a{ color:#ffffff; }
    footer a:hover{ color: var(--aqua); }
  </style>
</head>
<body>

<header class="topbar">
  <div class="container py-3">
    <div class="row g-3 align-items-center">
      <div class="col-12 col-md-3 text-center text-md-start">
        <a href="index.php" class="navbar-brand m-0 p-0 d-inline-flex align-items-center gap-2 text-decoration-none">
          <img src="images/logo.png" alt="Don Camarón" class="logo-header">
          <span class="fw-bold h4 m-0">Don Camarón</span>
        </a>
        <li class="nav-item">
          <a href="/php/logout.php">
            <i class='bx bxs-log-out nav-icon'></i>
            <span class="nav-text">Salir</span>
          </a>
        </li>
      </div>

      <div class="col-12 col-md-6">
        <form action="index.php" method="get" class="search-form">
          <div class="input-group input-group-lg shadow-sm">
            <span class="input-group-text bg-white border-end-0">
              <i class="bi bi-search"></i>
            </span>
            <input
              type="search"
              class="form-control border-start-0"
              name="q"
              value="<?php echo htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
              placeholder="Buscar producto..."
              aria-label="Buscar producto"
            >
            <button class="btn btn-secondary fw-bold" type="submit">Buscar</button>
          </div>
        </form>
      </div>

      <div class="col-12 col-md-3 text-center text-md-end cart-dropdown">
        <div class="dropdown">
          <a href="#" class="btn position-relative btn-cart dropdown-toggle"
             id="cartDropdown" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
            <i class="bi bi-bag-fill cart-icon"></i>
            <span class="ms-2 fw-bold">Carrito</span>
            <span id="num_cart"
                  class="position-absolute top-0 start-100 translate-middle badge rounded-pill badge-accent"
                  aria-live="polite" aria-atomic="true">
              <?php echo (int)$num_cart; ?>
            </span>
          </a>

          <div class="dropdown-menu dropdown-menu-end p-0" aria-labelledby="cartDropdown">
            <div class="p-3">
              <h6 class="mb-2">Mi carrito</h6>
              <div id="mini_cart_items" class="small">
                <em class="mini-cart-empty">Tu carrito está vacío</em>
              </div>
            </div>
            <div class="px-3 pb-2 d-flex justify-content-between align-items-center">
              <strong>Subtotal:</strong>
              <span id="mini_cart_subtotal">$0.00</span>
            </div>
            <div class="mini-cart-footer p-3 pt-2">
              <div class="d-flex gap-2">
                <button type="button" id="btn_empty_cart" class="btn btn-outline-danger btn-sm flex-grow-1">Vaciar</button>
                <a href="captura.php" class="btn btn-primary btn-sm flex-grow-1">Ir al carrito</a>
              </div>
            </div>
          </div>
        </div>        
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

<div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
  <div class="carousel-indicators">
    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true"></button>
    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
  </div>

  <div class="carousel-inner">
    <div class="carousel-item active">
      <img src="images/hero/slide1.jpg" class="d-block w-100 hero-img" alt="Promoción registro"
           loading="eager" onerror="this.src='images/hero/slide1.jpg'">
      <div class="carousel-caption d-none d-md-block">
        <h2 class="fw-bold">¡Regístrate y obtén 20% OFF!</h2>
        <p>Válido en tu primera compra</p>
      </div>
    </div>

    <div class="carousel-item">
      <img src="images/hero/slide2.jpg" class="d-block w-100 hero-img" alt="Del mar a tu mesa"
           loading="lazy" onerror="this.src='images/hero/slide2.jpg'">
      <div class="carousel-caption d-none d-md-block">
        <h2 class="fw-bold">Del mar a tu mesa</h2>
        <p></p>
      </div>
    </div>

    <div class="carousel-item">
      <img src="images/hero/slide3.jpg" class="d-block w-100 hero-img" alt="Calidad premium"
           loading="lazy" onerror="this.src='images/hero/slide3.jpg'">
      <div class="carousel-caption d-none d-md-block">
        <h2 class="fw-bold">Calidad premium</h2>
        <p>Mariscos y pescados siempre frescos</p>
      </div>
    </div>
  </div>

  <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
    <span class="visually-hidden">Anterior</span>
  </button>
  <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
    <span class="carousel-control-next-icon" aria-hidden="true"></span>
    <span class="visually-hidden">Siguiente</span>
  </button>
</div>

<main class="py-4">
  <div class="container">

    <?php if ($err): ?>
      <div class="alert alert-danger">
        <strong>Error:</strong> <?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?><br>
        <small>Verifica que exista la tabla <code>producto</code> y las columnas <code>id, nombre, precio, imagen, activo</code>.</small>
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['q']) && $_GET['q'] !== ''): ?>
      <p class="text-muted mb-3">Resultados para: <strong><?php echo htmlspecialchars($_GET['q'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
    <?php endif; ?>

    <div class="banner-top">
      <h2><i class="bi bi-fire me-2"></i>LO MÁS VENDIDO</h2>
    </div>

    <!-- Grid de productos -->
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-3">
      <?php if (!$err && $resultado): ?>
        <?php foreach ($resultado as $row): ?>
          <?php
            $id      = (int)$row['id'];
            $nombre  = htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8');
            $precio  = number_format((float)$row['precio'], 2, '.', ',');

            // Resolver URL de imagen (nuevo y legacy) + cache-buster
            $imgUrl = resolve_image_url_public($row['imagen'] ?? '');
            $imgUrl = add_cache_buster($imgUrl);

            $token   = hash_hmac('sha1', (string)$id, KEY_TOKEN);
          ?>
          <div class="col">
            <div class="card card-product h-100 position-relative">
              <img loading="lazy"
                   src="<?php echo htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8'); ?>"
                   onerror="this.onerror=null;this.src='/components/usuario/menu/images/no-photo.jpeg';"
                   alt="<?php echo $nombre; ?>">

              <div class="card-body d-flex flex-column">
                <h5 class="card-title mb-1"><?php echo $nombre; ?></h5>
                <p class="text-muted small mb-2">Mariscos frescos • Preparación al gusto</p>
                <div class="price-big mb-3">$<?php echo $precio; ?></div>

                <div class="mt-auto d-flex card-actions">
                  <a href="details.php?id=<?php echo $id; ?>&token=<?php echo $token; ?>"
                     class="btn btn-outline-primary btn-lgx">
                    <i class="bi bi-info-circle me-1"></i> Detalles
                  </a>
                  
                  <button class="btn btn-aqua btn-lgx"
                          type="button"
                          onclick="addProducto(<?php echo $id; ?>, '<?php echo $token; ?>')">
                    <i class="bi bi-bag-plus-fill me-1"></i> Agregar
                  </button>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <?php if (!$err): ?>
          <div class="col">
            <div class="alert alert-info">No hay productos para mostrar.</div>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>

  </div>
</main>


<!-- inicio del Chatbot -->


<!-- Code :) -->
   <button class="chatbot__button">
      <span class="material-symbols-outlined">mode_comment</span>
      <span class="material-symbols-outlined">close</span>
    </button>
    <div class="chatbot">
      <div class="chatbot__header">
        <h3 class="chatbox__title">Chatbot</h3>
        <span class="material-symbols-outlined">close</span>
      </div>
      <ul class="chatbot__box">
        <li class="chatbot__chat incoming">
          <span class="material-symbols-outlined">smart_toy</span>
          <p>Hi there. How can I help you today?</p>
        </li>
        <li class="chatbot__chat outgoing">
          <p>...</p>
        </li>
      </ul>
      <div class="chatbot__input-box">
        <textarea
          class="chatbot__textarea"
          placeholder="Enter a message..."
          required
        ></textarea>
        <span id="send-btn" class="material-symbols-outlined">send</span>
      </div>
    </div>
<!-- final del Chatbot -->



<footer>
  <div class="container py-4">
    <div class="row align-items-center g-4">
      <div class="col-12 col-lg-3 text-center text-lg-start">
        <div style="width:120px;height:38px;overflow:hidden;display:inline-block;line-height:0;">
          <img src="images/logo.png?v=3" alt="Don Camarón" style="height:100%;width:auto;display:block;">
        </div>
        <p style="margin:8px 0 0 0;font-style:italic;color:#d9ecff;">¡El mar directo hasta tu mesa!</p>
      </div>

      <div class="col-12 col-lg-6 text-center">
        <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:14px;font-size:28px;color:#fff;">
          <i class='bx bxl-visa' title="Visa"></i>
          <i class='bx bxl-mastercard' title="Mastercard"></i>
          <i class='bx bxl-amex' title="American Express"></i>
          <i class='bx bxl-paypal' title="PayPal"></i>
          <i class='bx bxl-apple' title="Apple Pay"></i>
          <i class='bx bxl-google' title="Google Pay"></i>
          <i class='bx bx-wallet' title="Mercado Pago"></i>
          <span style="display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:6px;background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.25);font-weight:600;font-size:14px;">
            <i class='bx bx-shield-alt-2'></i> Pago seguro
          </span>
        </div>

        <div style="margin-top:12px;display:flex;justify-content:center;gap:12px;">
          <a href="#" aria-label="Facebook" style="width:40px;height:40px;display:grid;place-items:center;border-radius:50%;background:rgba(255,255,255,.15);color:#ffffff;text-decoration:none;box-shadow:0 4px 12px rgba(0,0,0,.2);"><i class="bi bi-facebook"></i></a>
          <a href="#" aria-label="Instagram" style="width:40px;height:40px;display:grid;place-items:center;border-radius:50%;background:rgba(255,255,255,.15);color:#ffffff;text-decoration:none;box-shadow:0 4px 12px rgba(0,0,0,.2);"><i class="bi bi-instagram"></i></a>
          <a href="https://wa.me/525616677657?text=Hola%20Don%20Camar%C3%B3n%2C%20quiero%20hacer%20un%20pedido" aria-label="WhatsApp" target="_blank" rel="noopener noreferrer"
             style="width:40px;height:40px;display:grid;place-items:center;border-radius:50%;background:#25D366;color:#ffffff;text-decoration:none;box-shadow:0 4px 12px rgba(0,0,0,.2);">
            <i class="bi bi-whatsapp"></i>
          </a>
        </div>
      </div>

      <div class="col-12 col-lg-3 text-center text-lg-start">
        <div style="font-size:.95rem;">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
            <i class="bi bi-telephone"></i>
            <a href="tel:+525616677657" style="color:#ffffff;text-decoration:none;">561 667 7657</a>
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

    <div class="row mt-4">
      <div class="col-12">
        <div style="border:1px solid rgba(255,255,255,.25);border-radius:12px;overflow:hidden;">
          <iframe
            loading="lazy" allowfullscreen referrerpolicy="no-referrer-when-downgrade"
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3764.996227310386!2d-99.088!3d19.329!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2sLa%20Nueva%20Viga!5e0!3m2!1ses-419!2smx!4v1700000000000"
            style="border:0;width:100%;height:230px;"></iframe>
        </div>
      </div>
    </div>

    <div class="row mt-4 pt-3" style="border-top:1px solid rgba(255,255,255,.25);">
      <div class="col-12 col-lg-6 small text-center text-lg-start">
        © <?php echo date("Y"); ?> <strong>Don Camarón Online</strong>
      </div>
      <div class="col-12 col-lg-6 small">
        <ul class="list-inline m-0 d-flex justify-content-center justify-content-lg-end" style="gap:16px;">
          <li class="list-inline-item"><a href="#">Términos y Condiciones</a></li>
          <li class="list-inline-item"><a href="#">Privacidad</a></li>
          <li class="list-inline-item"><a href="#">Reembolsos</a></li>
          <li class="list-inline-item"><a href="#">Envíos</a></li>
        </ul>
      </div>
    </div>

  </div>

  <a href="https://wa.me/525616677657?text=Hola%20Don%20Camar%C3%B3n%2C%20quiero%20hacer%20un%20pedido"
     target="_blank" rel="noopener noreferrer" aria-label="Chat WhatsApp"
     style="position:fixed;left:18px;bottom:18px;width:56px;height:56px;border-radius:50%;background:#25D366;color:#fff;display:grid;place-items:center;box-shadow:0 6px 18px rgba(37,211,102,.5);z-index:1000;text-decoration:none;font-size:1.7rem;">
    <i class="bi bi-whatsapp"></i>
  </a>
</footer>
<script src="/js/main-chatbot.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p"
        crossorigin="anonymous"></script>
<script>
function applyMiniCartResponse(data){
  const badge = document.getElementById('num_cart');
  if (badge && typeof data.numero !== 'undefined') badge.textContent = data.numero;

  const cont = document.getElementById('mini_cart_items');
  if (cont) cont.innerHTML = (typeof data.html === 'string')
    ? data.html
    : '<em class="mini-cart-empty">Tu carrito está vacío</em>';

  const subt = document.getElementById('mini_cart_subtotal');
  if (subt && typeof data.subtotal_fmt !== 'undefined') subt.textContent = '$' + data.subtotal_fmt;
}

function addProducto (id, token){
  const fd = new FormData();
  fd.append('id', id);
  fd.append('token', token);
  fetch('clases/carrito.php', { method:'POST', body: fd })
    .then(r=>{ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
    .then(data=>{
      if(!data.ok) throw new Error(data.msg || 'Error al agregar');
      applyMiniCartResponse(data);
      const trg = document.getElementById('cartDropdown');
      if (trg) bootstrap.Dropdown.getOrCreateInstance(trg).show();
    })
    .catch(e=>alert('No se pudo agregar: '+e.message));
}

function cartAction(action, id){
  const fd = new FormData();
  fd.append('action', action);
  if (typeof id !== 'undefined') fd.append('id', id);
  fetch('clases/carrito.php', { method:'POST', body: fd })
    .then(r=>{ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
    .then(data=>{
      if(!data.ok) throw new Error(data.msg || 'Operación fallida');
      applyMiniCartResponse(data);
      const trg = document.getElementById('cartDropdown');
      if (trg) bootstrap.Dropdown.getOrCreateInstance(trg).show();
    })
    .catch(e=>alert(e.message));
}

document.addEventListener('DOMContentLoaded', function(){
  // Carga inicial del mini-carrito
  fetch('clases/carrito.php?action=mini')
    .then(r=>r.ok?r.json():null)
    .then(data=>{ if(data && data.ok) applyMiniCartResponse(data); })
    .catch(()=>{});

  // Delegación de eventos en mini-carrito
  const cont = document.getElementById('mini_cart_items');
  if (cont) {
    cont.addEventListener('click', function(ev){
      const inc = ev.target.closest('.mini-cart-inc');
      const dec = ev.target.closest('.mini-cart-dec');
      const rem = ev.target.closest('.mini-cart-remove');
      if (inc) { ev.preventDefault(); ev.stopPropagation(); cartAction('increment', inc.dataset.id); }
      if (dec) { ev.preventDefault(); ev.stopPropagation(); cartAction('decrement', dec.dataset.id); }
      if (rem) { ev.preventDefault(); ev.stopPropagation(); cartAction('remove', rem.dataset.id); }
    });
  }
  const btnEmpty = document.getElementById('btn_empty_cart');
  if (btnEmpty) {
    btnEmpty.addEventListener('click', function(ev){
      ev.preventDefault();
      cartAction('empty');
    });
  }
});
</script>
</body>
</html>
