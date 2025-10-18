<?php
// clases/registrar_email.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// 1) ¡Inicia sesión antes de tocar $_SESSION!
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

try {
  // Sólo POST + JSON
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
    exit;
  }

  $raw = file_get_contents('php://input');
  if ($raw === false || trim($raw) === '') {
    throw new RuntimeException('Cuerpo vacío');
  }

  $data  = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
  $email = isset($data['email']) ? trim((string)$data['email']) : '';

  if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new InvalidArgumentException('Correo no válido');
  }
  if (mb_strlen($email) > 190) {
    throw new InvalidArgumentException('Correo demasiado largo');
  }

  // 2) Guarda en sesión (esto es lo que luego usa gracias.php)
  $_SESSION['cliente_email'] = $email;
 $_SESSION['email_confirmed_for_payment'] = true;



  // 3) (Opcional) Guarda/actualiza en BD
  //    Si no tienes la tabla o falla, no detengas el flujo.
  try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/database.php';

    $con = (new Database())->conectar();
    $con->exec("
      CREATE TABLE IF NOT EXISTS clientes (
        id SERIAL PRIMARY KEY,
        email TEXT UNIQUE NOT NULL,
        creado_en TIMESTAMP NOT NULL DEFAULT NOW(),
        actualizado_en TIMESTAMP
      );
    ");
    $stmt = $con->prepare("
      INSERT INTO clientes (email)
      VALUES (:email)
      ON CONFLICT (email) DO UPDATE
        SET actualizado_en = NOW()
    ");
    $stmt->execute([':email' => $email]);
  } catch (Throwable $e) {
    // Silencioso: la sesión ya quedó con el correo
    // error_log('[registrar_email] ' . $e->getMessage());
  }

  echo json_encode(['ok' => true, 'email' => $email], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
