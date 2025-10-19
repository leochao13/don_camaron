// main-menu.js
(() => {
  const form      = document.getElementById('item-form');
  const idEl      = document.getElementById('item-id');
  const oldImgEl  = document.getElementById('old_image');
  const catEl     = document.getElementById('categoria');
  const nameEl    = document.getElementById('name');
  const precioEl  = document.getElementById('precio');
  const descEl    = document.getElementById('descuento');
  const descrEl   = document.getElementById('descripcion');
  const imgEl     = document.getElementById('imagen');
  const imgPrev   = document.getElementById('imagen-preview');
  const stockEl   = document.getElementById('stock');
  const activoEl  = document.getElementById('activo');
  const submitBtn = document.getElementById('submit-button');
  const cancelBtn = document.getElementById('cancel-button');
  const tbody     = document.querySelector('#products-table tbody');

  // --- Preview de imagen seleccionada
  imgEl.addEventListener('change', () => {
    const file = imgEl.files[0];
    if (!file) { imgPrev.style.display = 'none'; return; }
    const url = URL.createObjectURL(file);
    imgPrev.src = url;
    imgPrev.style.display = 'block';
  });

  // --- Cargar la tabla de productos
  async function loadTable() {
    const res = await fetch('/components/admin/menu/get_products.php', { cache: 'no-store' });
    tbody.innerHTML = await res.text();
    bindRowButtons();
  }

  // --- Resetear formulario
  function resetForm() {
    idEl.value = '';
    oldImgEl.value = '';
    form.reset();
    imgPrev.style.display = 'none';
    submitBtn.textContent = 'Agregar Producto';
    cancelBtn.style.display = 'none';
  }
  cancelBtn.addEventListener('click', resetForm);

  // --- Envío del form (crear/editar)
  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const fd = new FormData(form);
    if (!fd.get('categoria')) { alert('Selecciona una categoría'); return; }
    if (!fd.get('nombre'))    { alert('Escribe el nombre'); return; }
    if (Number(fd.get('precio')) < 0) { alert('Precio inválido'); return; }
    if (Number(fd.get('stock'))  < 0) { alert('Stock inválido'); return; }

    // Texto del botón dinámico
    const wasEdit = Boolean(idEl.value);
    submitBtn.disabled = true;
    submitBtn.textContent = wasEdit ? 'Guardando...' : 'Agregando...';

    try {
      const res = await fetch('/components/admin/menu/save_product.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (!data.ok) throw new Error(data.error || 'Error al guardar');

      await loadTable();
      resetForm();
    } catch (err) {
      alert(err.message);
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = wasEdit ? 'Guardar Cambios' : 'Agregar Producto';
    }
  });

  // --- Vincular botones de cada fila (editar / borrar)
  function bindRowButtons() {
    // Editar
    document.querySelectorAll('.btn-edit').forEach(btn => {
      btn.onclick = (ev) => {
        const tr  = ev.target.closest('tr');
        const tds = tr.querySelectorAll('td');

        idEl.value    = tr.getAttribute('data-id') || '';

        // columnas: 0 id, 1 img, 2 nombre, 3 precio, 4 descuento, 5 descripcion, 6 stock, 7 activo
        nameEl.value   = (tds[2].textContent || '').trim();
        // "$1,234.56" -> 1234.56
        precioEl.value = parseFloat((tds[3].textContent || '').replace('$','').replace(/,/g,'')) || 0;
        descEl.value   = parseFloat((tds[4].textContent || '').replace('%','')) || 0;
        descrEl.value  = (tds[5].textContent || '').trim();
        stockEl.value  = parseInt((tds[6].textContent || '0'), 10) || 0;

        const activoTxt = (tds[7].textContent || '').trim().toLowerCase();
        activoEl.value  = (activoTxt === 'sí' || activoTxt === 'si') ? '1' : '0';

        // Tomar la ruta relativa que está en el <img>
        const imgTag = tds[1].querySelector('img');
        if (imgTag && imgTag.src) {
          imgPrev.src = imgTag.src;
          imgPrev.style.display = 'block';

          // Convertir URL absoluta a ruta relativa {categoria/archivo}
          try {
            const url = new URL(imgTag.src, window.location.origin);
            const prefix = '/components/usuario/menu/images/productos/';
            const rel = url.pathname.startsWith(prefix) ? url.pathname.substring(prefix.length) : '';
            oldImgEl.value = rel;   // ej: camarones/prod_2025....jpg
            // También podemos inferir categoría desde la ruta
            const maybeCat = rel.split('/')[0];
            if (maybeCat) { catEl.value = maybeCat; }
          } catch { oldImgEl.value = ''; }
        } else {
          imgPrev.style.display = 'none';
          oldImgEl.value = '';
        }

        submitBtn.textContent = 'Guardar Cambios';
        cancelBtn.style.display = 'inline-block';
        window.scrollTo({ top: form.offsetTop - 20, behavior: 'smooth' });
      };
    });

    // Borrar
    document.querySelectorAll('.btn-delete').forEach(btn => {
      btn.onclick = async (ev) => {
        const tr = ev.target.closest('tr');
        const id = tr.getAttribute('data-id');
        if (!id) return;

        if (!confirm(`¿Eliminar producto #${id}?`)) return;

        const fd = new FormData();
        fd.append('id', id);

        try {
          const res  = await fetch('/components/admin/menu/delete_product.php', { method: 'POST', body: fd });
          const data = await res.json();
          if (!data.ok) throw new Error(data.error || 'Error al eliminar');

          await loadTable();
          resetForm();
        } catch (err) {
          alert(err.message);
        }
      };
    });
  }

  // --- Inicializar
  loadTable();
})();
