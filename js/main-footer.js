document.addEventListener("DOMContentLoaded", () => {
  const footer = document.getElementById("footer-container");

  footer.innerHTML = `
  
    <div class="site-footer">
      <div class="footer-container">
        
        <!-- Logo y slogan -->
        <div>
          <div class="footer-logo-box">
            <img src="https://media.istockphoto.com/id/956980438/es/vector/resumen-camar%C3%B3n-camar%C3%B3n-aislado-sobre-fondo-blanco.jpg?s=612x612&w=0&k=20&c=Wx4joEivZe8PG9ULcXVDU0GKBwWxPvz8cTk2_bnlQAU=" alt="Don Camarón">
          </div>
          <p class="slogan">¡El mar directo hasta tu mesa!</p>
        </div>

        <!-- Pagos + Redes -->
        <div class="text-center">
          <div class="payments">
            <i class='bx bxl-visa' title="Visa"></i>
            <i class='bx bxl-mastercard' title="Mastercard"></i>
            <i class='bx bxl-amex' title="American Express"></i>
            <i class='bx bxl-paypal' title="PayPal"></i>
            <i class='bx bxl-apple' title="Apple Pay"></i>
            <i class='bx bxl-google' title="Google Pay"></i>
            <i class='bx bx-wallet' title="Mercado Pago"></i>
            <span><i class='bx bx-shield-alt-2'></i> Pago seguro</span>
          </div>

          <div class="social">
            <a href="#" aria-label="Facebook"><i class='bx bxl-facebook'></i></a>
            <a href="#" aria-label="Instagram"><i class='bx bxl-instagram'></i></a>
            <a href="https://wa.me/525616677657?text=Hola%20Don%20Camar%C3%B3n%2C%20quiero%20hacer%20un%20pedido" 
                target="_blank" rel="noopener noreferrer" aria-label="WhatsApp">
                <i class='bx bxl-whatsapp'></i>
            </a>
            </div>

        </div>

        <!-- Contacto -->
        <div class="contact">
          <div><i class="bi bi-telephone"></i> <a href="tel:+525616677657">561 667 7657</a></div>
          <div><i class="bi bi-clock"></i> L-D: <strong>06:00–18:00</strong></div>
          <div>
            <i class="bi bi-geo-alt"></i>
            <div>Central de Pescados y Mariscos, La Nueva Viga<br>
              Eje 6 Sur 560, Bodega E-25, CDMX 09040
            </div>
          </div>
        </div>

        <!-- Mapa -->
        <div class="map-wrapper">
          <iframe class="map-iframe"
            loading="lazy" allowfullscreen referrerpolicy="no-referrer-when-downgrade"
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3764.996227310386!2d-99.088!3d19.329!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2sLa%20Nueva%20Viga!5e0!3m2!1ses-419!2smx!4v1700000000000">
          </iframe>
        </div>
      </div>

      <!-- Legal -->
      <div class="legal">
        <div>© ${new Date().getFullYear()} <strong>Don Camarón Online</strong></div>
        <div class="footer-links">
        <a href="/docs/terminos_y_condiciones.pdf" download="Terminos_DonCamaron.pdf">Términos y Condiciones</a>
        <a href="/docs/Privacidad.pdf" download="Privacidad_DonCamaron.pdf">Privacidad</a>
        <a href="/docs/Rembolsos.pdf" download="Reembolsos_DonCamaron.pdf">Reembolsos</a>
        <a href="/docs/Envíos.pdf" download="Envios_DonCamaron.pdf">Envíos</a>
        </div>
        </div>
      </div>

    <!-- WhatsApp flotante -->
    <a href="https://wa.me/525616677657?text=Hola%20Don%20Camarón%2C%20quiero%20hacer%20un%20pedido"
      target="_blank" rel="noopener noreferrer" aria-label="Chat WhatsApp" class="wa-float">
       <i class='bx bxl-whatsapp'></i>
    </a>
  `;
});
