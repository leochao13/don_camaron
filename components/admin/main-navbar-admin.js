// /js/main-navbar-admin.js
(function () {
  'use strict';

  // --- Utilidades ---
  const log  = (...a) => console.log("%c[admin-nav]", "color:#0aa", ...a);
  const warn = (...a) => console.warn("%c[admin-nav]", "color:#d80", ...a);
  const err  = (...a) => console.error("%c[admin-nav]", "color:#e33", ...a);

  // Ejecutar cuando el DOM esté listo (por si no usas defer)
  const onReady = (fn) => {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", fn, { once: true });
    } else {
      fn();
    }
  };

  onReady(() => {
    // 1) Montaje
    const mount = document.getElementById("navbar-container");
    if (!mount) {
      err("No existe #navbar-container en el DOM.");
      return;
    }

    // 2) Links (ajusta rutas si tu estructura es distinta)
    const LINKS = [
      { href: "/components/admin/index.php",           icon: "bx-home",           label: "Inicio" },
      { href: "/components/admin/menu/",               icon: "bx-book",           label: "Menu" },
      { href: "/components/admin/usuarios/usuarios.php", icon: "bx-user",        label: "Usuarios" },
      { href: "/components/admin/pedidos/",            icon: "bx-bar-chart",      label: "Pedidos" },
      { href: "/components/admin/reservaciones/",      icon: "bx-heart",          label: "Reservaciones" },
      { href: "/components/admin/graficas/",           icon: "bx-pie-chart-alt",  label: "Gráficas" },
    ];

    const htmlLink = ({ href, icon, label }) => `
      <li class="nav-item">
        <a class="nav-link" href="${href}" data-href="${href}">
          <i class='bx ${icon}' aria-hidden="true"></i>
          <span>${label}</span>
        </a>
      </li>
    `;

    // 3) HTML básico (sidebar + topbar)
    const html = `
      <aside class="admin-sidebar" id="admin-sidebar" aria-label="Navegación administrativa">
        <div class="sb-header">
          <button id="sb-toggle" class="sb-icon-btn" aria-label="Contraer/expandir menú" title="Contraer/expandir">
            <i class='bx bx-menu' aria-hidden="true"></i>
          </button>
        </div>

        <div class="sb-profile">
          <img src="/image/avatar.png" alt="" class="sb-avatar" onerror="this.src='/image/avatar-default.png'">
          <div class="sb-user">
            <strong class="sb-name">Admin</strong>
            <small class="sb-role">Panel</small>
          </div>
        </div>

        <nav class="sb-nav" role="navigation">
          <ul class="nav-list">
            ${LINKS.map(htmlLink).join("")}
          </ul>
          <div class="nav-bottom">
            <a class="nav-link" href="/components/admin/ajustes/">
              <i class='bx bx-cog' aria-hidden="true"></i><span>Ajustes</span>
            </a>
            <a class="nav-link" id="sb-logout" href="/logout.php">
              <i class='bx bx-log-out' aria-hidden="true"></i><span>Salir</span>
            </a>
          </div>
        </nav>
      </aside>

      <header class="admin-topbar" id="admin-topbar" role="banner">
        <div class="tb-left">
          <button id="tb-menu" class="sb-icon-btn" aria-label="Abrir menú" title="Abrir menú">
            <i class='bx bx-menu' aria-hidden="true"></i>
          </button>
          <h1 class="tb-title">Panel Admin</h1>
        </div>
        <div class="tb-right"></div>
      </header>
    `;
    mount.innerHTML = html;

    // 4) Referencias
    const body          = document.body;
    const btnToggle     = mount.querySelector("#sb-toggle");
    const btnTopbarMenu = mount.querySelector("#tb-menu");
    const btnLogout     = mount.querySelector("#sb-logout");

    // 5) Activo preciso (elige el prefijo más largo que coincida)
    const markActive = () => {
      const normalize = (p) => (p || "")
        .replace(/\/index\.php$/i, "")   // quita index.php al final
        .replace(/\/+$/, "");            // quita barras finales

      const current = normalize(window.location.pathname);
      let best = null;

      mount.querySelectorAll(".nav-link[data-href]").forEach(a => {
        const href = normalize(a.dataset.href || "");
        const isExact  = current === href;
        const isPrefix = href && (current === href || current.startsWith(href + "/"));
        if (isExact || isPrefix) {
          if (!best || href.length > best.href.length) best = { a, href };
        }
      });

      // limpia todos
      mount.querySelectorAll(".nav-link[data-href]").forEach(a => {
        a.classList.remove("active");
        a.setAttribute("aria-current", "false");
      });

      if (best) {
        best.a.classList.add("active");
        best.a.setAttribute("aria-current", "page");
      }
    };

    // 6) Colapso sidebar con persistencia
    const STORAGE_KEY = "adminSidebarCollapsed";
    const applySidebarState = (collapsed) => {
      body.classList.toggle("sidebar-collapsed", collapsed);
    };
    applySidebarState(localStorage.getItem(STORAGE_KEY) === "1");

    const toggleSidebar = () => {
      const collapsed = !body.classList.contains("sidebar-collapsed");
      applySidebarState(collapsed);
      localStorage.setItem(STORAGE_KEY, collapsed ? "1" : "0");
    };

    // 7) Listeners
    if (btnToggle)     btnToggle.addEventListener("click", toggleSidebar); else warn("No se encontró #sb-toggle");
    if (btnTopbarMenu) btnTopbarMenu.addEventListener("click", toggleSidebar); else warn("No se encontró #tb-menu");

    if (btnLogout) {
      btnLogout.addEventListener("click", (e) => {
        const ok = confirm("¿Seguro que deseas salir de la sesión?");
        if (!ok) e.preventDefault();
      });
    } else {
      warn("No se encontró #sb-logout");
    }

    // Accesos de teclado (Home/End)
    mount.addEventListener("keydown", (e) => {
      if (!["Home", "End"].includes(e.key)) return;
      const links = [...mount.querySelectorAll(".nav-link")];
      if (!links.length) return;
      e.preventDefault();
      (e.key === "Home" ? links[0] : links[links.length - 1]).focus();
    });

    // Toque visual al presionar (opcional)
    mount.querySelectorAll(".nav-link").forEach(a => {
      a.addEventListener("mousedown", () => a.classList.add("pressed"));
      a.addEventListener("mouseup", () => a.classList.remove("pressed"));
      a.addEventListener("mouseleave", () => a.classList.remove("pressed"));
    });

    // Recalcular activo
    markActive();
    window.addEventListener("popstate", markActive);

    // 8) Diagnóstico
    log("Navbar montado en #navbar-container");
  });
})();
