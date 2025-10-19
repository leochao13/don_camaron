<!-- validacion de usuario -->
<?php include("C:/xampp/htdocs/php/polices.php"); ?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel Admin | Reservaciones</title>

  <!-- Estilos globales -->
  <link rel="stylesheet" href="/components/admin/admin-estilo.css">
  <link rel="icon" href="/icon.png" type="image/x-icon">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

  <!-- ✅ Boot rápido de accesibilidad/tema (aplica ANTES de pintar) -->
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
    }catch(e){}
  })();
  </script>

  <style>
    /* ===========================
       Variables de tema (las define tu CSS global),
       aquí SOLO las usamos.
       =========================== */

    /* Layout */
    .container {
      margin-left: 240px;
      max-width: calc(100% - 260px);
      padding: 24px 16px;
      color: var(--fg);
      background: var(--bg);
    }
    @media (max-width: 991px){
      .container { margin-left: 16px; max-width: calc(100% - 32px); }
    }

    /* Cards / bloques */
    .card {
      background: var(--card-bg);
      border: 1px solid var(--card-border);
      border-radius: 16px;
      box-shadow: var(--shadow);
    }
    .card-header {
      padding: 12px 16px;
      border-bottom: 1px solid var(--card-border);
      display: flex; align-items: center; justify-content: space-between;
      color: var(--fg);
    }
    .card-body { padding: 16px; color: var(--fg); }
    .muted { color: var(--muted-fg); }

    /* Toolbar / controles */
    .toolbar { display:flex; flex-wrap:wrap; gap:.6rem; align-items:end; }
    .toolbar .field { display:flex; flex-direction:column; gap:.35rem; }
    .toolbar input, .toolbar select {
      background: var(--card-bg);
      color: var(--fg);
      border: 1px solid var(--card-border);
      border-radius: 10px;
      padding: .55rem .7rem; min-width: 190px;
    }

    /* Botones */
    .btn {
      display:inline-flex; align-items:center; gap:.45rem;
      border-radius:12px; border:1px solid transparent;
      padding:.6rem .85rem; cursor:pointer; font-weight:600;
      color: var(--fg); background: var(--card-bg); border-color: var(--card-border);
    }
    .btn i { font-size:1.1rem; }
    .btn-primary { background:#25b7a1; border-color:#25b7a1; color:#0d1514; }
    .btn-outline { background:transparent; border-color: var(--card-border); color: var(--muted-fg); }
    .btn-danger { background:#d9534f; border-color:#d9534f; color:#fff; }
    .btn:hover { filter:brightness(.95); }

    /* Grid principal */
    .grid { display:grid; gap:16px; grid-template-columns: 1.2fr .8fr; }
    @media (max-width: 1100px){ .grid{ grid-template-columns: 1fr; } }

    /* Formulario */
    .form-grid { display:grid; gap:12px; grid-template-columns: repeat(2, 1fr); }
    .form-grid .full { grid-column: 1 / -1; }
    .form-grid input, .form-grid select, .form-grid textarea {
      width:100%;
      background: var(--card-bg);
      color: var(--fg);
      border:1px solid var(--card-border);
      border-radius:10px; padding:.6rem .75rem;
    }
    .form-grid label { font-size:.9rem; color: var(--muted-fg); }

    /* Calendario */
    .cal-wrap { display:flex; flex-direction:column; gap:10px; }
    .cal-header { display:flex; align-items:center; justify-content:space-between; }
    .cal-header .title { font-weight:700; }
    .cal-grid { display:grid; grid-template-columns: repeat(7, 1fr); gap:6px; }
    .cal-cell {
      background: var(--card-bg);
      border:1px solid var(--card-border);
      border-radius:10px; min-height:74px; padding:6px; position:relative;
    }
    .cal-cell .day { font-size:.85rem; color: var(--muted-fg); }
    .cal-cell .dot { width:8px; height:8px; border-radius:999px; background:#25b7a1; position:absolute; bottom:8px; right:8px; }
    .cal-legend { display:flex; align-items:center; gap:.6rem; color: var(--muted-fg); font-size:.9rem; }
    .cal-legend span { display:inline-flex; align-items:center; gap:.35rem; }
    .legend-dot { width:10px; height:10px; border-radius:999px; background:#25b7a1; display:inline-block; }

    /* Tabla */
    .table-wrap { overflow:auto; }
    table { width:100%; border-collapse:collapse; color: var(--fg); }
    thead th {
      text-align:left; font-size:.85rem; color: var(--muted-fg);
      border-bottom:1px solid var(--card-border); padding:10px; white-space:nowrap;
    }
    tbody td { border-bottom:1px solid var(--card-border); padding:10px; vertical-align:top; }
    .status { font-weight:700; font-size:.78rem; padding:.25rem .5rem; border-radius:999px; display:inline-block; }
    .st-pend { background:#2d3142; color:#c2cff1; }
    .st-conf { background:#2b6651; color:#b9ffe5; }
    .st-canc { background:#663b3b; color:#ffd6d6; }
    .actions { display:flex; gap:.4rem; justify-content:flex-end; }

    .badge-soft {
      background: #223049; color:#cfe7ff;
      border:1px solid #2e3c55; border-radius:999px; padding:.25rem .55rem; font-size:.76rem;
    }
  </style>
</head>

<!-- ❌ quitamos class="darkmode" -->
<body>
  <!-- Navbar -->
  <div id="navbar-container"></div>

  <div class="container">
    <div class="d-flex align-items-center justify-content-between" style="margin-bottom:12px;">
      <h1 class="h5 m-0"><i class='bx bx-calendar'></i> Reservaciones</h1>
      <span class="badge-soft" id="res-count">0 reservaciones</span>
    </div>

    <!-- Toolbar de filtros (visual) -->
    <div class="card" style="margin-bottom:16px;">
      <div class="card-body">
        <div class="toolbar">
          <div class="field">
            <label class="muted">Buscar</label>
            <input type="text" id="f-buscar" placeholder="Nombre, teléfono o nota…">
          </div>
          <div class="field">
            <label class="muted">Fecha</label>
            <input type="date" id="f-fecha">
          </div>
          <div class="field">
            <label class="muted">Estatus</label>
            <select id="f-status">
              <option value="">Todos</option>
              <option value="pendiente">Pendiente</option>
              <option value="confirmada">Confirmada</option>
              <option value="cancelada">Cancelada</option>
            </select>
          </div>
          <button class="btn btn-outline" id="f-limpiar"><i class='bx bx-reset'></i> Limpiar</button>
          <button class="btn btn-primary" id="btn-nueva"><i class='bx bx-plus'></i> Nueva</button>
          <button class="btn btn-outline" id="btn-exportar"><i class='bx bx-download'></i> Exportar CSV</button>
        </div>
      </div>
    </div>

    <!-- Grid: Formulario + Calendario -->
    <div class="grid">
      <!-- Formulario -->
      <div class="card">
        <div class="card-header">
          <div class="title">Crear / Editar reservación</div>
          <small class="muted" id="form-mode">Modo: nueva</small>
        </div>
        <div class="card-body">
          <form id="res-form">
            <input type="hidden" id="res-id">
            <div class="form-grid">
              <div>
                <label>Nombre</label>
                <input type="text" id="res-nombre" required>
              </div>
              <div>
                <label>Teléfono</label>
                <input type="tel" id="res-telefono" placeholder="10 dígitos">
              </div>
              <div>
                <label>Personas</label>
                <input type="number" id="res-personas" min="1" value="2" required>
              </div>
              <div>
                <label>Fecha</label>
                <input type="date" id="res-fecha" required>
              </div>
              <div>
                <label>Hora</label>
                <input type="time" id="res-hora" required>
              </div>
              <div>
                <label>Estatus</label>
                <select id="res-status">
                  <option value="pendiente">Pendiente</option>
                  <option value="confirmada">Confirmada</option>
                  <option value="cancelada">Cancelada</option>
                </select>
              </div>
              <div class="full">
                <label>Notas</label>
                <textarea id="res-notas" rows="3" placeholder="Ej. Mesa cerca de la ventana, silla para bebé…"></textarea>
              </div>
            </div>
            <div style="display:flex; gap:.6rem; margin-top:12px;">
              <button class="btn btn-primary" type="submit"><i class='bx bx-save'></i> Guardar</button>
              <button class="btn btn-outline" type="button" id="res-cancelar"><i class='bx bx-x'></i> Cancelar</button>
              <button class="btn btn-danger" type="button" id="res-eliminar" style="margin-left:auto; display:none;">
                <i class='bx bx-trash'></i> Eliminar
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Calendario -->
      <div class="card">
        <div class="card-header">
          <div class="cal-header">
            <button class="btn btn-outline" id="cal-prev"><i class='bx bx-chevron-left'></i></button>
            <div class="title" id="cal-title">Mes Año</div>
            <button class="btn btn-outline" id="cal-next"><i class='bx bx-chevron-right'></i></button>
          </div>
        </div>
        <div class="card-body">
          <div class="cal-wrap">
            <div class="cal-grid" id="cal-weekdays"></div>
            <div class="cal-grid" id="cal-days"></div>
            <div class="cal-legend">
              <span><span class="legend-dot"></span> Día con reservaciones</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabla -->
    <div class="card" style="margin-top:16px;">
      <div class="card-header">
        <div class="title">Listado</div>
        <small class="muted">Solo visual (no BD)</small>
      </div>
      <div class="card-body table-wrap">
        <table id="tbl-res">
          <thead>
            <tr>
              <th>ID</th>
              <th>Fecha</th>
              <th>Hora</th>
              <th>Nombre</th>
              <th>Personas</th>
              <th>Teléfono</th>
              <th>Estatus</th>
              <th>Notas</th>
              <th style="text-align:right;">Acciones</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Scripts comunes -->
  <script src="/js/main-navbar-admin.js"></script>
  <script src="/js/accesibilidad-state.js" defer></script>

  <!-- Lógica visual (sin BD) -->
  <script>
    // ---------- Mock ----------
    const mock = [
      {id:1, nombre:'Juan Pérez',  telefono:'5551234567', personas:3, fecha:isoDate(0),  hora:'14:00', status:'pendiente',  notas:'Aniversario'},
      {id:2, nombre:'Ana López',   telefono:'5559876543', personas:2, fecha:isoDate(1),  hora:'19:30', status:'confirmada', notas:'Ventana'},
      {id:3, nombre:'Luis García', telefono:'5551112222', personas:6, fecha:isoDate(3),  hora:'20:00', status:'pendiente',  notas:''},
      {id:4, nombre:'María Ruiz',  telefono:'5553334444', personas:4, fecha:isoDate(10), hora:'13:30', status:'cancelada',  notas:'Mesa redonda'}
    ];
    let RES = [...mock];
    let sequence = RES.length ? Math.max(...RES.map(r=>r.id))+1 : 1;

    // ---------- Utils ----------
    function isoDate(offsetDays){
      const d = new Date(); d.setDate(d.getDate()+offsetDays);
      const y=d.getFullYear(), m=String(d.getMonth()+1).padStart(2,'0'), dd=String(d.getDate()).padStart(2,'0');
      return `${y}-${m}-${dd}`;
    }
    const $ = sel => document.querySelector(sel);
    const $$ = sel => Array.from(document.querySelectorAll(sel));
    function fmtPhone(t){ return t?.replace(/\D+/g,'').replace(/(\d{3})(\d{3})(\d{4})/,'($1) $2-$3') || ''; }
    function text(el, v){ if(el) el.textContent = v; }
    function escapeHtml(s){ return (s??'').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }

    function byFilters(r){
      const q = $('#f-buscar').value.trim().toLowerCase();
      const f = $('#f-fecha').value;
      const s = $('#f-status').value;
      if(q && !(`${r.nombre} ${r.telefono} ${r.notas}`.toLowerCase().includes(q))) return false;
      if(f && r.fecha !== f) return false;
      if(s && r.status !== s) return false;
      return true;
    }

    // ---------- Tabla ----------
    function statusPill(st){
      const map = {pendiente:'st-pend', confirmada:'st-conf', cancelada:'st-canc'};
      return `<span class="status ${map[st]||'st-pend'}">${st.toUpperCase()}</span>`;
    }
    function renderTable(){
      const tb = $('#tbl-res tbody'); tb.innerHTML = '';
      const rows = RES.filter(byFilters).sort((a,b)=> (a.fecha+a.hora).localeCompare(b.fecha+b.hora));
      rows.forEach(r=>{
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${r.id}</td>
          <td>${r.fecha}</td>
          <td>${r.hora}</td>
          <td>${escapeHtml(r.nombre)}</td>
          <td>${r.personas}</td>
          <td>${fmtPhone(r.telefono)}</td>
          <td>${statusPill(r.status)}</td>
          <td>${escapeHtml(r.notas)}</td>
          <td class="actions">
            <button class="btn btn-outline" data-act="edit" data-id="${r.id}"><i class='bx bx-edit-alt'></i> Editar</button>
            <button class="btn btn-danger"  data-act="del"  data-id="${r.id}"><i class='bx bx-trash'></i></button>
          </td>`;
        tb.appendChild(tr);
      });
      text($('#res-count'), `${rows.length} reservaciones`);
    }

    // ---------- Form ----------
    function resetForm(mode='nueva'){
      $('#res-id').value='';
      $('#res-nombre').value='';
      $('#res-telefono').value='';
      $('#res-personas').value=2;
      $('#res-fecha').value='';
      $('#res-hora').value='';
      $('#res-status').value='pendiente';
      $('#res-notas').value='';
      $('#res-eliminar').style.display = 'none';
      text($('#form-mode'), `Modo: ${mode}`);
    }
    function fillForm(r){
      $('#res-id').value=r.id;
      $('#res-nombre').value=r.nombre;
      $('#res-telefono').value=r.telefono;
      $('#res-personas').value=r.personas;
      $('#res-fecha').value=r.fecha;
      $('#res-hora').value=r.hora;
      $('#res-status').value=r.status;
      $('#res-notas').value=r.notas;
      $('#res-eliminar').style.display = 'inline-flex';
      text($('#form-mode'), `Modo: editar #${r.id}`);
    }

    $('#res-form').addEventListener('submit', (e)=>{
      e.preventDefault();
      const data = {
        id: $('#res-id').value ? Number($('#res-id').value) : sequence++,
        nombre: ($('#res-nombre').value||'').trim(),
        telefono: ($('#res-telefono').value||'').trim(),
        personas: Math.max(1, Number($('#res-personas').value)||1),
        fecha: $('#res-fecha').value,
        hora: $('#res-hora').value,
        status: $('#res-status').value,
        notas: ($('#res-notas').value||'').trim()
      };
      if(!data.nombre || !data.fecha || !data.hora) {
        alert('Nombre, fecha y hora son obligatorios.');
        return;
      }
      const idx = RES.findIndex(x=>x.id===data.id);
      if(idx>=0) RES[idx] = data; else RES.push(data);
      renderTable(); renderCalendar(); resetForm('nueva');
    });
    $('#res-cancelar').addEventListener('click', ()=> resetForm('nueva'));
    $('#res-eliminar').addEventListener('click', ()=>{
      const id = Number($('#res-id').value||0);
      if(!id) return;
      if(!confirm('¿Eliminar la reservación #' + id + '?')) return;
      RES = RES.filter(r=>r.id!==id);
      renderTable(); renderCalendar(); resetForm('nueva');
    });

    $('#tbl-res').addEventListener('click', (e)=>{
      const btn = e.target.closest('button[data-act]');
      if(!btn) return;
      const id = Number(btn.dataset.id);
      const r  = RES.find(x=>x.id===id);
      if(!r) return;
      if(btn.dataset.act==='edit') fillForm(r);
      if(btn.dataset.act==='del') {
        if(!confirm('¿Eliminar la reservación #' + id + '?')) return;
        RES = RES.filter(x=>x.id!==id);
        renderTable(); renderCalendar(); resetForm('nueva');
      }
    });

    // ---------- Filtros ----------
    $('#f-buscar').addEventListener('input', ()=> renderTable());
    $('#f-fecha').addEventListener('change', ()=> renderTable());
    $('#f-status').addEventListener('change', ()=> renderTable());
    $('#f-limpiar').addEventListener('click', ()=>{
      $('#f-buscar').value=''; $('#f-fecha').value=''; $('#f-status').value='';
      renderTable();
    });

    // ---------- Calendario ----------
    const weekNames = ['L','M','X','J','V','S','D'];
    const monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    let calCurrent = new Date();

    function renderWeekdays(){
      const w = $('#cal-weekdays'); w.innerHTML = '';
      weekNames.forEach(ch=>{
        const c = document.createElement('div');
        c.className='muted'; c.style.textAlign='center'; c.textContent = ch;
        w.appendChild(c);
      });
    }
    function renderCalendar(){
      const y = calCurrent.getFullYear();
      const m = calCurrent.getMonth();
      text($('#cal-title'), `${monthNames[m]} ${y}`);

      const first = new Date(y, m, 1);
      const last  = new Date(y, m+1, 0);
      const startOffset = ( (first.getDay()+6) % 7 );
      const totalCells = startOffset + last.getDate();
      const grid = $('#cal-days');
      grid.innerHTML = '';

      const marks = new Set(RES.filter(byFilters).map(r=>r.fecha));

      for(let i=0; i<totalCells; i++){
        const cell = document.createElement('div');
        cell.className='cal-cell';
        if(i>=startOffset){
          const day = i - startOffset + 1;
          const dstr = `${y}-${String(m+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
          const dayEl = document.createElement('div');
          dayEl.className='day'; dayEl.textContent = day;
          cell.appendChild(dayEl);

          if(marks.has(dstr)){
            const dot = document.createElement('span');
            dot.className='dot';
            cell.appendChild(dot);
          }

          cell.style.cursor='pointer';
          cell.addEventListener('click', ()=>{
            $('#f-fecha').value = dstr;
            renderTable();
            $('#res-fecha').value = dstr;
            $('#res-hora').focus();
          });
        }
        grid.appendChild(cell);
      }
    }
    $('#cal-prev').addEventListener('click', ()=>{ calCurrent.setMonth(calCurrent.getMonth()-1); renderCalendar(); });
    $('#cal-next').addEventListener('click', ()=>{ calCurrent.setMonth(calCurrent.getMonth()+1); renderCalendar(); });

    // Botón nueva
    $('#btn-nueva').addEventListener('click', ()=>{
      resetForm('nueva');
      $('#res-nombre').focus();
    });

    // Exportar CSV
    $('#btn-exportar').addEventListener('click', ()=>{
      const rows = [['ID','Fecha','Hora','Nombre','Personas','Teléfono','Estatus','Notas']];
      RES.filter(byFilters).forEach(r=>{
        rows.push([r.id, r.fecha, r.hora, r.nombre, r.personas, r.telefono, r.status, r.notas]);
      });
      const csv = rows.map(r=>r.map(v=>`"${String(v??'').replace(/"/g,'""')}"`).join(',')).join('\n');
      const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = 'reservaciones.csv';
      document.body.appendChild(a);
      a.click();
      a.remove();
    });

    // ---------- Init ----------
    (function init(){
      renderWeekdays();
      renderCalendar();
      renderTable();
      resetForm('nueva');
    })();
  </script>

  <!-- ✅ Booster TTS -->
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
