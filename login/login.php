<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Don Camarón | Login</title>
    <link rel="stylesheet" href="/login/login-estilo.css">
    <link rel="stylesheet" href="/css/estilo-inicio.css">
    <link rel="icon" href="/icon.png" type="image/x-icon">
</head>
<body>
    <div class="nav-bar" > 
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
    <div class="wrapper">
  <div class="card-switch">
    <label class="switch">
      <input type="checkbox" class="toggle">
      <span class="slider"></span>
      <span class="card-side"></span>
      <div class="flip-card__inner">
        
        <!-- Login -->
        <div class="flip-card__front">
          <div class="title">Iniciar sesión</div>
          <form class="flip-card__form" action="/php/auth.php" method="POST">
            <input type="hidden" name="accion" value="login">
            <input class="flip-card__input" name="email" placeholder="Correo" type="email" required>
            <input class="flip-card__input" name="password" placeholder="Contraseña" type="password" required>
            <button class="flip-card__btn" type="submit">Vamos!</button>
          </form>
        </div>

        <!-- Registro -->
        <div class="flip-card__back">
          <div class="title">Inscribirse</div>
          <form class="flip-card__form" action="/php/auth.php" method="POST">
            <input type="hidden" name="accion" value="register">
            <input class="flip-card__input" name="nombre" placeholder="Nombre" type="text" required>
            <input class="flip-card__input" name="correo" placeholder="Email" type="email" required>
            <input class="flip-card__input" name="password" placeholder="Password" type="password" required>
            <button class="flip-card__btn" type="submit">Confirmar!</button>
          </form>
        </div>

      </div>
    </label>
  </div>
</div>
      <footer>
    <div  id="footer-container"></div> 
    </footer>

    <script src="/js/main-footer.js"></script>
     <link rel="stylesheet" href="/css/estilo-footer.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</body>
</html>