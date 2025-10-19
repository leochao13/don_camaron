<!-- validacion de usuario -->
<?php include("C:/xampp/htdocs/php/polices.php"); ?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel Admin | Menú</title>

  <!-- Estilos -->
  <link rel="stylesheet" href="/components/admin/admin-estilo.css">
  <link rel="stylesheet" href="/components/admin/menu/estilo-menu.css">
  <link rel="icon" href="/icon.png" type="image/x-icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
    }catch(e){}

    // TTS auto si ya hubo gesto en Ajustes
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
  })();
  </script>

  <style>
    /* =========================
       Usar variables del tema
       ========================= */

    /* Empuja el contenido a la derecha del sidebar */
    .container {
      margin-left: 240px;
      max-width: calc(100% - 260px);
      color: var(--fg);
      background: var(--bg);
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

    .form-control, .form-select, textarea { 
      background: var(--card-bg); 
      color: var(--fg); 
      border:1px solid var(--card-border); 
    }
    .form-control::placeholder { color: var(--muted-fg); }

    .badge-soft { 
      background:#223049; color:#cfe7ff; border:1px solid #2e3c55; 
    }

    .table thead th { 
      background: var(--card-bg);
      color: var(--fg);
      border-bottom:1px solid var(--card-border); 
    }
    .table tbody td { 
      color: var(--fg);
      border-color: var(--card-border); 
    }

    /* Botones */
    .btn-primary{ background:#25b7a1; border-color:#25b7a1; color:#0d1514; }
    .btn-outline-secondary{ color: var(--fg); border-color: var(--card-border); }
    .btn-outline-secondary:hover{ background: var(--card-bg); }

    /* Vista previa de imagen */
    #imagen-preview{
      display:none; max-width:220px; margin-top:10px; border-radius:8px; border:1px solid var(--card-border);
    }
  </style>
</head>

<!-- ❌ sin class="darkmode" -->
<body>
  <!-- Navbar -->
  <div id="navbar-container"></div>

  <div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h1 class="h5 m-0"><i class='bx bx-food-menu'></i> Control del menú</h1>
    </div>

    <!-- Formulario Crear/Actualizar -->
    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <form id="item-form" enctype="multipart/form-data">
          <input type="hidden" id="item-id" name="id">
          <input type="hidden" id="old_image" name="old_image">

          <div class="row g-3">
            <div class="col-md-4">
              <label for="categoria" class="form-label">Categoría</label>
              <select id="categoria" name="categoria" class="form-select" required>
                <option value="camarones">Camarones</option>
                <option value="pescados">Pescados</option>
                <option value="pulpos_calamares">Pulpos & Calamares</option>
                <option value="conchas">Conchas</option>
                <option value="ahumados">Ahumados</option>
                <option value="premium">Premium</option>
              </select>
            </div>

            <div class="col-md-4">
              <label for="name" class="form-label">Nombre</label>
              <input type="text" id="name" name="nombre" class="form-control" required>
            </div>

            <div class="col-md-2">
              <label for="precio" class="form-label">Precio</label>
              <input type="number" id="precio" name="precio" step="0.01" min="0" class="form-control" required>
            </div>

            <div class="col-md-2">
              <label for="descuento" class="form-label">Descuento (%)</label>
              <input type="number" id="descuento" name="descuento" step="0.01" min="0" max="100" value="0" class="form-control">
            </div>

            <div class="col-12">
              <label for="descripcion" class="form-label">Descripción</label>
              <textarea id="descripcion" name="descripcion" rows="3" class="form-control" required></textarea>
            </div>

            <div class="col-md-6">
              <label for="imagen" class="form-label">Imagen</label>
              <input type="file" id="imagen" name="imagen" accept="image/*" class="form-control">
              <img id="imagen-preview" src="#" alt="Vista previa">
            </div>

            <div class="col-md-3">
              <label for="stock" class="form-label">Stock</label>
              <input type="number" id="stock" name="stock" min="0" value="0" class="form-control" required>
            </div>

            <div class="col-md-3">
              <label for="activo" class="form-label">Activo</label>
              <select id="activo" name="activo" class="form-select">
                <option value="1" selected>Sí</option>
                <option value="0">No</option>
              </select>
            </div>

            <div class="col-12 d-flex gap-2 mt-2">
              <button type="submit" id="submit-button" class="btn btn-primary"><i class='bx bx-plus'></i> Agregar Producto</button>
              <button type="button" id="cancel-button" class="btn btn-outline-secondary" style="display:none;">Cancelar</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Tabla de productos -->
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="table-responsive">
          <table id="products-table" class="table table-sm align-middle">
            <thead>
              <tr>
                <th>ID</th>
                <th>Imagen</th>
                <th>Nombre</th>
                <th>Precio</th>
                <th>Descuento</th>
                <th>Descripción</th>
                <th>Stock</th>
                <th>Activo</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <!-- Se llena desde PHP -->
              <?php include __DIR__ . '/get_products.php'; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts (comunes en defer) -->
  <script src="/js/main-navbar-admin.js" defer></script>
  <script src="/js/accesibilidad-state.js" defer></script>

  <!-- Lógica de la página de menú + Bootstrap -->
  <script src="/components/admin/menu/main-menu.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Vista previa de imagen (no interfiere con tu main-menu.js) -->
  <script>
    (function(){
      const input = document.getElementById('imagen');
      const prev  = document.getElementById('imagen-preview');
      if(!input || !prev) return;
      input.addEventListener('change', (e)=>{
        const f = e.target.files && e.target.files[0];
        if(!f){ prev.style.display='none'; prev.src='#'; return; }
        const url = URL.createObjectURL(f);
        prev.src = url;
        prev.style.display = 'block';
      });
    })();
  </script>
</body>
</html>
