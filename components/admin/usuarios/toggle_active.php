<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json; charset=utf-8');

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0){ echo json_encode(['ok'=>false,'msg'=>'ID inválido']); exit; }

try{
  $con = (new Database())->conectar();
  $stmt = $con->prepare("UPDATE usuario SET activo = NOT activo WHERE id = :id");
  $stmt->execute([':id'=>$id]);
  echo json_encode(['ok'=>true]);
}catch(Throwable $e){
  echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
