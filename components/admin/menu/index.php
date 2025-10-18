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

  <style>
    body.darkmode { background:#0f1216; color:#e5e7eb; }
    .card { background:#151a21; border:1px solid #1e2530; border-radius:16px; }
    .form-control, .form-select, textarea { background:#0f141d; color:#e5e7eb; border:1px solid #273142; }
    .table thead th { border-color:#273142; }
    .table tbody td { border-color:#1e2530; }
    .badge-soft { background:#223049; color:#cfe7ff; border:1px solid #2e3c55; }

    /* Empuja el contenido a la derecha del sidebar */
    .container {
      margin-left: 240px;                 
      max-width: calc(100% - 260px);
    }
    @media (max-width: 991px) {
      .container { margin-left: 16px; max-width: calc(100% - 32px); }
    }
  </style>
</head>
<body class="darkmode">
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
              <img id="imagen-preview" src="#" alt="Vista previa" style="display:none; max-width:220px; margin-top:10px; border-radius:8px;">
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
            <thead class="table-dark">
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

  <!-- Scripts -->
  <script src="/js/main-navbar-admin.js"></script>
  <script src="/components/admin/menu/main-menu.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
