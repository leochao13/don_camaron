<!-- validacion de usuario -->
<?php
  include("/xampp/htdocs/php/polices.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Don Camarón | Ajustes</title>
  <link rel="stylesheet" href="/components/mesero/mesero-estilo.css" />
  <link rel="stylesheet" href="/components/mesero/ajustes/ajustes-estilo.css" />
  <link rel="icon" href="/icon.png" type="image/x-icon" />
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Roboto:wght@400;700&family=Open+Sans:wght@400;700&family=Poppins:wght@400;700&family=Merriweather:wght@400;700&display=swap"
    rel="stylesheet"
  />
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
</head>

<body class="darkmode">
  <?php include("/xampp/htdocs/php/polices.php"); ?>

  <div id="navbar-container"></div>

  <section class="content">
    <main class="ajustes-container">
      <h2>⚙️ Panel de Accesibilidad</h2>

      <div class="ajustes-botones">
        <button id="btn-darkmode" onclick="toggleDarkMode()">🌙 Modo oscuro</button>
        <button onclick="resetFontSize()">🔡 Tamaño normal</button>
        <button onclick="increaseFontSize()">🔠 Aumentar letra</button>
        <button onclick="toggleGrayscale()">⚫ Escala de grises</button>
        <button onclick="toggleGuideline()">📖 Guía de lectura</button>
        <button onclick="toggleFontType()">🅰 Cambiar tipografía</button>
      </div>

      <div class="contraste-control">
        <label for="contrasteSlider">Contraste:</label>
        <input type="range" id="contrasteSlider" min="0.5" max="1.5" step="0.01" value="1" />
      </div>
    </main>
  </section>

  <script src="/components/mesero/mesero-main.js"></script>
  <script src="/components/mesero/ajustes/ajustes-main.js"></script>
</body>
</html>
