<?php
// config.php
// ================== Sesión ==================
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

// ================== Tienda ==================
define('MONEDA',   '$');      // símbolo para mostrar precios
define('CURRENCY', 'MXN');    // moneda para PayPal (Orders API)

// Token HMAC para links (detalles/agregar)
if (!defined('KEY_TOKEN')) {
  define('KEY_TOKEN', 'clave_dev'); // cámbialo en producción
}

// ================== PayPal ==================
// Entorno: 'sandbox' o 'live'
define('PAYPAL_ENV', 'Doncamaron');

// Tus credenciales Sandbox (tal cual las compartiste)
define('CLIENT_ID', 'AcjuWdDM5_0yxPG9n8nOMlI6Po3NCh6jdOB4bi8KXbv-M5CPbMTKEN5doRnX6rESnGaySJyG6ZgAqdby');
define('PAYPAL_SECRET', 'ECf0osFi1fK4b_yztUgjUBqKUIsHuv26Yi1XY9_TJEKixhQROIPz4IlXuZa29uRUL_QHVOC0ZDZC7w-0');

// Base URL de API según entorno (úsala en clases/captura.php)
define('PAYPAL_BASE', PAYPAL_ENV === 'live'
  ? 'https://api-m.paypal.com'
  : 'https://api-m.sandbox.paypal.com'
);

// ================== Badge del carrito ==================
$num_cart = 0;
if (isset($_SESSION['carrito']['producto']) && is_array($_SESSION['carrito']['producto'])) {
  $num_cart = count($_SESSION['carrito']['producto']);  // productos distintos
}

// (Opcional) Si prefieres unidades totales en el badge, usa esto:
// $num_cart = 0;
// if (!empty($_SESSION['carrito']['producto']) && is_array($_SESSION['carrito']['producto'])) {
//   foreach ($_SESSION['carrito']['producto'] as $c) { $num_cart += (int)$c; }
// }
