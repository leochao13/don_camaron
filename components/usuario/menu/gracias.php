<?php
declare(strict_types=1);

session_start();

// ✅ Validación de usuario (antes de cualquier salida)
include("C:/xampp/htdocs/php/polices.php");

// gracias.php

// =========================
// 1) Datos del pedido
// =========================
$data = $_SESSION['ultimo_pedido'] ?? [];

$folio    = isset($_GET['folio']) ? preg_replace('/[^A-Z0-9\-]/i', '', $_GET['folio']) : ($data['folio'] ?? '');
$order_id = isset($_GET['order_id']) ? preg_replace('/[^A-Za-z0-9\-]/', '', $_GET['order_id']) : ($data['order_id'] ?? '');
$total    = $data['total']   ?? '';
$status   = $data['status']  ?? '';

// Correo del comprador según PayPal (capturado en captura_api.php)
$payer_email = $data['payer_email'] ?? ($data['email'] ?? '');

// Si no hay folio, redirige
if ($folio === '') {
  header('Location: index.php');
  exit;
}

// =========================
// 2) Elegir correo destino
// =========================
$registered_email = $_SESSION['cliente_email'] ?? '';
$registered_email = is_string($registered_email) ? trim($registered_email) : '';

$to_email = '';
if ($registered_email !== '' && filter_var($registered_email, FILTER_VALIDATE_EMAIL)) {
  $to_email = $registered_email; // prioridad al registrado por el usuario
} elseif ($payer_email !== '' && filter_var($payer_email, FILTER_VALIDATE_EMAIL)) {
  $to_email = $payer_email; // fallback a PayPal
}

// =========================
// 3) Enviar email (opcional si hay $to_email)
// =========================
$send_note = '';  // Mensaje para mostrar en la vista (a quién se envió / si falló)
if ($to_email !== '') {
  try {
    require_once __DIR__ . '/vendor/autoload.php';      // PHPMailer
    $MAIL = require __DIR__ . '/config/mail.php';       // Config SMTP (Mailtrap)
    if (!is_array($MAIL)) { $MAIL = []; }

    $from_email = $MAIL['from_email'] ?? 'tienda@doncamaron.com';
    $from_name  = $MAIL['from_name']  ?? 'Don Camarón Online';

    $subject = 'Confirmación de pedido: ' . $folio;

    $htmlBody = '<!doctype html>
<html lang="es"><head><meta charset="utf-8"></head><body style="font-family:Arial,Helvetica,sans-serif;line-height:1.5;">
  <h2>¡Gracias por tu compra! 🦐</h2>
  <p>Hemos recibido tu pago correctamente.</p>
  <ul>
    <li><strong>Folio:</strong> ' . htmlspecialchars($folio, ENT_QUOTES, 'UTF-8') . '</li>
    ' . ($order_id ? '<li><strong>Order ID (PayPal):</strong> ' . htmlspecialchars($order_id, ENT_QUOTES, 'UTF-8') . '</li>' : '') . '
    ' . ($total ? '<li><strong>Total:</strong> ' . htmlspecialchars($total, ENT_QUOTES, 'UTF-8') . ' MXN</li>' : '') . '
    ' . ($status ? '<li><strong>Estado:</strong> ' . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . '</li>' : '') . '
  </ul>
  <p>Muestra este folio al recoger tu pedido. ¡Gracias por confiar en Don Camarón!</p>
</body></html>';

    $altBody = "¡Gracias por tu compra!\n" .
               "Folio: {$folio}\n" .
               ($order_id ? "Order ID (PayPal): {$order_id}\n" : '') . 
               ($total ? "Total: {$total} MXN\n" : '') .
               ($status ? "Estado: {$status}\n" : '') .
               "Muestra este folio al recoger tu pedido.";

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = $MAIL['host'] ?? 'sandbox.smtp.mailtrap.io';
    $mail->SMTPAuth   = true;
    $mail->Username   = $MAIL['username'] ?? '';
    $mail->Password   = $MAIL['password'] ?? '';
    $mail->Port       = (int)($MAIL['port'] ?? 2525);
    if (!empty($MAIL['encryption'])) { $mail->SMTPSecure = $MAIL['encryption']; } // STARTTLS opcional
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom($from_email, $from_name);
    $mail->addAddress($to_email);
    $mail->addReplyTo($from_email, $from_name);

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $htmlBody;
    $mail->AltBody = $altBody;

    $mail->send();
    $send_note = 'Se envió la confirmación a: <strong>' . htmlspecialchars($to_email, ENT_QUOTES, 'UTF-8') . '</strong>';
  } catch (Throwable $e) {
    $send_note = 'No se pudo enviar el correo de confirmación: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
  }
} else {
  // No hay ningún correo válido disponible
  $send_note = 'No se pudo enviar confirmación: no hay correo válido (ni registrado ni de PayPal).';
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>¡Gracias por tu compra!</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .folio { font-size: clamp(28px, 5vw, 42px); font-weight: 800; letter-spacing: 1px; }
    .card  { border-radius: 14px; }
  </style>
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-12 col-md-8 col-lg-6">
        <div class="card shadow-sm">
          <div class="card-body p-4 text-center">
            <h1 class="h3 fw-bold">¡Gracias por tu compra! 🦐</h1>
            <p class="text-muted mb-4">Tu pago fue procesado correctamente.</p>

            <div class="alert alert-success">
              <div>Tu <strong>folio de retiro</strong> es:</div>
              <div class="folio mt-1"><?php echo htmlspecialchars($folio, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>

            <?php if ($order_id): ?>
              <p class="mb-1"><strong>Order ID (PayPal):</strong> <?php echo htmlspecialchars($order_id, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
            <?php if ($total): ?>
              <p class="mb-1"><strong>Total:</strong> <?php echo htmlspecialchars($total, ENT_QUOTES, 'UTF-8'); ?> MXN</p>
            <?php endif; ?>

            <div class="mt-3">
              <div class="alert alert-info py-2 mb-0">
                <?php echo $send_note; ?>
              </div>
            </div>

            <div class="d-grid gap-2 d-sm-flex justify-content-sm-center mt-4">
              <button class="btn btn-outline-secondary" onclick="window.print()">Imprimir</button>
              <a class="btn btn-primary" href="index.php">Seguir comprando</a>
            </div>

            <p class="text-muted small mt-3 mb-0">
              Muestra este folio al recoger tu pedido. Si tienes dudas, contáctanos por WhatsApp.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
