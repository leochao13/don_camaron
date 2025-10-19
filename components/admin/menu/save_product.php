<?php
// save_product.php
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../usuario/menu/config/config.php';
require_once __DIR__ . '/../../usuario/menu/config/database.php';

const TABLE_NAME = 'producto'; // <<-- SINGULAR

try {
  $con = (new Database())->conectar();
  $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $id          = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  $nombre      = trim($_POST['nombre'] ?? '');
  $precio      = (float)($_POST['precio'] ?? 0);
  $descuento   = (float)($_POST['descuento'] ?? 0);
  $descripcion = trim($_POST['descripcion'] ?? '');
  $stock       = (int)($_POST['stock'] ?? 0);
  $activo      = (int)($_POST['activo'] ?? 1);
  $categoria   = preg_replace('/[^a-z0-9_]/i', '', $_POST['categoria'] ?? '');
  $old_image   = trim($_POST['old_image'] ?? '');

  if ($nombre === '' || $precio < 0 || $stock < 0) { throw new Exception('Datos inválidos.'); }
  if ($categoria === '') { throw new Exception('Categoría inválida.'); }

  $baseDir   = realpath(__DIR__ . '/../../../components/usuario/menu/images/productos');
  $uploadDir = $baseDir . '/' . $categoria;
  if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0777, true); }

  // Mantener ruta anterior por defecto
  $newFilePath = $old_image;

  if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) { throw new Exception('Error al subir imagen.'); }
    $tmp  = $_FILES['imagen']['tmp_name'];
    $type = mime_content_type($tmp);
    if (!in_array($type, ['image/jpeg','image/png','image/webp','image/gif'])) { throw new Exception('Formato no permitido.'); }
    if ($_FILES['imagen']['size'] > 4*1024*1024) { throw new Exception('La imagen excede 4 MB.'); }

    $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
    $fileName = 'prod_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

    if (!move_uploaded_file($tmp, $uploadDir . '/' . $fileName)) { throw new Exception('No se pudo mover la imagen.'); }

    $newFilePath = $categoria . '/' . $fileName;

    // Borra la anterior si existía
    if ($id > 0 && $old_image && is_file($baseDir . '/' . $old_image)) { @unlink($baseDir . '/' . $old_image); }
  }

  if ($id > 0) {
    $sql = "
      UPDATE ".TABLE_NAME."
      SET nombre = :nombre,
          precio = :precio,
          descuento = :descuento,
          descripcion = :descripcion,
          imagen = :imagen,
          stock = :stock,
          activo = :activo,
          actualizado_en = NOW()
      WHERE id = :id
    ";
    $stmt = $con->prepare($sql);
    $stmt->execute([
      ':nombre' => $nombre,
      ':precio' => $precio,
      ':descuento' => $descuento,
      ':descripcion' => $descripcion,
      ':imagen' => $newFilePath,
      ':stock' => $stock,
      ':activo' => $activo,
      ':id' => $id,
    ]);
    $msg = 'Producto actualizado';
  } else {
    $sql = "
      INSERT INTO ".TABLE_NAME."
      (nombre, precio, descuento, descripcion, imagen, stock, activo, creado_en, actualizado_en)
      VALUES (:nombre, :precio, :descuento, :descripcion, :imagen, :stock, :activo, NOW(), NOW())
      RETURNING id
    ";
    $stmt = $con->prepare($sql);
    $stmt->execute([
      ':nombre' => $nombre,
      ':precio' => $precio,
      ':descuento' => $descuento,
      ':descripcion' => $descripcion,
      ':imagen' => $newFilePath,
      ':stock' => $stock,
      ':activo' => $activo,
    ]);
    $id = (int)($stmt->fetchColumn() ?: 0);
    $msg = 'Producto creado';
  }

  echo json_encode(['ok'=>true, 'id'=>$id, 'msg'=>$msg, 'imagen'=>$newFilePath]);
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
}
