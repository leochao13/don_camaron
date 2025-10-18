<!-- validacion de usuario -->
  <?php
      		include("/xampp/htdocs/php/polices.php");
      ?>
<?php
require 'config/config.php';
require 'config/database.php';

$db = new Database();
$con = $db->conectar();

$num_cart = isset($num_cart) ? (int)$num_cart : (int)($_SESSION['num_cart'] ?? 0);

$productos = $_SESSION['carrito']['producto'] ?? null;
$lista_carrito = [];

if ($productos) {
  foreach ($productos as $clave => $cantidad) {
    $sql = $con->prepare("SELECT id, nombre, precio, descuento, :cant AS cantidad
                          FROM producto
                          WHERE id = :id AND activo = true");
    $sql->bindValue(':cant', (int)$cantidad, PDO::PARAM_INT);
    $sql->bindValue(':id', (int)$clave, PDO::PARAM_INT);
    $sql->execute();
    $lista_carrito[] = $sql->fetch(PDO::FETCH_ASSOC);
  }
} else {
  header("Location: index.php");
  exit;
}

$total = 0.00;
foreach ($lista_carrito as $p) {
  $precio_desc = (float)$p['precio'] - (((float)$p['precio'] * (float)$p['descuento']) / 100.0);
  $subtotal = ((int)$p['cantidad']) * $precio_desc;
  $total += $subtotal;
}

$total_str = number_format($total, 2, '.', '');

// Asegurar constantes mínimas
if (!defined('CURRENCY')) define('CURRENCY', 'MXN');
if (!defined('CLIENT_ID')) define('CLIENT_ID', '');

// Prefill si ya guardaste el correo antes
$cliente_email = ''; // no prefill del correo

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Pago - Don Camarón</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css"
        rel="stylesheet" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <style>
    header.topbar { background-color: #0d47a1; color: #fff; }
    header.topbar .logo-header { height: 40px; width: auto; }
    header.topbar .cart-icon { font-size: 1.75rem; line-height:1; color:#fff; }
    header.topbar .btn-cart { border:1px solid rgba(255,255,255,.7); color:#fff; }
    header.topbar .btn-cart:hover { background: rgba(255,255,255,.12); }

    .section-bar { background:#fff; }
    .section-list .nav-link { font-weight:600; color:#143a4a; white-space:nowrap; }
    .section-list .nav-link:hover { color:#0d6efd; }

    .table thead th { background:#f4f6f8; border-bottom:0; }
    .table td, .table th { vertical-align: middle; }

    .pay-box { border:1px solid #e7ecf2; border-radius:12px; padding:16px; background:#fff; }
    .alert-mini { font-size: .95rem; }

    .email-box { border:1px solid #e7ecf2; border-radius:12px; background:#fff; padding:16px; }
    .form-hint { font-size:.9rem; color:#6b7e88; }
  </style>
</head>
<body>

<header class="topbar">
  <div class="container py-3">
    <div class="row g-3 align-items-center">
      <div class="col-12 col-md-3 text-center text-md-start">
        <a href="index.php" class="navbar-brand d-inline-flex align-items-center gap-2 text-decoration-none">
          <img src="images/logo.png" alt="Don Camarón" class="logo-header">
          <span class="fw-bold h4 text-white m-0">Don Camarón Online</span>
        </a>
      </div>
      <div class="col-12 col-md-6"></div>
     <!--  <div class="col-12 col-md-3 text-center text-md-end">
        <a href="captura.php" class="btn position-relative btn-cart">
          <i class="bi bi-bag-fill cart-icon"></i>
          <span class="ms-2 fw-bold">Carrito</span>
          <span id="num_cart" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
            <?php echo (int)$num_cart; ?>
          </span>
        </a>
      </div> -->
    </div>
  </div>
</header>

<nav class="section-bar border-bottom">
  <div class="container">
    <ul class="nav justify-content-between flex-nowrap overflow-auto py-2 gap-3 section-list">
      <li class="nav-item"><a class="nav-link" href="index.php?cat=camaron"><i class="bi bi-droplet-half me-2"></i>Camarones</a></li>
      <li class="nav-item"><a class="nav-link" href="index.php?cat=pescado"><i class="bi bi-fish me-2"></i>Pescados</a></li>
      <li class="nav-item"><a class="nav-link" href="index.php?cat=pulpo"><i class="bi bi-emoji-smile me-2"></i>Pulpos y Calamares</a></li>
      <li class="nav-item"><a class="nav-link" href="index.php?cat=premium"><i class="bi bi-gem me-2"></i>Mariscos Premium</a></li>
      <li class="nav-item"><a class="nav-link" href="index.php?cat=conchas"><i class="bi bi-circle me-2"></i>Conchas y Moluscos</a></li>
      <li class="nav-item"><a class="nav-link" href="index.php?cat=ahumados"><i class="bi bi-fire me-2"></i>Ahumados</a></li>
    </ul>
  </div>
</nav>

<main class="py-4">
  <div class="container">
    <div class="row g-4">
      <div class="col-12">
        <div class="text-center">
          <h3 class="fw-bold">Pagar pedido</h3>
          <p class="text-muted mb-0">Revisa tu orden y completa tu pago con PayPal.</p>
        </div>
      </div>

      <div class="col-12 col-lg-7">
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>Producto</th>
                <th class="text-center">Cant.</th>
                <th class="text-end">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($lista_carrito as $producto):
                $_id       = (int)$producto['id'];
                $nombre    = htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8');
                $precio    = (float)$producto['precio'];
                $descuento = (float)$producto['descuento'];
                $cantidad  = (int)$producto['cantidad'];
                $precio_desc = $precio - (($precio * $descuento) / 100.0);
                $subtotal  = $cantidad * $precio_desc;
              ?>
              <tr>
                <td><?php echo $nombre; ?></td>
                <td class="text-center"><?php echo $cantidad; ?></td>
                <td class="text-end"><?php echo MONEDA . number_format($subtotal, 2, '.', ','); ?></td>
              </tr>
              <?php endforeach; ?>
              <tr>
                <td colspan="2" class="text-end"><strong>Total</strong></td>
                <td class="text-end"><strong id="total"><?php echo MONEDA . number_format($total, 2, '.', ','); ?></strong></td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="email-box mt-2">
          <form id="formEmail" class="row g-2 align-items-center">
            <div class="col-12 col-md-8">
              <label for="emailCliente" class="form-label mb-1">Correo para tu recibo</label>
              <input type="email"
       class="form-control"
       id="emailCliente"
       name="email"
       placeholder="tucorreo@ejemplo.com"
       autocomplete="off"
       autocapitalize="none"
       spellcheck="false"
       required>

              <div class="form-hint mt-1">Usaremos tu correo para enviarte el comprobante y seguimiento del pedido.</div>
            </div>
            <div class="col-12 col-md-4 d-grid">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-envelope-check me-1"></i> Guardar correo
              </button>
            </div>
          </form>
          <div id="emailMsg" class="mt-2"></div>
        </div>

      </div>

      <div class="col-12 col-lg-5">
        <div class="pay-box">
          <div class="mb-3">
            <div class="alert alert-info alert-mini">
              <i class="bi bi-shield-lock me-1"></i>
              Pagos procesados de forma segura por PayPal.
            </div>
          </div>

          <div id="paypal-button-container"></div>

          <div class="mt-3 text-center">
            <a href="captura.php" class="btn btn-outline-secondary btn-sm">Regresar al carrito</a>
          </div>
        </div>
      </div>

    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

<!-- SDK de PayPal -->
<script src="https://www.paypal.com/sdk/js?client-id=<?php echo urlencode(CLIENT_ID); ?>&currency=<?php echo urlencode(CURRENCY); ?>&components=buttons&intent=capture" data-sdk-integration-source="button-factory"></script>

<script>
(function(){

  function extractPayPalError(err){
    try {
      if (typeof err === 'string') return err;
      if (err?.message) return err.message;
      if (err?.data?.details?.length) {
        return err.data.details.map(d => (d.issue || 'ISSUE') + ': ' + (d.description || '')).join(' | ');
      }
      return JSON.stringify(err).slice(0, 500);
    } catch(e) {
      return 'Error desconocido';
    }
  }

  const totalServidor = "<?php echo $total_str ?? '0.00'; ?>";
  if (!/^\d+(\.\d{1,2})?$/.test(totalServidor) || parseFloat(totalServidor) <= 0) {
    console.error("Total inválido para PayPal:", totalServidor);
    alert("El total del pedido es inválido o 0. Corrige el carrito.");
    return;
  }

  if (typeof paypal === 'undefined' || !paypal.Buttons) {
    console.error("El SDK de PayPal no se cargó (revisa client-id y red).");
    alert("No se pudo cargar PayPal. Verifica tu client-id y conexión.");
    return;
  }

  paypal.Buttons({
    style:{ color:'blue', shape:'pill', label:'pay', layout:'vertical' },

    createOrder: function(data, actions){
      return actions.order.create({
        intent: 'CAPTURE',
        purchase_units: [{
          amount: {
            currency_code: "<?php echo addslashes(CURRENCY); ?>",
            value: totalServidor
          },
          description: "Compra en Don Camarón Online"
        }],
        application_context: {
          shipping_preference: 'NO_SHIPPING'
        }
      }).catch(function(err){
        const msg = extractPayPalError(err);
        console.error('createOrder error:', err);
        alert('Error al crear orden en PayPal: ' + msg);
        throw err;
      });
    },

    onApprove: function(data, actions){
      return actions.order.capture().then(function(detalles){
        if (!detalles || detalles.status !== 'COMPLETED') {
          console.error('Captura no completada:', detalles);
          alert('Pago no completado en PayPal: ' + (detalles?.status || 'desconocido'));
          return;
        }

        return fetch('clases/captura_api.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify({ detalles: detalles })
        })
        .then(async (r) => {
          const text = await r.text();
          let res;
          try { res = JSON.parse(text); }
          catch(parseErr){ throw new Error("Respuesta no JSON de captura_api.php: " + text.slice(0, 300)); }
          if (!r.ok || !res.ok) { throw new Error(res?.msg || 'Fallo al registrar el pago.'); }
          return res;
        })
        .then(function(res){
          const folio = encodeURIComponent(res.pickup_code || '');
          const oid   = encodeURIComponent(res.order_id || '');
          if (folio) {
            window.location.href = "gracias.php?folio=" + folio + "&order_id=" + oid;
          } else {
            alert("Pago exitoso. No se recibió folio, te llevamos al inicio.");
            window.location.href = "index.php";
          }
        })
        .catch(function(err){
          console.error('Post-capture error:', err);
          alert('Tu pago se capturó en PayPal, pero ocurrió un problema registrándolo en el sitio: ' + err.message);
        });
      }).catch(function(err){
        const msg = extractPayPalError(err);
        console.error('capture error:', err);
        alert('Error al capturar el pago en PayPal: ' + msg);
        throw err;
      });
    },

    onCancel: function(data){
      alert("Pago cancelado.");
      console.log("PayPal cancel:", data);
    },

    onError: function(err){
      const msg = extractPayPalError(err);
      console.error("PayPal error:", err);
      alert("Ocurrió un error con PayPal: " + msg);
    }

  }).render('#paypal-button-container');

})();
</script>

<!-- ====== JS para registrar el correo por AJAX ====== -->
<script>
(function(){
  const formEmail = document.getElementById('formEmail');
  const emailInput = document.getElementById('emailCliente');
  const emailMsg = document.getElementById('emailMsg');

  function showMsg(ok, msg){
    if (!emailMsg) return;
    emailMsg.innerHTML = '<div class="alert '+(ok?'alert-success':'alert-danger')+' py-2 mb-0">'+msg+'</div>';
    setTimeout(()=>{ emailMsg.innerHTML=''; }, 4000);
  }

  if (formEmail) {
    formEmail.addEventListener('submit', function(ev){
      ev.preventDefault();
      const email = (emailInput.value || '').trim();

      if (!email) { showMsg(false, 'Ingresa un correo.'); return; }
      const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!re.test(email)) { showMsg(false, 'Correo no válido.'); return; }

      fetch('clases/registrar_email.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: email })
      })
      .then(r => r.ok ? r.json() : { ok:false, msg:'Error de red' })
      .then(res => {
        if (!res.ok) throw new Error(res.msg || 'No se pudo registrar el correo');
        showMsg(true, '¡Correo guardado!');
      })
      .catch(err => showMsg(false, err.message));
    });
  }
})();
</script>

</body>
</html>
