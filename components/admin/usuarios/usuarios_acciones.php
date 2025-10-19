<?php
// components/admin/usuarios/usuarios_acciones.php
// ¡Nada de HTML aquí! Solo JSON.

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

// Sesión (necesaria para detectar si se borra el propio perfil)
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

function jexit(array $data, int $status=200): void {
  http_response_code($status);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

// --- Conexión a BD ---
try {
  require_once __DIR__ . '/../../usuario/menu/config/config.php';
  require_once __DIR__ . '/../../usuario/menu/config/database.php';
  $db  = new Database();
  $con = $db->conectar(); // PDO
} catch (Throwable $e) {
  jexit(['ok'=>false, 'msg'=>'No se pudo conectar a la BD: '.$e->getMessage()], 500);
}

// --- Utilidades ---
function db_driver(PDO $pdo): string {
  return (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME); // 'mysql' | 'pgsql' | ...
}

function ensure_upload_dir(): string {
  // Carpeta física real: C:\xampp\htdocs\image\Usuarios
  $dir = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\') . '/image/Usuarios';
  if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
  return $dir;
}

function clean_old_user_images(string $dir, int $id): void {
  foreach (glob($dir.'/'.$id.'.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [] as $f) {
    @unlink($f);
  }
}

function image_to_webp(string $src, string $dstWebp, int $quality=82): bool {
  if (!function_exists('imagewebp')) return false;
  $info = @getimagesize($src);
  if (!$info) return false;
  $mime = $info['mime'] ?? '';
  $im = null;
  if ($mime === 'image/jpeg')      $im = @imagecreatefromjpeg($src);
  elseif ($mime === 'image/png')   $im = @imagecreatefrompng($src);
  elseif ($mime === 'image/webp')  $im = @imagecreatefromwebp($src);
  else return false;

  if (!$im) return false;
  imagepalettetotruecolor($im);
  imagealphablending($im, true);
  imagesavealpha($im, true);

  $ok = @imagewebp($im, $dstWebp, $quality);
  imagedestroy($im);
  return (bool)$ok;
}

/**
 * Guarda la foto subida para el usuario $id.
 * - Valida tipo/tamaño
 * - Convierte a WEBP si es posible
 * - Elimina previas {id}.{ext}
 * - Devuelve nombre relativo (ej. image/Usuarios/5.webp) o null si no se subió
 * - No escribe la ruta en BD (el listado localiza por archivo/id)
 */
function handle_user_photo_upload(int $id): ?string {
  if (!isset($_FILES['foto']) || !is_array($_FILES['foto'])) return null;
  if (($_FILES['foto']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;

  $err = (int)($_FILES['foto']['error'] ?? UPLOAD_ERR_OK);
  if ($err !== UPLOAD_ERR_OK) throw new RuntimeException('Error al subir la imagen (código '.$err.').');

  $tmp  = (string)$_FILES['foto']['tmp_name'];
  $size = (int)($_FILES['foto']['size'] ?? 0);
  if ($size <= 0) throw new RuntimeException('Archivo vacío.');
  if ($size > 2*1024*1024) throw new RuntimeException('La imagen supera 2MB.');

  $info = @getimagesize($tmp);
  if (!$info) throw new RuntimeException('No es una imagen válida.');
  $mime = $info['mime'] ?? '';
  $allowed = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp'];
  if (!isset($allowed[$mime])) throw new RuntimeException('Formato no válido (usa JPG/PNG/WebP).');

  $dirAbs = ensure_upload_dir();
  if (!is_dir($dirAbs) || !is_writable($dirAbs)) {
    throw new RuntimeException('No se puede escribir en ' . $dirAbs);
  }

  // Limpia previas
  clean_old_user_images($dirAbs, $id);

  // Intentar WEBP
  $dstWebp = $dirAbs.'/'.$id.'.webp';
  if (image_to_webp($tmp, $dstWebp, 82)) {
    return 'image/Usuarios/'.$id.'.webp';
  }

  // Fallback: extensión original
  $ext = $allowed[$mime];
  $dst = $dirAbs.'/'.$id.'.'.$ext;
  if (!@move_uploaded_file($tmp, $dst)) {
    if (!@copy($tmp, $dst)) throw new RuntimeException('No se pudo guardar la imagen.');
  }
  return 'image/Usuarios/'.$id.'.'.$ext;
}

// --- Solo POST ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  jexit(['ok'=>false,'msg'=>'Método no permitido'], 405);
}

// --- Entradas ---
$action     = isset($_POST['action']) ? trim((string)$_POST['action']) : '';
$id         = isset($_POST['id'])     ? (int)$_POST['id'] : 0;

$nombre     = isset($_POST['nombre'])     ? trim((string)$_POST['nombre']) : '';
$correo     = isset($_POST['correo'])     ? trim((string)$_POST['correo']) : '';
$rol        = isset($_POST['rol'])        ? trim((string)$_POST['rol'])    : '';
$contrasena = isset($_POST['contrasena']) ? (string)$_POST['contrasena']   : '';

// Whitelist
$ROL_VALIDOS = ['admin','mesero','cliente'];
if ($rol !== '' && !in_array($rol, $ROL_VALIDOS, true)) {
  jexit(['ok'=>false,'msg'=>'Rol inválido']);
}

// --- DELETE ---
if ($action === 'delete') {
  if ($id <= 0) jexit(['ok'=>false,'msg'=>'ID inválido']);

  try {
    // Evitar borrar el último admin
    $stmt = $con->query("SELECT COUNT(*) FROM usuarios WHERE rol='admin'");
    $numAdmins = (int)$stmt->fetchColumn();

    $stmtOne = $con->prepare("SELECT rol FROM usuarios WHERE id = ?");
    $stmtOne->execute([$id]);
    $rolDel = (string)($stmtOne->fetchColumn() ?: '');

    if ($rolDel === 'admin' && $numAdmins <= 1) {
      jexit(['ok'=>false,'msg'=>'No puedes borrar el último administrador.']);
    }

    // Borrar registro
    $del = $con->prepare("DELETE FROM usuarios WHERE id = ?");
    $del->execute([$id]);

    // Borrar archivos de imagen asociados
    try {
      $dirAbs = ensure_upload_dir();
      clean_old_user_images($dirAbs, $id);
    } catch (Throwable $e) { /* no bloquear por fallo de FS */ }

    // Si es el mismo usuario conectado -> cerrar sesión y marcar logout
    $currentId = (int)($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0); // ajusta la clave si usas otra
    if ($id === $currentId && $currentId > 0) {
      $_SESSION = [];
      if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
      }
      session_destroy();
      jexit(['ok'=>true,'msg'=>'Tu cuenta fue eliminada.','logout'=>true]);
    }

    jexit(['ok'=>true,'msg'=>'Usuario eliminado','logout'=>false]);
  } catch (Throwable $e) {
    jexit(['ok'=>false,'msg'=>'Error al eliminar: '.$e->getMessage()]);
  }
}

// --- CREATE / UPDATE (guardar) ---
if ($nombre === '' || $correo === '' || $rol === '') {
  jexit(['ok'=>false,'msg'=>'Faltan datos obligatorios (nombre, correo, rol).']);
}
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
  jexit(['ok'=>false,'msg'=>'Correo inválido.']);
}

try {
  // ¿Correo duplicado?
  if ($id > 0) {
    $du = $con->prepare("SELECT id FROM usuarios WHERE correo = ? AND id <> ?");
    $du->execute([$correo, $id]);
  } else {
    $du = $con->prepare("SELECT id FROM usuarios WHERE correo = ?");
    $du->execute([$correo]);
  }
  if ($du->fetch()) {
    jexit(['ok'=>false,'msg'=>'El correo ya está registrado.']);
  }

  $driver = db_driver($con);

  if ($id > 0) {
    // UPDATE
    if ($contrasena !== '') {
      if (strlen($contrasena) < 6) jexit(['ok'=>false,'msg'=>'La contraseña debe tener al menos 6 caracteres.']);
      $hash = password_hash($contrasena, PASSWORD_BCRYPT);
      $sql  = "UPDATE usuarios
               SET nombre = :n, correo = :c, rol = :r, contrasena = :p
               WHERE id = :id";
      $st = $con->prepare($sql);
      $st->execute([':n'=>$nombre, ':c'=>$correo, ':r'=>$rol, ':p'=>$hash, ':id'=>$id]);
    } else {
      $sql = "UPDATE usuarios
              SET nombre = :n, correo = :c, rol = :r
              WHERE id = :id";
      $st = $con->prepare($sql);
      $st->execute([':n'=>$nombre, ':c'=>$correo, ':r'=>$rol, ':id'=>$id]);
    }

    // Foto (opcional)
    try {
      handle_user_photo_upload($id);
    } catch (Throwable $fe) {
      // Aviso sin bloquear el update
      jexit(['ok'=>true,'msg'=>'Usuario actualizado (aviso imagen: '.$fe->getMessage().')']);
    }

    jexit(['ok'=>true,'msg'=>'Usuario actualizado']);

  } else {
    // INSERT
    if ($contrasena === '' || strlen($contrasena) < 6) {
      jexit(['ok'=>false,'msg'=>'La contraseña es obligatoria y debe tener al menos 6 caracteres.']);
    }
    $hash = password_hash($contrasena, PASSWORD_BCRYPT);

    if ($driver === 'pgsql') {
      // PostgreSQL: RETURNING id
      $sql = "INSERT INTO usuarios (nombre, correo, contrasena, rol, fecha_registro)
              VALUES (:n, :c, :p, :r, NOW())
              RETURNING id";
      $st = $con->prepare($sql);
      $st->execute([':n'=>$nombre, ':c'=>$correo, ':p'=>$hash, ':r'=>$rol]);
      $newId = (int)$st->fetchColumn();
    } else {
      // MySQL / otros
      $sql = "INSERT INTO usuarios (nombre, correo, contrasena, rol, fecha_registro)
              VALUES (:n, :c, :p, :r, NOW())";
      $st = $con->prepare($sql);
      $st->execute([':n'=>$nombre, ':c'=>$correo, ':p'=>$hash, ':r'=>$rol]);
      $newId = (int)$con->lastInsertId();
    }

    // Foto (opcional)
    if ($newId > 0) {
      try {
        handle_user_photo_upload($newId);
      } catch (Throwable $fe) {
        jexit(['ok'=>true,'msg'=>'Usuario creado (aviso imagen: '.$fe->getMessage().')','id'=>$newId]);
      }
    }

    jexit(['ok'=>true,'msg'=>'Usuario creado','id'=>$newId]);
  }

} catch (Throwable $e) {
  jexit(['ok'=>false,'msg'=>'Error al guardar: '.$e->getMessage()]);
}
