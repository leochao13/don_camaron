<?php
// -----------------------------
// Don Camarón | Admin Dashboard
// -----------------------------

// 1) Seguridad / sesión
include("C:/xampp/htdocs/php/polices.php");

// 2) Config y DB
require_once __DIR__ . "/../../components/usuario/menu/config/config.php";
require_once __DIR__ . "/../../components/usuario/menu/config/database.php";

// 3) Conexión PDO
$db  = new Database();
$con = $db->conectar();

$NDIAS  = 30;
$hoy    = new DateTime('today');
$inicio = (clone $hoy)->modify('-' . ($NDIAS - 1) . ' days');

$driver = $con->getAttribute(PDO::ATTR_DRIVER_NAME);

if ($driver === 'pgsql') {
  $sql = "
    SELECT to_char(creado_en::date, 'YYYY-MM-DD') AS dia,
           COUNT(*)::int AS pedidos,
           COALESCE(SUM(total), 0)::numeric AS ventas
    FROM pedidos
    WHERE creado_en >= :start
    GROUP BY dia
    ORDER BY dia;
  ";
} else {
  $sql = "
    SELECT DATE(creado_en) AS dia,
           COUNT(*) AS pedidos,
           COALESCE(SUM(total), 0) AS ventas
    FROM pedidos
    WHERE creado_en >= :start
    GROUP BY DATE(creado_en)
    ORDER BY DATE(creado_en);
  ";
}

$stmt = $con->prepare($sql);
$stmt->bindValue(':start', $inicio->format('Y-m-d') . ' 00:00:00', PDO::PARAM_STR);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$map = [];
foreach ($rows as $r) {
  $map[$r['dia']] = [
    'pedidos' => (int)$r['pedidos'],
    'ventas'  => (float)$r['ventas'],
  ];
}

$labels = [];
$pedidosData = [];
$ventasData  = [];

$period = new DatePeriod($inicio, new DateInterval('P1D'), (clone $hoy)->modify('+1 day'));
foreach ($period as $d) {
  $k = $d->format('Y-m-d');
  $labels[]      = $k;
  $pedidosData[] = $map[$k]['pedidos'] ?? 0;
  $ventasData[]  = $map[$k]['ventas'] ?? 0.0;
}

$totalPedidos = array_sum($pedidosData);
$totalVentas  = array_sum($ventasData);
$ticketProm   = $totalPedidos > 0 ? $totalVentas / $totalPedidos : 0.0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Don Camarón | Panel Admin: Inicio</title>

  <!-- Estilos globales del admin (incluye variables de accesibilidad/tema) -->
  <link rel="stylesheet" href="/components/admin/admin-estilo.css">
  <link rel="icon" href="/icon.png" type="image/x-icon">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

  <!-- ✅ Boot accesibilidad/tema (aplica ANTES de pintar + TTS auto si seeded) -->
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
      r.classList.toggle('modo-nocturno', isDark); // compatibilidad con estilos antiguos
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

      // TTS auto (si ya hubo gesto en Ajustes)
      (function(){
        const want = localStorage.getItem('ac_tts')==='auto';
        let seeded=false; try{ seeded=sessionStorage.getItem('ac_tts_seeded')==='1'; }catch(_){}
        if(!want || !seeded) return;
        function collect(){
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
          document.addEventListener('DOMContentLoaded', ()=>speakNow(collect()), {once:true});
        }else{
          speakNow(collect());
        }
      })();
    }catch(e){}
  })();
  </script>

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

  <style>
    /* =========================
       Usar variables del tema
       ========================= */
    .container {
      max-width: 1100px; margin: 24px auto; padding: 0 16px;
      color: var(--fg); background: var(--bg);
    }
    .grid { display:grid; gap:16px; }
    .grid-cols-3 { grid-template-columns: repeat(3, 1fr); }

    .card {
      background: var(--card-bg);
      border: 1px solid var(--card-border);
      border-radius: 16px;
      padding: 16px;
      box-shadow: var(--shadow);
      color: var(--fg);
    }
    .kpi { display:flex; align-items:center; gap:12px; }
    .kpi .icon { font-size:28px; }
    .kpi .title { font-size:12px; color: var(--muted-fg); text-transform:uppercase; letter-spacing:.08em; }
    .kpi .value { font-weight:700; font-size:22px; }
    .muted { color: var(--muted-fg); font-size:12px; }

    .chart-wrap { padding:12px; }
    .header { display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; color: var(--fg); }
    .header h2 { margin:0; font-size:18px; }
    .badge {
      background: var(--card-bg);
      border:1px solid var(--card-border);
      border-radius:999px; padding:4px 10px; font-size:12px; color: var(--muted-fg);
    }
  </style>
</head>

<!-- ❌ sin class="darkmode" -->
<body>
  <!-- Navbar -->
  <div id="navbar-container"></div>

  <div class="container">
    <div class="header">
      <h2>Resumen de los últimos <?= (int)$NDIAS ?> días</h2>
      <span class="badge"><?= htmlspecialchars($inicio->format('Y-m-d')) ?> → <?= htmlspecialchars($hoy->format('Y-m-d')) ?></span>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-3">
      <div class="card">
        <div class="kpi">
          <i class='bx bx-cart icon'></i>
          <div>
            <div class="title">Pedidos</div>
            <div class="value"><?= number_format($totalPedidos) ?></div>
            <div class="muted">Total acumulado</div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="kpi">
          <i class='bx bx-dollar-circle icon'></i>
          <div>
            <div class="title">Ventas (MXN)</div>
            <div class="value"><?= number_format($totalVentas, 2) ?></div>
            <div class="muted">Suma de totales</div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="kpi">
          <i class='bx bx-trending-up icon'></i>
          <div>
            <div class="title">Ticket Promedio</div>
            <div class="value"><?= number_format($ticketProm, 2) ?></div>
            <div class="muted">Ventas / Pedido</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Gráfica -->
    <div class="card chart-wrap" style="margin-top:16px;">
      <div class="header">
        <h2><i class='bx bx-line-chart'></i> Pedidos vs Ventas por día</h2>
      </div>
      <canvas id="pedidosVentasChart" height="120"></canvas>
    </div>
  </div>

  <!-- Inicialización de la gráfica (colores desde CSS para respetar tema) -->
  <script>
    (function(){
      const labels      = <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>;
      const pedidosData = <?= json_encode($pedidosData, JSON_UNESCAPED_UNICODE) ?>;
      const ventasData  = <?= json_encode($ventasData,  JSON_UNESCAPED_UNICODE) ?>;

      function chartColors(){
        const cs = getComputedStyle(document.documentElement);
        return {
          text: cs.getPropertyValue('--fg')?.trim() || '#111827',
          muted: cs.getPropertyValue('--muted-fg')?.trim() || '#6b7280',
          grid: 'rgba(148,163,184,0.12)',
          line1: '#3b82f6', // azul
          line2: '#ef4444', // rojo
        };
      }

      const c = chartColors();
      new Chart(document.getElementById('pedidosVentasChart'), {
        type: 'line',
        data: {
          labels,
          datasets: [
            { label: 'Pedidos',      data: pedidosData, tension:0.25, borderWidth:2, pointRadius:2, borderColor:c.line1, yAxisID:'y1' },
            { label: 'Ventas (MXN)', data: ventasData,  tension:0.25, borderWidth:2, pointRadius:2, borderColor:c.line2, yAxisID:'y2' }
          ]
        },
        options: {
          responsive: true,
          interaction: { mode: 'index', intersect: false },
          plugins: { legend: { labels: { color: c.text } } },
          scales: {
            x:  { ticks: { color: c.muted }, grid:{ color: c.grid } },
            y1: { type:'linear', position:'left',  ticks:{ color: c.muted }, grid:{ color: c.grid }, title:{ display:true, text:'Pedidos', color: c.muted } },
            y2: { type:'linear', position:'right', ticks:{ color: c.muted }, grid:{ drawOnChartArea:false }, title:{ display:true, text:'Ventas (MXN)', color: c.muted } }
          }
        }
      });

      // Si el usuario cambia preferencias de tema en otra pestaña, reestilamos rápido
      window.addEventListener('storage', (e)=>{
        if (!e.key) return;
        if (e.key.startsWith('ac_')) {
          setTimeout(()=>location.reload(), 30);
        }
      });
    })();
  </script>

  <!-- Scripts comunes -->
  <script src="/js/main-navbar-admin.js" defer></script>
  <script src="/js/accesibilidad-state.js" defer></script>
</body>
</html>
