// /js/accesibilidad-state.js
(function () {
  // =============================
  // Helpers
  // =============================
  const root = document.documentElement;
  const $ = (sel, ctx = document) => ctx.querySelector(sel);
  const get = (k) => localStorage.getItem(k);
  const set = (k, v) => localStorage.setItem(k, v);
  const del = (k) => localStorage.removeItem(k);
  const clamp = (n, a, b) => Math.max(a, Math.min(b, n));

  const TTS_KEY   = "ac_tts";   // 'auto' | 'off'
  const THEME_KEY = "ac_dark";  // "1" oscuro, "0" claro (default)

  // =============================
  // Tema (CLARO por defecto)
  // =============================
  const COLORS = {
    light: { bg: "#ffffff", fg: "#000000" },
    dark:  { bg: "#000000", fg: "#ffffff" },
  };

  function applyTheme(isDark) {
    const { bg, fg } = isDark ? COLORS.dark : COLORS.light;
    root.classList.toggle("theme-dark", isDark);
    root.classList.toggle("theme-light", !isDark);
    root.classList.toggle("modo-nocturno", isDark);        // compat CSS antiguo
    root.style.setProperty("--bg", bg);
    root.style.setProperty("--fg", fg);
    root.style.setProperty("--ac-invert", isDark ? "1" : "0"); // usado por tu CSS global
  }

  // =============================
  // Tipografías (por índice + compat)
  // =============================
  const FONTS = [
    `"Nunito", sans-serif`,                 // 0 (por defecto)
    `"Atkinson Hyperlegible", system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif`, // 1
    `"Merriweather Sans", system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif`       // 2
  ];

  function getFontIndexFromLegacy() {
    // Compatibilidad con flags antiguos
    const a = get("ac_fontface")  === "1";
    const b = get("ac_fontface2") === "1";
    if (a) return 1;
    if (b) return 2;
    return 0;
  }

  function applyFontByIndex(idx) {
    const i = Math.max(0, Math.min(FONTS.length - 1, Number(idx) || 0));
    root.style.setProperty("--font-family", FONTS[i]);
    set("ac_font_index", String(i));

    // Mantener compatibilidad con flags antiguos (por si otras vistas los leen)
    set("ac_fontface",  i === 1 ? "1" : "0");
    set("ac_fontface2", i === 2 ? "1" : "0");

    // Quitar clases antiguas por si existen en el DOM
    root.classList.remove("tipografia-alt", "tipografia-alt2");

    // Reflejar etiqueta/botón
    const lbl = document.getElementById("lbl-tipografia");
    if (lbl) {
      lbl.textContent =
        i === 0 ? "Tipografía (Normal)" :
        i === 1 ? "Tipografía (Atkinson)" :
                  "Tipografía (Merriweather Sans)";
    }
    const btn = document.getElementById("btn-tipografia");
    if (btn) btn.setAttribute("aria-pressed", i !== 0 ? "true" : "false");
  }

  function cycleFont() {
    const cur = Number(get("ac_font_index") ?? getFontIndexFromLegacy() ?? 0) || 0;
    const next = (cur + 1) % FONTS.length;
    applyFontByIndex(next);
  }

  // =============================
  // Aplicar estado desde storage
  // =============================
  function applyFromStorage() {
    // Tema: asegurar arranque en BLANCO si no existe preferencia
    let darkRaw = get(THEME_KEY);
    if (darkRaw === null || darkRaw === undefined) {
      set(THEME_KEY, "0"); // claro por defecto
      darkRaw = "0";
    }
    const isDark = darkRaw === "1";
    applyTheme(isDark);

    // Contraste
    const cv = parseFloat(get("ac_contrast_val"));
    const contrast =
      isNaN(cv) ? (get("ac_contrast") === "1" ? 1.6 : 1) : clamp(cv, 0.5, 2);
    root.style.setProperty("--ac-contrast", String(contrast));

    // Tamaño de letra
    const fv = parseFloat(get("ac_font_scale"));
    const fontScale =
      isNaN(fv) ? (get("ac_font") === "1" ? 1.35 : 1) : clamp(fv, 0.9, 1.6);
    root.style.setProperty("--ac-font-scale", String(fontScale));

    // Grises
    root.style.setProperty("--ac-gray", get("ac_gray") === "1" ? "1" : "0");

    // Guía lectura
    root.classList.toggle("guia-lectura", get("ac_ruler") === "1");

    // Tipografías (preferir índice nuevo; fallback a flags antiguos)
    const idxStored = get("ac_font_index");
    applyFontByIndex(idxStored !== null ? Number(idxStored) : getFontIndexFromLegacy());

    reflectButtonsState();
    reflectSlidersState();
  }

  // =============================
  // Sliders (si existen en Ajustes)
  // =============================
  function reflectSlidersState() {
    const rangeC = $("#range-contrast");
    const rangeF = $("#range-font");
    const valC = $("#val-contrast");
    const valF = $("#val-font");

    const cv = parseFloat(get("ac_contrast_val"));
    const contrast =
      isNaN(cv) ? (get("ac_contrast") === "1" ? 1.6 : 1) : clamp(cv, 0.5, 2);
    const fv = parseFloat(get("ac_font_scale"));
    const fontScale =
      isNaN(fv) ? (get("ac_font") === "1" ? 1.35 : 1) : clamp(fv, 0.9, 1.6);

    if (rangeC) rangeC.value = contrast;
    if (rangeF) rangeF.value = fontScale;
    if (valC) valC.textContent = formatX(contrast);
    if (valF) valF.textContent = formatX(fontScale);
  }

  function wireSlidersOnce() {
    const rangeC = $("#range-contrast");
    const rangeF = $("#range-font");
    const valC = $("#val-contrast");
    const valF = $("#val-font");

    if (rangeC && !rangeC._wired) {
      rangeC._wired = true;
      rangeC.addEventListener("input", (e) => {
        const v = clamp(parseFloat(e.target.value) || 1, 0.5, 2);
        root.style.setProperty("--ac-contrast", String(v));
        set("ac_contrast_val", String(v));
        if (valC) valC.textContent = formatX(v);
      });
    }
    if (rangeF && !rangeF._wired) {
      rangeF._wired = true;
      rangeF.addEventListener("input", (e) => {
        const v = clamp(parseFloat(e.target.value) || 1, 0.9, 1.6);
        root.style.setProperty("--ac-font-scale", String(v));
        set("ac_font_scale", String(v));
        if (valF) valF.textContent = formatX(v);
      });
    }
  }

  function formatX(x) {
    return (Math.round(x * 100) / 100).toFixed(2) + "×";
  }

  // =============================
  // Botones (si existen)
  // =============================
  function reflectButtonsState() {
    const press = (id, on) => {
      const btn = document.getElementById(id);
      if (!btn) return;
      btn.setAttribute("aria-pressed", on ? "true" : "false");
    };

    const isDark = get(THEME_KEY) === "1";
    press("btn-nocturno", isDark);
    press("btn-grises", get("ac_gray") === "1");
    press("btn-guia", get("ac_ruler") === "1");

    // Tipografía: ya se actualiza etiqueta en applyFontByIndex
    const idx = Number(get("ac_font_index") ?? getFontIndexFromLegacy() ?? 0) || 0;
    press("btn-tipografia", idx !== 0);

    const btnTTS = document.getElementById("btn-lector");
    if (btnTTS) {
      const on = get(TTS_KEY) === "auto";
      btnTTS.setAttribute("aria-pressed", on ? "true" : "false");
      const sp = btnTTS.querySelector("span");
      if (sp) sp.textContent = on ? "Lector pantalla (AUTO)" : "Lector pantalla";
    }
  }

  function wireButtonsOnce() {
    // Nocturno
    const bDark = $("#btn-nocturno");
    if (bDark && !bDark._wired) {
      bDark._wired = true;
      bDark.addEventListener("click", () => {
        const on = get(THEME_KEY) === "1";
        const nextIsDark = !on;
        set(THEME_KEY, nextIsDark ? "1" : "0");
        applyTheme(nextIsDark); // aplica al instante
        reflectButtonsState();
      });
    }

    // Grises
    const bGray = $("#btn-grises");
    if (bGray && !bGray._wired) {
      bGray._wired = true;
      bGray.addEventListener("click", () => {
        const on = get("ac_gray") === "1";
        set("ac_gray", on ? "0" : "1");
        applyFromStorage();
      });
    }

    // Guía
    const bRuler = $("#btn-guia");
    if (bRuler && !bRuler._wired) {
      bRuler._wired = true;
      bRuler.addEventListener("click", () => {
        const on = get("ac_ruler") === "1";
        set("ac_ruler", on ? "0" : "1");
        applyFromStorage();
      });
    }

    // Tipografía (ciclo 0→1→2→0)
    const bFont = $("#btn-tipografia");
    if (bFont && !bFont._wired) {
      bFont._wired = true;
      bFont.addEventListener("click", cycleFont);
    }

    // Reset
    const bReset = $("#btn-reset");
    if (bReset && !bReset._wired) {
      bReset._wired = true;
      bReset.addEventListener("click", resetAccessibility);
    }

    // TTS
    const bTTS = $("#btn-lector");
    if (bTTS && !bTTS._wired) {
      bTTS._wired = true;
      bTTS.addEventListener("click", (e) => {
        e.preventDefault();
        toggleTTS(bTTS);
      });
    }
  }

  // Reset accesibilidad (siempre vuelve a CLARO)
  function resetAccessibility() {
    root.style.setProperty("--ac-contrast", "1");
    root.style.setProperty("--ac-font-scale", "1");
    root.style.setProperty("--ac-invert", "0");
    root.style.setProperty("--ac-gray", "0");

    // Tipografía -> índice 0 (Nunito)
    applyFontByIndex(0);

    // Quitar clases accesorias
    root.classList.remove("guia-lectura");

    // Limpiar storage (mantengo ac_font_index a 0)
    [
      "ac_contrast_val",
      "ac_font_scale",
      "ac_gray",
      "ac_ruler",
      "ac_fontface",
      "ac_fontface2",
      TTS_KEY,
    ].forEach(del);
    set("ac_font_index", "0");

    // Fuerza tema claro
    set(THEME_KEY, "0");
    applyTheme(false);

    reflectButtonsState();
    reflectSlidersState();
    try { speechSynthesis.cancel(); } catch (_) {}
  }

  // =============================
  // TTS robusto
  // =============================
  function getPageText() {
    try {
      const cloned = document.body.cloneNode(true);
      const panel = cloned.querySelector("#accesibilidad-panel");
      if (panel) panel.remove();
      cloned.querySelectorAll("script,style,noscript").forEach((n) => n.remove());
      return (cloned.innerText || "").trim();
    } catch (e) {
      return document.body.innerText || "";
    }
  }

  function awaitVoices(maxMs = 2500) {
    return new Promise((resolve) => {
      if (!("speechSynthesis" in window)) return resolve(false);
      const has = () => (speechSynthesis.getVoices() || []).length > 0;
      if (has()) return resolve(true);
      let done = false;
      const on = () => {
        if (!done && has()) {
          done = true;
          speechSynthesis.removeEventListener("voiceschanged", on);
          resolve(true);
        }
      };
      speechSynthesis.addEventListener("voiceschanged", on);
      setTimeout(() => {
        if (!done) {
          speechSynthesis.removeEventListener("voiceschanged", on);
          resolve(has());
        }
      }, maxMs);
    });
  }

  function pickSpanishVoice() {
    const vs = speechSynthesis.getVoices() || [];
    return (
      vs.find((v) => /es[-_]mx/i.test(v.lang)) ||
      vs.find((v) => /es[-_]es/i.test(v.lang)) ||
      vs.find((v) => /^es\b/i.test(v.lang)) ||
      null
    );
  }

  async function speakOnce(text) {
    if (!("speechSynthesis" in window)) return false;
    if (!text) return false;
    try {
      speechSynthesis.cancel();
      const ready = await awaitVoices(2500);
      const u = new SpeechSynthesisUtterance(text);
      const v = ready ? pickSpanishVoice() : null;
      u.lang = v?.lang || "es-MX";
      if (v) u.voice = v;
      u.rate = 1;
      u.pitch = 1;
      speechSynthesis.speak(u);
      return true;
    } catch (e) {
      console.warn("TTS error:", e);
      return false;
    }
  }

  async function retrySpeak(text, tries = 8, gapMs = 600) {
    for (let i = 0; i < tries; i++) {
      const ok = await speakOnce(text);
      if (ok) return true;
      await new Promise((res) => setTimeout(res, gapMs));
    }
    return false;
  }

  function toggleTTS(btn) {
    const on = get(TTS_KEY) === "auto";
    if (on) {
      set(TTS_KEY, "off");
      try { speechSynthesis.cancel(); } catch (_) {}
      if (btn) {
        btn.setAttribute("aria-pressed", "false");
        const sp = btn.querySelector("span");
        if (sp) sp.textContent = "Lector pantalla";
      }
    } else {
      set(TTS_KEY, "auto");
      if (btn) {
        btn.setAttribute("aria-pressed", "true");
        const sp = btn.querySelector("span");
        if (sp) sp.textContent = "Lector pantalla (AUTO)";
      }
      try { sessionStorage.setItem("ac_tts_seeded", "1"); } catch (_) {}
      retrySpeak(getPageText());
    }
  }

  function autoSpeakOnLoad() {
    const want = get(TTS_KEY) === "auto";
    let seeded = false;
    try { seeded = sessionStorage.getItem("ac_tts_seeded") === "1"; } catch (_) {}
    if (!want || !seeded) return;

    if (window.__ac_tts_autospoken__) return;
    window.__ac_tts_autospoken__ = true;

    retrySpeak(getPageText());
    document.addEventListener(
      "visibilitychange",
      () => {
        if (document.visibilityState === "visible" && !speechSynthesis.speaking) {
          retrySpeak(getPageText(), 3, 500);
        }
      },
      { once: true }
    );
  }

  // =============================
  // Init
  // =============================
  function init() {
    applyFromStorage();
    wireSlidersOnce();
    wireButtonsOnce();
    autoSpeakOnLoad();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init, { once: true });
  } else {
    init();
  }

  // Sync entre pestañas
  window.addEventListener("storage", (e) => {
    if (e.key && e.key.startsWith("ac_")) {
      if (e.key === THEME_KEY) {
        const isDark = get(THEME_KEY) === "1";
        applyTheme(isDark);
        reflectButtonsState();
      } else if (e.key === "ac_font_index" || e.key === "ac_fontface" || e.key === "ac_fontface2") {
        const idx = e.key === "ac_font_index"
          ? Number(get("ac_font_index") || 0)
          : getFontIndexFromLegacy();
        applyFontByIndex(idx);
      } else {
        applyFromStorage();
      }
      if (e.key === TTS_KEY) autoSpeakOnLoad();
    }
  });

  // Exponer helpers si los necesitas
  window.AccessibilityState = {
    apply: applyFromStorage,
    autoSpeakOnLoad,
    cycleFont, // opcional: por si quieres llamarlo desde fuera
  };
})();
