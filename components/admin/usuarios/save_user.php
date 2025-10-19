<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

function ok($msg='ok', $extra=[]){ echo json_encode(['ok'=>true,'msg'=>$msg]+$extra); exit; }
function bad($msg, $code=400){ http_response_code($code); echo json_encode(['ok'=>false,'msg'=>$msg]); exit; }

$nombre = trim($_POST['nombre'] ?? '');
$email  = trim($_POST['email'] ?? '');
$rol    = trim($_POST['rol'] ?? 'cliente');
$activo = isset($_POST['activo']) && $_POST['activo']=='0' ? 0 : 1;
$id     = (int)($_POST['id'] ?? 0);
$old    = trim($_POST['old_avatar'] ?? '');
$pass   = (string)($_POST['password'] ?? '');

if ($nombre==='' || $email==='' || !filter_var($email, FILTER_VALIDATE_EMAIL)) bad('Datos inválidos');
if (!in_array($rol, ['admin','mesero','cliente'], true)) bad('Rol inválido');

$avatarRel = $old;

// subir avatar (opcional)
if (!empty($_FILES['avatar']['name']) && $_FILES['avatar']['error']===UPLOAD_ERR_OK){
  $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) bad('Formato de imagen no permitido');
  $dir = $_SERVER['DOCUMENT_ROOT'].'/images/usuarios';
  if (!is_dir($dir)) @mkdir($dir, 0775, true);
  $fname = 'u_'.date('Ymd_His').'_'.bin2hex(random_bytes(3)).'.'.$ext;
  $destFs = $dir.'/'.$fname;
  if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $destFs)) bad('No se pudo guardar el avatar');
  $avatarRel = 'images/usuarios/'.$fname;
}

try{
  $con = (new Database())->conectar();

  if ($id > 0){
    // editar
    if ($pass !== ''){
      $ph = password_hash($pass, PASSWORD_DEFAULT);
      $sql = "UPDATE usuario SET nombre=:n, email=:e, rol=:r, activo=:a, avatar=:av, password_hash=:ph WHERE id=:id";
      $stmt = $con->prepare($sql);
      $stmt->execute([':n'=>$nombre, ':e'=>$email, ':r'=>$rol, ':a'=>$activo, ':av'=>$avatarRel?:null, ':ph'=>$ph, ':id'=>$id]);
    } else {
      $sql = "UPDATE usuario SET nombre=:n, email=:e, rol=:r, activo=:a, avatar=:av WHERE id=:id";
      $stmt = $con->prepare($sql);
      $stmt->execute([':n'=>$nombre, ':e'=>$email, ':r'=>$rol, ':a'=>$activo, ':av'=>$avatarRel?:null, ':id'=>$id]);
    }
    ok('Actualizado');
  } else {
    // crear (password requerido)
    if ($pass==='') bad('La contraseña es requerida para crear');
    $ph = password_hash($pass, PASSWORD_DEFAULT);

    $sql = "INSERT INTO usuario (nombre,email,password_hash,rol,activo,avatar) VALUES (:n,:e,:ph,:r,:a,:av)";
    $stmt = $con->prepare($sql);
    $stmt->execute([':n'=>$nombre, ':e'=>$email, ':ph'=>$ph, ':r'=>$rol, ':a'=>$activo, ':av'=>$avatarRel?:null]);
    ok('Creado');
  }

} catch(Throwable $e){
  // Email duplicado u otros errores SQL
  bad($e->getMessage(), 500);
}
