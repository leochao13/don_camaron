<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Don Camaron | Nosotros</title>
    <link rel="stylesheet" href="/css/estilo-inicio.css">
    <link rel="stylesheet" href="/inicio/nosotros/estilo-nosotros.css">
    <link rel="icon" href="/icon.png" type="image/x-icon">
    <link rel="stylesheet" href="/css/estilo-footer.css">
    <!-- Swiper CSS -->
    <link  rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- Swiper JS -->
    

</head>
<body>

     <div  id="navbar-container"></div> 
    
    <section>
      <div class="content">
      <div class="info">
        <h2>Don <span> Camaron</span></h2>
        <p>
          Ven a disfrutar con <span class="movie-night">Don Camarón</span> lo mejor del mar:  
          ceviches frescos, cocteles, camarones, pescados y más, preparados al momento.  
          Haz tu pedido en línea desde tu casa y pasa por el de manera express, o acompáñanos para vivir la experiencia marinera.  
          ¡El sabor auténtico del océano directo a tu mesa!
        </p>
        <button class="btn">Ordenar Ahora</button>
      </div>
        <div class="swiper">
          <div class="swiper-wrapper">
            <div class="swiper-slide">
              <img
                src="https://i.pinimg.com/1200x/ac/72/59/ac72592758f1bbfbe637141af8e09ddc.jpg"
                alt="" />
              <div class="overlay">
                <span>8.5</span>
                <h2>Camar al Ajillo</h2>
              </div>
            </div>

            <div class="swiper-slide">
              <img
                class="img-position"
                src="https://media-cdn.tripadvisor.com/media/photo-s/1d/01/04/c0/exquisitos-platillos.jpg"
                alt="" />
              <div class="overlay">
                <span>9.5</span>
                <h2>Pulpo a la diabla</h2>
              </div>
            </div>

            <div class="swiper-slide">
              <img
                src="https://www.recetasnestle.com.mx/sites/default/files/2024-01/mariscos-ajillo-pincho.jpg"
                alt="" />
              <div class="overlay">
                <span>8.1</span>
                <h2>Brochetas de camarones</h2>
              </div>
            </div>

            <div class="swiper-slide">
              <img
                src="https://www.gob.mx/cms/uploads/article/main_image/94234/Platillo_tilapia.jpg"
                alt="" />
              <div class="overlay">
                <span>8.7</span>
                <h2>Pescado a la plancha</h2>
              </div>
            </div>

            <div class="swiper-slide">
              <img
                src="https://cdn.pixabay.com/photo/2019/09/26/06/38/seafood-soup-4505211_1280.jpg"
                alt="" />
              <div class="overlay">
                <span>8.6</span>
                <h2>Caldo de cangrejo</h2>
              </div>
            </div>

            <div class="swiper-slide">
              <img
                src="https://cdn.pixabay.com/photo/2019/09/09/22/19/rice-with-seafood-4464793_1280.jpg"
                alt="" />
              <div class="overlay">
                <span>8.9</span>
                <h2>Paeya de mariscos</h2>
              </div>
            </div>

            <div class="swiper-slide">
              <img
                class="img-position"
                src="https://cdn.pixabay.com/photo/2020/03/21/04/00/shrimp-4952607_1280.jpg"
                alt="" />
              <div class="overlay">
                <span>8.6</span>
                <h2>Orden de camarones</h2>
              </div>
            </div>

            <div class="swiper-slide">
              <img
                src="https://jameaperu.com/assets/images/parihuela_800x534.webp"
                alt="" />
              <div class="overlay">
                <span>8.7</span>
                <h2>Cangrejo al mojo de ajo</h2>
              </div>
            </div>

            <div class="swiper-slide">
              <img
                src="https://www.mexicoenmicocina.com/wp-content/uploads/2022/05/pescadillas-tacos-de-pescado-1-500x500.jpg"
                alt="" />
              <div class="overlay">
                <span>9.2</span>
                <h2>Pescadillas</h2>
              </div>
            </div>
          </div>
        </div>
      </div>

      <ul class="circles">
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
      </ul>
    </section>
    
    <footer>
        <div  id="footer-container"></div> 
    </footer>

     <script src="/js/main-navbar.js"></script>
     <script src="/inicio/nosotros/main-nosotros.js"></script>
     <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
     <script src="/js/main-footer.js"></script>

    <script>
    var swiper = new Swiper(".swiper", {
        effect: "cards",
        grabCursor: true,
        initialSlide: 2,
        speed: 500,
        loop: true,
        rotate: true,
        mousewheel: {
        invert: false,
        },
    });
    </script>
</body>
</html>