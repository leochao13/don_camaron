<?php
// components/admin/usuarios/usuarios.php

// --- validación de sesión/permiso si aplica ---
@include_once("C:/xampp/htdocs/php/polices.php");

// --- Config / DB (según tu estructura actual) ---
require_once __DIR__ . '/../../usuario/menu/config/config.php';
require_once __DIR__ . '/../../usuario/menu/config/database.php';

ini_set('display_errors', '1');
error_reporting(E_ALL);

try {
  $db  = new Database();
  $con = $db->conectar();
} catch (Throwable $e) {
  http_response_code(500);
  echo "<pre style='color:#b00'>Error de conexión: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</pre>";
  exit;
}

// --- Filtros (buscar y rol) ---
$busca = trim($_GET['q'] ?? '');
$rolFilter = trim($_GET['rol'] ?? '');

// --- Traer usuarios ---
$usuarios = [];
$err = '';

try {
  // PostgreSQL: alias nulo para evitar columnas inexistentes (foto/avatar)
  $sql = "SELECT id, nombre, correo, rol, fecha_registro,
                 NULL::text AS foto
          FROM usuarios";
  $where = [];
  $params = [];

  // En PG, ILIKE = case-insensitive. Si usas MySQL, cambia por LIKE.
  if ($busca !== '') {
    $where[] = "(nombre ILIKE :q OR correo ILIKE :q)";
    $params[':q'] = "%{$busca}%";
  }
  if ($rolFilter !== '' && in_array($rolFilter, ['admin','mesero','cliente'], true)) {
    $where[] = "rol = :rol";
    $params[':rol'] = $rolFilter;
  }
  if ($where) $sql .= " WHERE " . implode(" AND ", $where);
  $sql .= " ORDER BY id DESC";

  $stmt = $con->prepare($sql);
  foreach ($params as $k=>$v) $stmt->bindValue($k, $v, PDO::PARAM_STR);
  $stmt->execute();
  $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $err = $e->getMessage();
}

// --- Util: resolver URL de avatar (carpeta real: C:\xampp\htdocs\image\Usuarios) ---
function avatar_url(array $u): string {
  $baseUrl = '/image/Usuarios/'; // ruta web
  $absBase = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\') . $baseUrl;

  // 1) Si viniera ruta en BD (compatibilidad)
  if (!empty($u['foto'])) {
    $fn = (string)$u['foto'];
    $fn = ltrim(str_replace(['..','\\'], ['','/'], $fn), '/');
    if (function_exists('str_starts_with')) {
      if (str_starts_with($fn, 'image/Usuarios/')) return '/' . $fn;
    } else {
      if (substr($fn, 0, 14) === 'image/Usuarios/') return '/' . $fn;
    }
    return $baseUrl . basename($fn);
  }

  // 2) Probar por ID: id.webp|jpg|jpeg|png
  $id = (int)($u['id'] ?? 0);
  foreach (["{$id}.webp", "{$id}.jpg", "{$id}.jpeg", "{$id}.png"] as $f) {
    if (is_file($absBase . $f)) return $baseUrl . $f;
  }

  // 3) Default
  return $baseUrl . 'default.png';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel Admin | Usuarios</title>

  <link rel="stylesheet" href="/components/admin/admin-estilo.css">
  <link rel="stylesheet" href="/components/admin/usuarios/estilo-usuarios.css">
  <link rel="icon" href="/icon.png" type="image/x-icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

  <!-- ✅ Boot tema/accesibilidad ANTES de pintar + TTS auto -->
  <script>
  (function(){
    var r=document.documentElement;
    try{
      // Tema: por defecto CLARO
      var dark = localStorage.getItem('ac_dark');
      if (dark === null || dark === undefined) {
        localStorage.setItem('ac_dark','0');
        dark = '0';
      }
      var isDark = dark === '1';
      r.classList.toggle('theme-dark', isDark);
      r.classList.toggle('theme-light', !isDark);
      r.classList.toggle('modo-nocturno', isDark); // compat con estilos antiguos
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
    /* ====== usar variables del tema global ====== */

    .container { margin-left: 240px; max-width: calc(100% - 260px); }
    @media (max-width: 991px){ .container{ margin-left: 16px; max-width: calc(100% - 32px); } }

    .card{
      background: var(--card-bg);
      border: 1px solid var(--card-border);
      border-radius: 16px;
      box-shadow: var(--shadow);
      color: var(--fg);
    }

    .form-control,.form-select,textarea{
      background: var(--card-bg);
      color: var(--fg);
      border: 1px solid var(--card-border);
    }
    .form-control::placeholder{ color: var(--muted-fg); }

    .btn-primary{ background:#25b7a1; border-color:#25b7a1; color:#0d1514; }
    .btn-outline-primary{ border-color:#25b7a1; color:#25b7a1; }
    .btn-outline-primary:hover{ background:#25b7a1; color:#0d1514; }

    .table thead th{
      background: var(--card-bg);
      color: var(--fg);
      border-bottom: 1px solid var(--card-border);
    }
    .table tbody td{
      color: var(--fg);
      border-color: var(--card-border);
    }
    .table-hover tbody tr:hover{ background: var(--muted-bg); }

    .badge-role{ font-weight:700; letter-spacing:.3px; border-radius:999px; padding:.35rem .6rem; font-size:.72rem; }
    .badge-role.admin{ background:#2b3e66; color:#b9d4ff; }
    .badge-role.mesero{ background:#2b6651; color:#b9ffe5; }
    .badge-role.cliente{ background:#5a3b3b; color:#ffd6d6; }

    .avatar{ width:42px; height:42px; border-radius:50%; object-fit:cover; border:1px solid var(--card-border); background: var(--card-bg); }
    .user-cell{ display:flex; align-items:center; gap:.75rem; }

    .pill-filter .btn{ border-radius:999px; padding:.25rem .7rem; font-size:.85rem; }

    .searchbar{
      display:flex; gap:.5rem; align-items:center;
      background: var(--card-bg);
      border:1px solid var(--card-border);
      border-radius:12px; padding:.25rem .6rem;
    }
    .searchbar input{ border:0; background:transparent; color: var(--fg); outline:0; }
    .searchbar .bx{ color: var(--muted-fg); font-size:1.2rem; }

    .text-muted, .muted { color: var(--muted-fg) !important; }
  </style>
</head>

<!-- ❌ sin class="darkmode" -->
<body>

  <!-- Navbar lateral -->
  <div id="navbar-container"></div>

  <div class="container py-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
      <h1 class="h5 m-0 d-flex align-items-center gap-2">
        <i class='bx bx-user'></i> Usuarios
      </h1>
      <div class="d-flex flex-wrap gap-2 align-items-center">
        <form class="d-flex align-items-center gap-2" method="get">
          <div class="searchbar">
            <i class='bx bx-search'></i>
            <input name="q" value="<?php echo htmlspecialchars($busca, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Buscar nombre o correo">
          </div>
          <div class="pill-filter btn-group" role="group" aria-label="Filtro rol">
            <?php
              $roles = [''=>'Todos','cliente'=>'Cliente','mesero'=>'Mesero','admin'=>'Admin'];
              foreach($roles as $val=>$label):
                $active = ($rolFilter === $val) ? 'active' : '';
            ?>
              <a class="btn btn-outline-primary <?php echo $active; ?>"
                 href="?<?php $qs = $_GET; $qs['rol']=$val; echo http_build_query($qs); ?>">
                 <?php echo $label; ?>
              </a>
            <?php endforeach; ?>
          </div>
          <a class="btn btn-outline-primary" href="?"><i class='bx bx-reset'></i></a>
        </form>

        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#usuarioModal" onclick="openCreate()">
          <i class='bx bx-plus'></i> Nuevo usuario
        </button>
      </div>
    </div>

    <?php if ($err): ?>
      <div class="alert alert-danger">Error: <?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm align-middle table-hover">
            <thead>
              <tr>
                <th style="width:60px;">ID</th>
                <th style="width:70px;">Avatar</th>
                <th>Nombre</th>
                <th>Correo</th>
                <th style="width:140px;">Rol</th>
                <th style="width:200px;">Registrado</th>
                <th style="width:170px;" class="text-end">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($usuarios): foreach ($usuarios as $u):
                $rol = (string)($u['rol'] ?? 'cliente');
                $rolClass = in_array($rol, ['admin','mesero','cliente'], true) ? $rol : 'cliente';
                $avatar = avatar_url($u);
              ?>
                <tr data-id="<?php echo (int)$u['id']; ?>">
                  <td class="text-muted"><?php echo (int)$u['id']; ?></td>
                  <td>
                    <img src="<?php echo htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8'); ?>"
                         alt="Avatar" class="avatar" loading="lazy"
                         onerror="this.src='/image/Usuarios/default.png'">
                  </td>
                  <td>
                    <div class="user-cell">
                      <div>
                        <div class="fw-semibold"><?php echo htmlspecialchars($u['nombre'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="small text-muted">#<?php echo (int)$u['id']; ?></div>
                      </div>
                    </div>
                  </td>
                  <td class="text-break"><?php echo htmlspecialchars($u['correo'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><span class="badge badge-role <?php echo $rolClass; ?>"><?php echo strtoupper($rolClass); ?></span></td>
                  <td class="text-muted"><?php echo htmlspecialchars((string)$u['fecha_registro'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td class="text-end">
                    <button class="btn btn-outline-primary btn-sm"
                      onclick='openEdit(<?php echo (int)$u["id"]; ?>, <?php echo json_encode($u, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>)'>
                      <i class="bx bx-edit-alt"></i> Editar
                    </button>
                    <button class="btn btn-outline-danger btn-sm" onclick="confirmDelete(<?php echo (int)$u['id']; ?>)">
                      <i class="bx bx-trash"></i>
                    </button>
                  </td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="7" class="text-center text-muted">No hay usuarios aún.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Crear/Editar -->
  <div class="modal fade" id="usuarioModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form class="modal-content" id="formUsuario" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title" id="modalTitle">Nuevo usuario</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="u_id">
          <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" class="form-control" name="nombre" id="u_nombre" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Correo</label>
            <input type="email" class="form-control" name="correo" id="u_correo" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Rol</label>
            <select class="form-select" name="rol" id="u_rol" required>
              <option value="admin">Admin</option>
              <option value="mesero">Mesero</option>
              <option value="cliente" selected>Cliente</option>
            </select>
          </div>

          <div class="row g-3 align-items-center">
            <div class="col-4 text-center">
              <img id="u_preview" class="avatar" src="/image/Usuarios/default.png" alt="Vista previa">
            </div>
            <div class="col-8">
              <label class="form-label">Foto de usuario</label>
              <input class="form-control" type="file" name="foto" id="u_foto" accept=".jpg,.jpeg,.png,.webp">
              <div class="form-text">Formatos: JPG/PNG/WebP, máx. ~2MB.</div>
            </div>
          </div>

          <hr class="my-3">

          <div class="mb-2">
            <label class="form-label">Contraseña</label>
            <input type="password" class="form-control" name="contrasena" id="u_pass" placeholder="Mínimo 6 caracteres">
            <div class="form-text" id="pass_help">Obligatoria al crear; opcional al editar.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancelar</button>
          <button class="btn btn-primary" type="submit">Guardar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Scripts comunes -->
  <script src="/js/main-navbar-admin.js" defer></script>
  <script src="/js/accesibilidad-state.js" defer></script>

  <!-- Bootstrap + lógica local -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const modalEl = document.getElementById('usuarioModal');
    const modal   = new bootstrap.Modal(modalEl);
    const preview = document.getElementById('u_preview');
    const fileInp = document.getElementById('u_foto');

    function openCreate(){
      document.getElementById('modalTitle').textContent = 'Nuevo usuario';
      document.getElementById('u_id').value = '';
      document.getElementById('u_nombre').value = '';
      document.getElementById('u_correo').value = '';
      document.getElementById('u_rol').value = 'cliente';
      document.getElementById('u_pass').value = '';
      document.getElementById('pass_help').textContent = 'Obligatoria al crear; opcional al editar.';
      preview.src = '/image/Usuarios/default.png';
      fileInp.value = '';
      modal.show();
    }

    function openEdit(id, data){
      document.getElementById('modalTitle').textContent = 'Editar usuario #' + id;
      document.getElementById('u_id').value = id;
      document.getElementById('u_nombre').value = data.nombre || '';
      document.getElementById('u_correo').value = data.correo || '';
      document.getElementById('u_rol').value = data.rol || 'cliente';
      document.getElementById('u_pass').value = '';
      document.getElementById('pass_help').textContent = 'Deja vacío para mantener la contraseña actual.';

      // Vista previa
      let src = (data && data.foto) ? (data.foto.startsWith('/') ? data.foto : '/' + data.foto) : '';
      if (!src) {
        const rowImg = document.querySelector(`tr[data-id="${id}"] img.avatar`);
        if (rowImg && rowImg.src) src = rowImg.src;
      }
      preview.src = src || '/image/Usuarios/default.png';

      fileInp.value = '';
      modal.show();
    }

    fileInp.addEventListener('change', (e)=>{
      const f = e.target.files?.[0];
      if(!f) return;
      const ok = ['image/jpeg','image/png','image/webp'].includes(f.type);
      if(!ok){ alert('Formato no válido (usa JPG/PNG/WebP)'); fileInp.value=''; return; }
      if(f.size > 2*1024*1024){ alert('La imagen supera 2MB'); fileInp.value=''; return; }
      const reader = new FileReader();
      reader.onload = ev => preview.src = ev.target.result;
      reader.readAsDataURL(f);
    });

    // Submit del formulario
    document.getElementById('formUsuario').addEventListener('submit', function(ev){
      ev.preventDefault();
      const fd = new FormData(this);

      fetch('/components/admin/usuarios/usuarios_acciones.php', {
        method: 'POST',
        body: fd
      })
      .then(r => r.json())
      .then(data => {
        if (!data.ok) throw new Error(data.msg || 'Error');
        if (data.logout) {
          window.location.reload(); // polices.php actuará y mandará a 404
          return;
        }
        modal.hide();
        location.reload();
      })
      .catch(e => alert('No se pudo guardar: ' + e.message));
    });

    // Borrado
    function confirmDelete(id){
      if(!confirm('¿Eliminar el usuario #' + id + '?')) return;
      const fd = new FormData();
      fd.append('action', 'delete');
      fd.append('id', id);

      fetch('/components/admin/usuarios/usuarios_acciones.php', {
        method: 'POST',
        body: fd
      })
      .then(r => r.json())
      .then(data => {
        if (!data.ok) throw new Error(data.msg || 'Error');
        if (data.logout) {
          window.location.reload(); // polices.php actuará y mandará a 404
        } else {
          location.reload();
        }
      })
      .catch(e => alert('No se pudo eliminar: ' + e.message));
    }
    window.confirmDelete = confirmDelete;
    window.openCreate = openCreate;
    window.openEdit = openEdit;
  </script>
</body>
</html>
