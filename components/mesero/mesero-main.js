/* menu encapsulado */
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
            <a href="/components/mesero/index.php">
              <i class='bx bx-user nav-icon'></i>
              <span class="nav-text">Inicio</span>
            </a>
          </li>

          <li class="nav-item">
            <a href="/components/mesero/menu/index.php">
              <i class='bx bx-map nav-icon'></i>
              <span class="nav-text">Menu</span>
            </a>
          </li>

          <li class="nav-item">
            <a href="/components/mesero/comandas/index.php">
              <i class='bx bx-trending-up nav-icon'></i>
              <span class="nav-text">Comandas</span>
            </a>
          </li>

          <li class="nav-item">
            <a href="/components/mesero/pedidos/index.php">
              <i class='bx bx-receipt nav-icon'></i>
              <span class="nav-text">Pedidos</span>
            </a>
          </li>

          <li class="nav-item">
            <a href="/components/mesero/reservaciones/index.php">
              <i class='bx bx-heart nav-icon'></i>
              <span class="nav-text">Reservaciones</span>
            </a>
          </li>

          <li class="nav-item">
            <a href="/components/mesero/caja/index.php">
              <i class='bx bx-wallet nav-icon'></i>
              <span class="nav-text">Mi caja</span>
            </a>
          </li>
        </ul>

        <ul>
          <li class="nav-item">
            <a href="/components/mesero/ajustes/index.php">
              <i class='bx bx-cog nav-icon'></i>
              <span class="nav-text">Ajustes</span>
            </a>
          </li>

          <li class="nav-item">
            <a href="/php/logout.php">
              <i class='bx bx-log-out nav-icon'></i>
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