/* inicio de modo oscuro */

// Función que activa/desactiva el modo oscuro

function toggleDarkMode() {
  document.body.classList.toggle("dark-mode");
  const isDark = document.body.classList.contains("dark-mode");
  
  // Guardar preferencia en localStorage
  localStorage.setItem("modo-oscuro", isDark);

  // Cambiar texto del botón solo si existe en la página
  const btn = document.getElementById("btn-darkmode");
  if (btn) btn.textContent = isDark ? "☀️ Modo claro" : "🌙 Modo oscuro";
}

// Aplicar automáticamente al cargar cada página
window.addEventListener("load", () => {
  const darkMode = localStorage.getItem("modo-oscuro") === "true";
  if (darkMode) {
    document.body.classList.add("dark-mode");
  }

  // Actualizar el texto del botón solo si existe
  const btn = document.getElementById("btn-darkmode");
  if (btn) btn.textContent = darkMode ? "☀️ Modo claro" : "🌙 Modo oscuro";
});
/* Final del modo oscuro */
/* inicio de modo contraste */
// Contenedor de todo el contenido excepto navbar
const contenido = document.querySelector('section.content'); // ajusta si tu contenedor principal tiene otro selector
const contrasteSlider = document.getElementById("contrasteSlider");

function aplicarContraste(valor) {
  if (contenido) {
    // Aplicar brillo al contenido, no al navbar
    contenido.style.filter = `brightness(${valor})`;
  }
  localStorage.setItem("contraste", valor);
}

// Escuchar cambios del slider
if (contrasteSlider) {
  contrasteSlider.addEventListener("input", (e) => {
    aplicarContraste(e.target.value);
  });
}

// Aplicar contraste al cargar la página
window.addEventListener("load", () => {
  const savedContrast = localStorage.getItem("contraste");
  if (savedContrast && contenido) {
    contenido.style.filter = `brightness(${savedContrast})`;
    if (contrasteSlider) contrasteSlider.value = savedContrast;
  } else if (contenido) {
    contenido.style.filter = `brightness(1)`;
  }
});

/* -------- AUMENTAR LETRA -------- */
let currentFontSize = parseFloat(localStorage.getItem("fontSize")) || 1; // 1 = tamaño base

function applyFontSize(size) {
  document.documentElement.style.setProperty("--font-scale", size);
  localStorage.setItem("fontSize", size);
}

// Función que aumenta el tamaño hasta cierto límite
function increaseFontSize() {
  currentFontSize += 0.1;
  if (currentFontSize > 1.6) currentFontSize = 1.6; // Límite máximo
  applyFontSize(currentFontSize);
}

// Aplicar el tamaño guardado al cargar la página
window.addEventListener("load", () => {
  const savedSize = parseFloat(localStorage.getItem("fontSize")) || 1;
  applyFontSize(savedSize);
});
function resetFontSize() {
  currentFontSize = 1;
  applyFontSize(currentFontSize);
}

/* -------- ESCALA DE GRISES -------- */
function toggleGrayscale() {
  document.body.classList.toggle("grayscale-mode");
  const isGrayscale = document.body.classList.contains("grayscale-mode");
  localStorage.setItem("escala-grises", isGrayscale);

  // Cambiar texto del botón (opcional)
  const btn = document.querySelector('button[onclick="toggleGrayscale()"]');
  if (btn) btn.textContent = isGrayscale ? "⚪ Color normal" : "⚫ Escala de grises";
}

// Aplicar al cargar la página
window.addEventListener("load", () => {
  const grayscale = localStorage.getItem("escala-grises") === "true";
  if (grayscale) {
    document.body.classList.add("grayscale-mode");
    const btn = document.querySelector('button[onclick="toggleGrayscale()"]');
    if (btn) btn.textContent = "⚪ Color normal";
  }
});

/* -------- GUÍA DE LECTURA -------- */
let guideline = null;

function toggleGuideline() {
  const isActive = document.body.classList.toggle("reading-guide");
  localStorage.setItem("guia-lectura", isActive);

  // Crear o eliminar la guía visual
  if (isActive) {
    guideline = document.createElement("div");
    guideline.id = "readingGuide";
    document.body.appendChild(guideline);

    // Mover la guía según el ratón
    document.addEventListener("mousemove", moveGuide);
  } else {
    const existingGuide = document.getElementById("readingGuide");
    if (existingGuide) existingGuide.remove();
    document.removeEventListener("mousemove", moveGuide);
  }

  // Cambiar texto del botón (opcional)
  const btn = document.querySelector('button[onclick="toggleGuideline()"]');
  if (btn) btn.textContent = isActive ? "❌ Quitar guía" : "📖 Guía de lectura";
}

function moveGuide(e) {
  const guide = document.getElementById("readingGuide");
  if (guide) {
    guide.style.top = `${e.clientY - guide.offsetHeight / 2}px`;
  }
}

// Aplicar al cargar la página
window.addEventListener("load", () => {
  const active = localStorage.getItem("guia-lectura") === "true";
  if (active) {
    document.body.classList.add("reading-guide");
    toggleGuideline(); // Activa automáticamente la guía
  }
});
/* -------- CAMBIO DE TIPOGRAFÍA -------- */
const fontFamilies = [
  "'Nunito', sans-serif",   // tu fuente actual
  "'Roboto', sans-serif",
  "'Open Sans', sans-serif",
  "'Poppins', sans-serif",
  "'Merriweather', serif"
];

let currentFontIndex = parseInt(localStorage.getItem("fontIndex")) || 0;

function applyFontType(index) {
  document.documentElement.style.setProperty("--font-family", fontFamilies[index]);
  localStorage.setItem("fontIndex", index);
}

function toggleFontType() {
  currentFontIndex = (currentFontIndex + 1) % fontFamilies.length;
  applyFontType(currentFontIndex);

  // Cambiar texto del botón (opcional)
  const btn = document.querySelector('button[onclick="toggleFontType()"]');
  if (btn) btn.textContent = `🅰 Cambiar tipografía (${fontFamilies[currentFontIndex].replace(/['",]/g, '')})`;
}

// Aplicar al cargar la página
window.addEventListener("load", () => {
  applyFontType(currentFontIndex);
});

