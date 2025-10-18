<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Don Camarón | Inicio </title>
    <link rel="stylesheet" href="/components/usuario/usuario-estilo.css">
    <link rel="stylesheet" href="/css/estilo-chatbot.css">
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@48,400,0,0"
    />
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@48,400,1,0"
    />
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="/css/estilo-footer.css">
    <link rel="icon" href="/icon.png" type="image/x-icon">
</head>
<body>
    <nav class="nav">
    <div class="container">
        <div class="logo">
            <a href="#">Don Camaron</a>
        </div>
        <div class="main_list" id="mainListDiv">
            <ul>
                <li><a href="#">Inicio</a></li>
                <li><a href="/components/usuario/menu/index.php">Menu</a></li>
                <li><a href="/components/usuario/menu/ver_pedido.php">Pedidos</a></li>
                <li><a href="#">Reservaciones</a></li>
                <li><a href="#">Configuraciones</a></li>
            </ul>
        </div>
        <div class="media_button">
            <button class="main_media_button" id="mediaButton">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </div>
</nav>
    
<section class="home">

<!-- inicio del Chatbot -->
<!-- Code :) -->
   <button class="chatbot__button">
      <span class="material-symbols-outlined">mode_comment</span>
      <span class="material-symbols-outlined">close</span>
    </button>
    <div class="chatbot">
      <div class="chatbot__header">
        <h3 class="chatbox__title">Chatbot</h3>
        <span class="material-symbols-outlined">close</span>
      </div>
      <ul class="chatbot__box">
        <li class="chatbot__chat incoming">
          <span class="material-symbols-outlined">smart_toy</span>
          <p>Hi there. How can I help you today?</p>
        </li>
        <li class="chatbot__chat outgoing">
          <p>...</p>
        </li>
      </ul>
      <div class="chatbot__input-box">
        <textarea
          class="chatbot__textarea"
          placeholder="Enter a message..."
          required
        ></textarea>
        <span id="send-btn" class="material-symbols-outlined">send</span>
      </div>
    </div>
<!-- final del Chatbot -->

</section>
    <footer>
    <div  id="footer-container"></div> 
    </footer>

    
<script src="/js/main-chatbot.js"></script>
<script src="/components/usuario/usuario-main.js"></script>
<script src="/js/main-footer.js"></script>
</body>
</html>