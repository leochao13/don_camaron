<?php
// delete_product.php
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../usuario/menu/config/config.php';
require_once __DIR__ . '/../../usuario/menu/config/database.php';

const TABLE_NAME = 'producto'; // <<-- SINGULAR

try {
  $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  if ($id <= 0) { throw new Exception('ID inválido'); }

  $con = (new Database())->conectar();
  $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // Obtener ruta de imagen antes de eliminar
  $stmt = $con->prepare("SELECT imagen FROM ".TABLE_NAME." WHERE id = :id");
  $stmt->execute([':id' => $id]);
  $relPath = (string)($stmt->fetchColumn() ?: '');

  // Borrar registro
  $stmt = $con->prepare("DELETE FROM ".TABLE_NAME." WHERE id = :id");
  $stmt->execute([':id' => $id]);

  // Borrar archivo físico si existe
  if ($relPath !== '') {
    $baseDir  = realpath(__DIR__ . '/../../../components/usuario/menu/images/productos');
    $absFile  = $baseDir . DIRECTORY_SEPARATOR . $relPath;
    if ($baseDir && is_file($absFile)) { @unlink($absFile); }
  }

  echo json_encode(['ok' => true, 'msg' => 'Producto eliminado']);
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
