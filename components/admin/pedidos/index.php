<!-- validacion de usuario -->
<?php include("C:/xampp/htdocs/php/polices.php"); ?>

<?php
// --- Conexión a BD (ruta según tu estructura /components/usuario/menu/config/) ---
require_once __DIR__ . '/../../usuario/menu/config/config.php';
require_once __DIR__ . '/../../usuario/menu/config/database.php';

$errMsg = '';
$listado = [];
$moneda = defined('MONEDA') ? MONEDA : '$';

// Filtros opcionales
$q      = isset($_GET['q']) ? trim($_GET['q']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$lim    = isset($_GET['lim']) ? max(1, min(200, (int)$_GET['lim'])) : 50;

try {
  $con = (new Database())->conectar();
  $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // Construcción dinámica del WHERE
  $where = [];
  $args  = [];

  if ($q !== '') {
    if (preg_match('/^DC-\d{6}-[A-Z0-9]+$/i', $q)) {
      $where[] = "folio = :folio";
      $args[':folio'] = preg_replace('/[^A-Z0-9\-]/i', '', $q);
    } else {
      $where[] = "(LOWER(email) LIKE :likeq OR LOWER(COALESCE(paypal_order_id,'')) LIKE :likeq)";
      $args[':likeq'] = '%'.mb_strtolower($q,'UTF-8').'%';
    }
  }

  if ($status !== '') {
    $where[] = "LOWER(status) = :status";
    $args[':status'] = mb_strtolower($status,'UTF-8');
  }

  $sql = "
    SELECT id, folio, total, email, status, creado_en
    FROM pedidos
  ";
  if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
  }
  $sql .= " ORDER BY id DESC LIMIT :lim";

  $stmt = $con->prepare($sql);
  foreach ($args as $k => $v) {
    $stmt->bindValue($k, $v, PDO::PARAM_STR);
  }
  $stmt->bindValue(':lim', (int)$lim, PDO::PARAM_INT);
  $stmt->execute();
  $listado = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $errMsg = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel Admin | Pedidos</title>

  <!-- Estilos -->
  <link rel="stylesheet" href="/components/admin/admin-estilo.css">
  <link rel="icon" href="/icon.png" type="image/x-icon">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- ✅ Boot de tema/accesibilidad ANTES de pintar -->
  <script>
  (function(){
    var r=document.documentElement;
    try{
      // Tema por defecto: CLARO
      var dark = localStorage.getItem('ac_dark');
      if (dark === null || dark === undefined) {
        localStorage.setItem('ac_dark','0');
        dark = '0';
      }
      var isDark = dark === '1';
      r.classList.toggle('theme-dark', isDark);
      r.classList.toggle('theme-light', !isDark);
      r.classList.toggle('modo-nocturno', isDark); // compatibilidad
      r.style.setProperty('--ac-invert', isDark ? '1' : '0');

      // Accesibilidad
      var c=parseFloat(localStorage.getItem('ac_contrast_val'));
      if(isNaN(c)) c = (localStorage.getItem('ac_contrast')==='1') ? 1.6 : 1;
      var f=parseFloat(localStorage.getItem('ac_font_scale'));
      if(isNaN(f)) f = (localStorage.getItem('ac_font')==='1') ? 1.35 : 1;
      r.style.setProperty('--ac-contrast', String(Math.min(2,Math.max(0.5,c))));
      r.style.setProperty('--ac-font-scale', String(Math.min(1.6,Math.max(0.9,f))));
      r.style.setProperty('--ac-gray', localStorage.getItem('ac_gray')==='1' ? '1' : '0');
      if(localStorage.getItem('ac_ruler')==='1'){ r.classList.add('guia-lectura'); }
      if(localStorage.getItem('ac_fontface')==='1'){ r.classList.add('tipografia-alt'); }
      if(localStorage.getItem('ac_fontface2')==='1'){ r.classList.add('tipografia-alt2'); }

      // TTS auto (si ya hubo gesto)
      (function(){
        const want = localStorage.getItem('ac_tts')==='auto';
        let seeded = false;
        try { seeded = sessionStorage.getItem('ac_tts_seeded')==='1'; } catch(_){}
        if(!want || !seeded) return;

        function collectText(){
          const q=s=>document.querySelector(s);
          const parts=[];
          parts.push(q('.admin-sidebar, .main-menu, nav[role="navigation"]')?.innerText||'');
          parts.push(q('main, .container, .content')?.innerText||'');
          const t=parts.filter(Boolean).join('\n\n').trim();
          return t || (document.body.innerText||'').trim();
        }
        function speakNow(t){
          try{
            if(!('speechSynthesis' in window)) return;
            speechSynthesis.cancel();
            const u=new SpeechSynthesisUtterance(t);
            u.lang='es-MX';
            speechSynthesis.speak(u);
          }catch(_){}
        }
        if(document.readyState==='loading'){
          document.addEventListener('DOMContentLoaded', ()=>speakNow(collectText()), {once:true});
        }else{
          speakNow(collectText());
        }
      })();
    }catch(e){}
  })();
  </script>

  <style>
    /* =====================
       Usar variables de tema
       ===================== */
    .container {
      margin-left: 240px;
      max-width: calc(100% - 260px);
    }
    @media (max-width: 991px) {
      .container { margin-left: 16px; max-width: calc(100% - 32px); }
    }

    .card {
      background: var(--card-bg);
      border:1px solid var(--card-border);
      border-radius:16px;
      box-shadow: var(--shadow);
      color: var(--fg);
    }
    .muted { color: var(--muted-fg); }
    .badge-soft {
      background:#223049; color:#cfe7ff; border:1px solid #2e3c55;
    }

    /* Toolbar */
    .toolbar .form-control,
    .toolbar .form-select {
      background: var(--card-bg);
      color: var(--fg);
      border:1px solid var(--card-border);
    }

    /* Tabla con variables (evitar .table-dark fija) */
    .table thead th {
      background: var(--card-bg);
      color: var(--fg);
      border-bottom:1px solid var(--card-border);
    }
    .table tbody td {
      color: var(--fg);
      border-color: var(--card-border);
    }
  </style>
</head>

<!-- ❌ quitar class="darkmode" -->
<body>
  <div id="navbar-container"></div>

  <div class="container py-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
          <h1 class="h5 m-0"><i class='bx bx-receipt'></i> Pedidos</h1>
          <span class="badge badge-soft">Mostrando <?= htmlspecialchars((string)$lim) ?> resultados</span>
        </div>

        <!-- Toolbar de búsqueda -->
        <form class="row g-2 align-items-end toolbar mb-3" method="get">
          <div class="col-sm-5">
            <label class="form-label muted">Buscar</label>
            <input type="text" class="form-control" name="q"
                   placeholder="Folio DC-..., email o Order ID"
                   value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-sm-3">
            <label class="form-label muted">Status</label>
            <select class="form-select" name="status">
              <option value="">Todos</option>
              <?php
                $statuses = ['COMPLETED','PENDING','CANCELLED','VOID','FAILED','CREATED'];
                foreach ($statuses as $st) {
                  $sel = (strcasecmp($status,$st)===0) ? 'selected' : '';
                  echo "<option value=\"{$st}\" {$sel}>{$st}</option>";
                }
              ?>
            </select>
          </div>
          <div class="col-sm-2">
            <label class="form-label muted">Límite</label>
            <select class="form-select" name="lim">
              <?php foreach ([20,50,100,200] as $opt): ?>
                <option value="<?= $opt ?>" <?= $lim===$opt?'selected':''; ?>><?= $opt ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-sm-2 d-grid">
            <button class="btn btn-primary"><i class='bx bx-search'></i> Filtrar</button>
          </div>
        </form>

        <?php if ($errMsg): ?>
          <div class="alert alert-danger">
            <strong>Error:</strong> <?= htmlspecialchars($errMsg, ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>

        <?php if ($listado): ?>
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead>
                <tr>
                  <th style="width:70px;">ID</th>
                  <th style="min-width:160px;">Folio</th>
                  <th style="min-width:120px;">Total</th>
                  <th>Email</th>
                  <th style="min-width:120px;">Status</th>
                  <th style="min-width:160px;">Fecha</th>
                  <th style="width:120px;"></th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($listado as $row): ?>
                <tr>
                  <td><?= (int)$row['id'] ?></td>
                  <td class="text-break">
                    <span class="badge badge-soft"><?= htmlspecialchars($row['folio'], ENT_QUOTES, 'UTF-8') ?></span>
                  </td>
                  <td><?= $moneda . number_format((float)$row['total'], 2) ?></td>
                  <td class="text-break"><?= htmlspecialchars($row['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                  <td>
                    <?php
                      $st = strtoupper((string)($row['status'] ?? ''));
                      $cls = 'secondary';
                      if ($st==='COMPLETED') $cls='success';
                      elseif ($st==='PENDING' || $st==='CREATED') $cls='warning';
                      elseif (in_array($st,['FAILED','CANCELLED','VOID'])) $cls='danger';
                    ?>
                    <span class="badge bg-<?= $cls ?>"><?= htmlspecialchars($st, ENT_QUOTES, 'UTF-8') ?></span>
                  </td>
                  <td><?= htmlspecialchars($row['creado_en'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="text-end">
                    <a class="btn btn-outline-primary btn-sm"
                       href="/components/usuario/menu/ver_pedido.php?modo=folio&q=<?= urlencode($row['folio']) ?>">
                      <i class='bx bx-show'></i> Ver
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="alert alert-info mb-0">No hay pedidos que coincidan con el filtro.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Scripts comunes -->
  <script src="/js/main-navbar-admin.js" defer></script>
  <script src="/js/accesibilidad-state.js" defer></script>

  <!-- Bootstrap -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
