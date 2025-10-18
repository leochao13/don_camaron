const navbarHTML = `

   <nav class="main-menu">
  <div>
    <div class="user-info">
      <img
        src="https://github.com/ecemgo/mini-samples-great-tricks/assets/13468728/e5a707f4-d5ac-4f30-afd8-4961ae353dbc"
        alt="user" />
      <p>Mia Taylor</p>
    </div>
    <ul>
       <li class="nav-item ">
        <a href="/components/admin/index.php">
          <i class='bx bxs-home nav-icon'></i>
          <span class="nav-text">Inicio</span>
        </a>
      </li>

      <li class="nav-item">
        <a href="/components/admin/menu/index.php">
          <i class='bx bxs-food-menu nav-icon'></i>
          <span class="nav-text">Menu</span>
        </a>
      </li>

      <li class="nav-item">
        <a href="/components/admin/usuarios/usuarios.php">
          <i class='bx bxs-user-detail nav-icon'></i>
          <span class="nav-text">Usuarios</span>
        </a>
      </li>

      <li class="nav-item">
        <a href="/components/admin/pedidos/index.php">
          <i class='bx bxs-bar-chart-alt-2 nav-icon'></i>
          <span class="nav-text">Pedidos</span>
        </a>
      </li>

      <li class="nav-item">
        <a href="/components/admin/reservaciones/reservaciones.php">
          <i class='bx bxs-heart nav-icon'></i>
          <span class="nav-text">Reservaciones</span>
        </a>
      </li>

      <li class="nav-item">
        <a href="/components/admin/graficas/index.php">
          <i class='bx bxs-edit nav-icon'></i>
          <span class="nav-text">Graficas</span>
        </a>
      </li>
    </ul>
  </div>

  <ul>
    <li class="nav-item">
      <a href="/components/admin/ajustes-admin/index.php">
        <i class='bx bxs-cog nav-icon'></i>
        <span class="nav-text">Ajustes</span>
      </a>
    </li>
     <li class="nav-item">
      <a href="/php/logout.php">
        <i class='bx bxs-log-out nav-icon'></i>
        <span class="nav-text">Salir</span>
      </a>
    </li>
  </ul>
</nav>

`;

const navbarContainer = document.getElementById('navbar-container');
navbarContainer.innerHTML = navbarHTML;

let menuList = document.getElementById("menuList")
menuList.style.maxHeight = "0px";

function toggleMenu(){
    if(menuList.style.maxHeight == "0px")
    {
        menuList.style.maxHeight = "300px";
    }
    else{
        menuList.style.maxHeight = "0px";
    }
}