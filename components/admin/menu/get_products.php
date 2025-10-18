<?php
// get_products.php (ADMIN)
require_once __DIR__ . '/../../usuario/menu/config/config.php';
require_once __DIR__ . '/../../usuario/menu/config/database.php';

const TABLE_NAME = 'producto';

// Config base de imágenes (misma que usas en el front)
$CATS = ['camarones','pescados','pulpos_calamares','conchas','ahumados','premium'];
$BASE_DIR = realpath(__DIR__ . '/../../../components/usuario/menu/images/productos');
$BASE_URL = '/components/usuario/menu/images/productos';
$NO_IMG   = '/components/admin/menu/no-image.png';

function resolve_image_url(?string $img) {
  global $CATS, $BASE_DIR, $BASE_URL, $NO_IMG;

  $img = trim((string)$img);
  if ($img === '') return $NO_IMG;

  // Caso nuevo: ya viene "categoria/archivo.ext"
  if (strpos($img, '/') !== false) {
    $abs = $BASE_DIR . DIRECTORY_SEPARATOR . $img;
    return is_file($abs) ? ($BASE_URL . '/' . $img) : $NO_IMG;
  }

  // Caso legacy: sólo "archivo.ext" -> busca en cada categoría
  foreach ($CATS as $cat) {
    $rel = $cat . '/' . $img;
    $abs = $BASE_DIR . DIRECTORY_SEPARATOR . $rel;
    if (is_file($abs)) {
      return $BASE_URL . '/' . $rel;
    }
  }

  // Si no está en ninguna, no-image
  return $NO_IMG;
}

try {
  $con = (new Database())->conectar();
  $stmt = $con->query("
    SELECT id, nombre, precio, descuento, descripcion, imagen, stock, activo, creado_en, actualizado_en
    FROM " . TABLE_NAME . "
    ORDER BY id DESC
  ");
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  foreach ($rows as $r) {
    $imgUrl = resolve_image_url($r['imagen'] ?? '');

    $activoBadge = ($r['activo']
      ? "<span class='badge bg-success'>Sí</span>"
      : "<span class='badge bg-secondary'>No</span>");

    echo "<tr data-id='{$r['id']}'>
      <td>{$r['id']}</td>
      <td><img src='".htmlspecialchars($imgUrl,ENT_QUOTES)."' alt='' style='width:64px;height:64px;object-fit:cover;border-radius:8px;'></td>
      <td>".htmlspecialchars($r['nombre'],ENT_QUOTES)."</td>
      <td>$".number_format((float)$r['precio'],2)."</td>
      <td>".number_format((float)($r['descuento'] ?? 0),2)."%</td>
      <td class='text-break' style='max-width:360px;'>".htmlspecialchars($r['descripcion'],ENT_QUOTES)."</td>
      <td>".(int)$r['stock']."</td>
      <td>{$activoBadge}</td>
      <td>
        <button class='btn btn-sm btn-outline-primary btn-edit'>Editar</button>
        <button class='btn btn-sm btn-outline-danger btn-delete'>Borrar</button>
      </td>
    </tr>";
  }
} catch (Throwable $e) {
  http_response_code(500);
  echo "<tr><td colspan='9' style='color:#ff6b6b;'>Error: ".htmlspecialchars($e->getMessage(),ENT_QUOTES)."</td></tr>";
}
