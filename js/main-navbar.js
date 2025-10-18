const navbarHTML = `

    
    <div class="nav-bar">
        <header class="header">
            <div class="logo">
                <p>Don <span>Camarón</span></p>
            </div>
            <div class="hamburguesa">
                <img src="image/menu.png" alt="Menu hamburguesa">
            </div>
            <nav class="menu">
                <ul class="navegacion">
                   
                    <li><a href="/index.html">Inicio</a></li>
                    <li><a href="/inicio/menu/index.php">Menu</a></li>
                    <li><a href="/inicio/servicios/index.php">Servicios</a></li>
                    <li><a href="/inicio/nosotros/index.php">Nosotros</a></li>
                    <li><a href="/inicio/galeria/index.php">Galeria</a></li>
                    <li><a href="#"></a><a href="/login/login.php">Iniciar Session</a></li>

                </ul>
            </nav>
        </header>
        </div>

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