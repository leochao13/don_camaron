<!-- validacion de usuario -->
  <?php
      		include("/xampp/htdocs/php/polices.php");
      ?>
<?php
declare(strict_types=1);

// Si tu config ya hace session_start(), puedes quitar esta línea.
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require 'config/config.php';
require 'config/database.php';

$db  = new Database();
$con = $db->conectar();

$id    = isset($_GET['id'])    ? (int)$_GET['id']    : 0;
$token = isset($_GET['token']) ? (string)$_GET['token'] : '';

if ($id <= 0 || $token === '') {
  echo 'Error al procesar la petición';
  exit;
}

// Recalcula token
$token_tmp = hash_hmac('sha1', (string)$id, KEY_TOKEN);
if (!hash_equals($token_tmp, $token)) {
  echo 'Error al procesar la petición';
  exit;
}

// ===== Datos del carrito para badge
$num_cart = (isset($_SESSION['carrito']['producto']) && is_array($_SESSION['carrito']['producto']))
  ? count($_SESSION['carrito']['producto'])
  : 0;

// ===== Verificar existencia del producto (POSTGRES: activo = true)
$sql = $con->prepare('SELECT COUNT(id) FROM producto WHERE id = :id AND activo = true');
$sql->bindValue(':id', $id, PDO::PARAM_INT);
$sql->execute();

if ((int)$sql->fetchColumn() <= 0) {
  echo 'Producto no disponible';
  exit;
}

// ===== Cargar datos del producto (POSTGRES: activo = true)
$sql = $con->prepare('
  SELECT nombre, descripcion, precio, descuento
  FROM producto
  WHERE id = :id AND activo = true
  LIMIT 1
');
$sql->bindValue(':id', $id, PDO::PARAM_INT);
$sql->execute();

$row        = $sql->fetch(PDO::FETCH_ASSOC);
$nombre     = (string)$row['nombre'];
$descripcion= (string)$row['descripcion'];
$precio     = (float)$row['precio'];
$descuento  = (float)$row['descuento'];
$precio_desc= $precio - (($precio * $descuento) / 100.0);

// ===== Imágenes
$dir_images = 'images/productos/' . $id . '/';
$rutaImg    = $dir_images . '1.jpg';
if (!is_file($rutaImg)) {
  $rutaImg = 'images/no-photo.jpeg';
}

$imagenes = [];
if (is_dir($dir_images)) {
  $d = dir($dir_images);
  while (false !== ($archivo = $d->read())) {
    $ext = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
    if ($archivo !== '1.jpg' && in_array($ext, ['jpg','jpeg','png','webp'], true)) {
      $imagenes[] = $dir_images . $archivo;
    }
  }
  $d->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tienda de Mariscos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        crossorigin="anonymous">
  <link rel="stylesheet" href="css/estilos.css">
</head>
<body>

<header>
  <div class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <a href="index.php" class="navbar-brand">
        <strong>Tienda de mariscos</strong> <strong>Don Camarón</strong>
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
              data-bs-target="#navbarHeader" aria-controls="navbarHeader"
              aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarHeader">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item"><a href="index.php" class="nav-link active">Inicio</a></li>
          <li class="nav-item"><a href="#" class="nav-link">Contacto</a></li>
        </ul>

        <a href="captura.php" class="btn btn-primary">
          Carrito <span id="num_cart" class="badge bg-secondary"><?php echo (int)$num_cart; ?></span>
        </a>
      </div>
    </div>
  </div>
</header>

<main>
  <div class="container py-4">
    <div class="row g-4">
      <div class="col-md-6 order-md-1">
        <div id="carouselImages" class="carousel slide" data-bs-ride="carousel">
          <div class="carousel-inner">
            <div class="carousel-item active">
              <img src="<?php echo htmlspecialchars($rutaImg, ENT_QUOTES, 'UTF-8'); ?>" class="d-block w-100" alt="Imagen principal">
            </div>

            <?php foreach ($imagenes as $img): ?>
              <div class="carousel-item">
                <img src="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" class="d-block w-100" alt="Imagen secundaria">
              </div>
            <?php endforeach; ?>
          </div>

          <button class="carousel-control-prev" type="button" data-bs-target="#carouselImages" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Anterior</span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#carouselImages" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Siguiente</span>
          </button>
        </div>
      </div>

      <div class="col-md-6 order-md-2">
        <h2><?php echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?></h2>

        <?php if ($descuento > 0): ?>
          <p>
            <del><?php echo MONEDA . number_format($precio, 2, '.', ','); ?></del>
          </p>
          <h2>
            <?php echo MONEDA . number_format($precio_desc, 2, '.', ','); ?>
            <small class="text-success"><?php echo (float)$descuento; ?>% descuento</small>
          </h2>
        <?php else: ?>
          <h2><?php echo MONEDA . number_format($precio, 2, '.', ','); ?></h2>
        <?php endif; ?>

        <p class="lead"><?php echo nl2br(htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8')); ?></p>

        <div class="d-grid gap-3 col-10 mx-auto">
          <a href="captura.php" class="btn btn-primary" type="button">Comprar ahora</a>
          <button class="btn btn-outline-primary" type="button"
                  onclick="addProducto(<?php echo (int)$id; ?>, '<?php echo $token_tmp; ?>')">
            Agregar al carrito
          </button>
        </div>
      </div>
    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script>
function addProducto(id, token){
  const url = "clases/carrito.php";
  const formData = new FormData();
  formData.append('id', id);
  formData.append('token', token);

  fetch(url, { method: 'POST', body: formData, mode: 'cors' })
    .then(r => r.json())
    .then(data => {
      if (data.ok) {
        const elemento = document.getElementById("num_cart");
        if (elemento) elemento.textContent = data.numero;
      } else {
        alert(data.msg || 'No se pudo agregar al carrito');
      }
    })
    .catch(e => alert('Error: ' + e.message));
}
</script>
</body>
</html>
