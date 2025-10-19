<!-- validacion de usuario -->
<?php include("C:/xampp/htdocs/php/polices.php"); ?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel Admin | Gráficas</title>

  <!-- Estilos globales -->
  <link rel="stylesheet" href="/components/admin/admin-estilo.css">
  <link rel="icon" href="/icon.png" type="image/x-icon">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

  <!-- ✅ Boot rápido de accesibilidad/tema (aplica ANTES de pintar) -->
  <script>
  (function(){
    var r=document.documentElement;
    try{
      // --- Tema: forzar valor por defecto CLARO si no existe
      var dark = localStorage.getItem('ac_dark');
      if (dark === null || dark === undefined) {
        localStorage.setItem('ac_dark','0'); // claro por defecto
        dark = '0';
      }
      var isDark = dark === '1';
      r.classList.toggle('theme-dark', isDark);
      r.classList.toggle('theme-light', !isDark);
      r.classList.toggle('modo-nocturno', isDark); // compatibilidad
      r.style.setProperty('--ac-invert', isDark ? '1' : '0');

      // --- Contraste / fuente / grises / guía / tipografías
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
    }catch(e){}
  })();
  </script>

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

  <style>
    /* Usa variables de tema en lugar de colores fijos oscuros */
    .container {
      margin-left: 240px;
      max-width: calc(100% - 260px);
      padding: 24px 16px;
    }
    @media (max-width: 991px){
      .container{ margin-left:16px; max-width: calc(100% - 32px); }
    }

    .card {
      background: var(--card-bg);
      border: 1px solid var(--card-border);
      border-radius: 16px;
      box-shadow: var(--shadow);
    }
    .card-header {
      padding: 12px 16px;
      border-bottom: 1px solid var(--card-border);
      display:flex; align-items:center; justify-content:space-between;
      color: var(--fg);
    }
    .card-body { padding: 16px; color: var(--fg); }
    .muted { color: var(--muted-fg); }

    .grid { display:grid; gap:16px; grid-template-columns: 1fr; }
    .grid-2 { display:grid; gap:16px; grid-template-columns: 1fr 1fr; }
    @media (max-width: 1100px){ .grid-2{ grid-template-columns: 1fr; } }

    .toolbar { display:flex; flex-wrap:wrap; gap:.6rem; align-items:end; }
    .toolbar .field { display:flex; flex-direction:column; gap:.35rem; }
    .toolbar input, .toolbar select {
      background: var(--card-bg);
      color: var(--fg);
      border: 1px solid var(--card-border);
      border-radius: 10px;
      padding: .55rem .7rem; min-width: 160px;
    }

    .btn {
      display:inline-flex; align-items:center; gap:.45rem;
      border-radius:12px; border:1px solid transparent;
      padding:.6rem .85rem; cursor:pointer; font-weight:600;
      color: var(--fg); background: var(--card-bg); border-color: var(--card-border);
    }
    .btn i { font-size:1.1rem; }
    .btn-primary {
      background:#25b7a1; border-color:#25b7a1; color:#0d1514;
    }
    .btn-outline {
      background:transparent; border-color: var(--card-border); color: var(--muted-fg);
    }
    .btn:hover { filter:brightness(.95); }

    canvas { width: 100%; height: 340px; }
  </style>
</head>

<!-- ❌ Quitamos class="darkmode" -->
<body>
  <!-- Navbar -->
  <div id="navbar-container"></div>

  <div class="container">
    <div class="d-flex align-items-center justify-content-between" style="margin-bottom:12px;">
      <h1 class="h5 m-0"><i class='bx bx-pie-chart-alt'></i> Gráficas</h1>
      <span class="muted">Visual • Datos </span>
    </div>

    <!-- Toolbar (visual) -->
    <div class="card" style="margin-bottom:16px;">
      <div class="card-body">
        <div class="toolbar">
          <div class="field">
            <label class="muted">Rango:</label>
            <select id="range">
              <option value="7">Últimos 7 días</option>
              <option value="14">Últimos 14 días</option>
              <option value="30" selected>Últimos 30 días</option>
            </select>
          </div>
          <div class="field">
            <label class="muted">Semilla de datos:</label>
            <input type="number" id="seed" placeholder="(opcional)" min="0" step="1">
          </div>
          <button class="btn btn-outline" id="btn-random"><i class='bx bx-dice-5'></i> Aleatorio</button>
          <button class="btn btn-primary" id="btn-apply"><i class='bx bx-refresh'></i> Actualizar</button>
        </div>
      </div>
    </div>

    <!-- Línea: Ventas vs Pedidos -->
    <div class="grid">
      <div class="card">
        <div class="card-header">
          <div class="d-flex align-items-center gap-2">
            <i class='bx bx-line-chart'></i>
            <strong>Ventas (MXN) vs. Pedidos</strong>
          </div>
        </div>
        <div class="card-body">
          <canvas id="lineChart"></canvas>
        </div>
      </div>
    </div>

    <!-- Barras + Dona -->
    <div class="grid-2" style="margin-top:16px;">
      <div class="card">
        <div class="card-header">
          <div class="d-flex align-items-center gap-2">
            <i class='bx bx-bar-chart-alt-2'></i>
            <strong>Ventas por categoría</strong>
          </div>
        </div>
        <div class="card-body">
          <canvas id="barChart"></canvas>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <div class="d-flex align-items-center gap-2">
            <i class='bx bx-doughnut-chart'></i>
            <strong>Métodos de pago</strong>
          </div>
        </div>
        <div class="card-body">
          <canvas id="doughnutChart"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts comunes -->
  <script src="/js/main-navbar-admin.js" defer></script>
  <script src="/js/accesibilidad-state.js" defer></script>

  <!-- Lógica de gráficas (datos simulados + respeto a preferencias) -->
  <script>
    // --------- Utilidades ----------
    const $ = (s)=>document.querySelector(s);

    // Colores que respetan tema actual (usa CSS vars aplicadas)
    function chartColors(){
      const cs = getComputedStyle(document.documentElement);
      return {
        text: cs.getPropertyValue('--fg').trim() || getComputedStyle(document.body).color || '#111',
        grid: 'rgba(148,163,184,0.12)',
        line1: '#4cc9f0',
        line2: '#f72585',
        bars: ['#22c55e','#ef4444','#eab308','#3b82f6','#a855f7','#14b8a6','#f97316'],
        doughnut: ['#22c55e','#3b82f6','#eab308','#f97316','#a855f7']
      };
    }

    // PRNG simple para reproducibilidad con semilla
    function makeRng(seed){
      let s = Number(seed)||Date.now();
      return function(){
        s = (s * 1664525 + 1013904223) % 4294967296;
        return s / 4294967296;
      };
    }

    function genSerieDays(n, rng, base=100, spread=60, min=0){
      const arr = [];
      for (let i=0; i<n; i++){
        const val = Math.max(min, Math.round(base + (rng()-0.5)*spread*2));
        arr.push(val);
      }
      return arr;
    }
    function labelsDays(n){
      const out = [];
      const d = new Date();
      for (let i=n-1; i>=0; i--){
        const x = new Date(d); x.setDate(x.getDate()-i);
        out.push(x.toISOString().slice(0,10));
      }
      return out;
    }

    // --------- Estado local ----------
    let line, bar, doughnut;

    function buildCharts(){
      // limpiar instancias previas
      [line, bar, doughnut].forEach(ch => { try{ ch?.destroy(); }catch(e){} });

      const n    = Number($('#range').value || 30);
      const seed = $('#seed').value || undefined;
      const rng  = makeRng(seed);
      const c    = chartColors();

      const labels   = labelsDays(n);
      const pedidos  = genSerieDays(n, rng, 40, 25, 0);
      const ventas   = genSerieDays(n, rng, 1200, 700, 100);
      const cats     = ['Camarones','Pescados','Pulpos & Calamares','Conchas','Ahumados','Premium','Bebidas'];
      const catVals  = cats.map(()=> Math.max(0, Math.round((rng()+0.2)*1000)));
      const pagosLab = ['Tarjeta','PayPal','Efectivo','Transferencia','Vales'];
      const pagosVal = pagosLab.map(()=> Math.max(0, Math.round((rng()+0.3)*100)));

      // Línea
      line = new Chart($('#lineChart'), {
        type: 'line',
        data: {
          labels,
          datasets: [
            { label:'Pedidos', data: pedidos, tension:0.25, borderWidth:2, pointRadius:2, borderColor:c.line1 },
            { label:'Ventas (MXN)', data: ventas, tension:0.25, borderWidth:2, pointRadius:2, borderColor:c.line2, yAxisID:'y2' }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio:false,
          interaction: { mode:'index', intersect:false },
          plugins: { legend: { labels: { color:c.text } } },
          scales: {
            x: { ticks: { color:c.text }, grid:{ color:c.grid } },
            y: { ticks: { color:c.text }, grid:{ color:c.grid }, title:{ display:true, text:'Pedidos', color:c.text } },
            y2:{ position:'right', ticks:{ color:c.text }, grid:{ drawOnChartArea:false }, title:{ display:true, text:'Ventas (MXN)', color:c.text } }
          }
        }
      });

      // Barras
      bar = new Chart($('#barChart'), {
        type: 'bar',
        data: {
          labels: cats,
          datasets: [{ label:'Ventas por categoría', data: catVals, backgroundColor:c.bars }]
        },
        options: {
          responsive:true,
          maintainAspectRatio:false,
          plugins: { legend: { labels: { color:c.text } } },
          scales: {
            x: { ticks:{ color:c.text }, grid:{ color:c.grid } },
            y: { ticks:{ color:c.text }, grid:{ color:c.grid } }
          }
        }
      });

      // Dona
      doughnut = new Chart($('#doughnutChart'), {
        type: 'doughnut',
        data: {
          labels: pagosLab,
          datasets: [{ data: pagosVal, backgroundColor: c.doughnut }]
        },
        options: {
          responsive:true,
          maintainAspectRatio:false,
          plugins: {
            legend: { position:'bottom', labels:{ color:c.text } },
            tooltip:{ enabled:true }
          },
          cutout:'58%'
        }
      });
    }

    // Eventos UI
    $('#btn-apply').addEventListener('click', buildCharts);
    $('#btn-random').addEventListener('click', ()=>{
      $('#seed').value = Math.floor(Math.random()*1e9);
      buildCharts();
    });

    // Re-render si cambian preferencias (por ejemplo, modo oscuro)
    window.addEventListener('storage', (e)=>{
      if (!e.key) return;
      if (e.key.startsWith('ac_')) {
        setTimeout(buildCharts, 30);
      }
    });

    // Primer render
    document.addEventListener('DOMContentLoaded', buildCharts);
  </script>

  <!-- ✅ Booster TTS (lee main si ac_tts = 'auto') -->
  <script>
    (function(){
      const TTS_KEY = 'ac_tts';
      window.AccessibilityTTS = window.AccessibilityTTS || {};
      window.AccessibilityTTS.speakPage = function(){
        try{
          speechSynthesis.cancel();
          const mainEl = document.querySelector('.container, .content, main') || document.body;
          const txt = (mainEl.innerText || '').trim();
          if(!txt) return;
          const u = new SpeechSynthesisUtterance(txt);
          u.lang = 'es-MX';
          speechSynthesis.speak(u);
        }catch(e){}
      };
      window.AccessibilityTTS.stop = function(){ try{ speechSynthesis.cancel(); }catch(e){} };

      document.addEventListener('DOMContentLoaded', function(){
        if (localStorage.getItem(TTS_KEY) === 'auto') {
          window.AccessibilityTTS.speakPage();
          const once = () => { window.AccessibilityTTS.speakPage(); document.removeEventListener('pointerdown', once); };
          document.addEventListener('pointerdown', once, { once:true });
        }
      });
    })();
  </script>
</body>
</html>
