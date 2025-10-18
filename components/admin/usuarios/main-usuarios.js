// Helpers
function qs(sel){ return document.querySelector(sel); }
function qsa(sel){ return Array.from(document.querySelectorAll(sel)); }

const tbody = qs('#users-table tbody');
const cardForm = qs('#card-form');
const btnNuevo = qs('#btn-nuevo');
const btnCancel = qs('#btn-cancel');
const frm = qs('#user-form');
const avatarInp = qs('#avatar');
const avatarPrev = qs('#avatar-preview');

function showForm(show=true){
  cardForm.style.display = show ? 'block' : 'none';
  if (show) { window.scrollTo({ top: 0, behavior: 'smooth' }); }
}
function resetForm(){
  frm.reset();
  qs('#id').value = '';
  qs('#old_avatar').value = '';
  avatarPrev.style.display = 'none';
  avatarPrev.src = '#';
  qs('#password').value = '';
  qs('#activo').value = '1';
}

function loadUsers(){
  fetch('/components/admin/usuarios/get_users.php')
    .then(r=>r.ok?r.text():Promise.reject('HTTP '+r.status))
    .then(html=>{ tbody.innerHTML = html; })
    .catch(err=>{ tbody.innerHTML = `<tr><td colspan="8">Error: ${err}</td></tr>`; });
}

btnNuevo?.addEventListener('click', ()=>{
  resetForm();
  showForm(true);
});

btnCancel?.addEventListener('click', ()=>{
  showForm(false);
});

avatarInp?.addEventListener('change', (e)=>{
  const f = e.target.files?.[0];
  if (f){
    const url = URL.createObjectURL(f);
    avatarPrev.src = url;
    avatarPrev.style.display = 'block';
  }
});

// Guardar (crear/editar)
frm?.addEventListener('submit', (ev)=>{
  ev.preventDefault();
  const fd = new FormData(frm);
  fetch('/components/admin/usuarios/save_user.php', { method:'POST', body: fd })
    .then(r=>r.json())
    .then(data=>{
      if(!data.ok) throw new Error(data.msg || 'Error');
      loadUsers();
      showForm(false);
    })
    .catch(e=>alert(e.message));
});

// Delegación de acciones en la tabla
tbody?.addEventListener('click', (ev)=>{
  const editBtn = ev.target.closest('.btn-edit');
  const delBtn  = ev.target.closest('.btn-del');
  const togBtn  = ev.target.closest('.btn-toggle');

  if (editBtn){
    const row = editBtn.closest('tr');
    const user = JSON.parse(row.dataset.user);
    // llenar form
    resetForm();
    qs('#id').value = user.id;
    qs('#nombre').value = user.nombre;
    qs('#email').value = user.email;
    qs('#rol').value = user.rol;
    qs('#activo').value = user.activo ? '1' : '0';
    if (user.avatar_url){
      avatarPrev.src = user.avatar_url;
      avatarPrev.style.display = 'block';
      qs('#old_avatar').value = user.avatar || '';
    }
    showForm(true);
  }

  if (delBtn){
    if (!confirm('¿Eliminar este usuario?')) return;
    const id = delBtn.dataset.id;
    const fd = new FormData();
    fd.append('id', id);
    fetch('/components/admin/usuarios/delete_user.php', { method:'POST', body: fd })
      .then(r=>r.json())
      .then(data=>{
        if(!data.ok) throw new Error(data.msg||'Error');
        loadUsers();
      })
      .catch(e=>alert(e.message));
  }

  if (togBtn){
    const id = togBtn.dataset.id;
    fetch('/components/admin/usuarios/toggle_active.php', {
      method:'POST',
      body: new URLSearchParams({ id })
    })
    .then(r=>r.json())
    .then(data=>{
      if(!data.ok) throw new Error(data.msg||'Error');
      loadUsers();
    })
    .catch(e=>alert(e.message));
  }
});

loadUsers();
