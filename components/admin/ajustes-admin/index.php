<!-- validacion de usuario -->
<?php include("/xampp/htdocs/php/polices.php"); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Panel Admin | Ajustes</title>

  <!-- Estilos del panel admin (incluye accesibilidad + sliders + reset) -->
  <link rel="stylesheet" href="/components/admin/admin-estilo.css">
  <link rel="icon" href="/icon.png" type="image/x-icon">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

  <!-- 🔧 Fijar que TODO use la variable de fuente (por si el CSS global aún fija "Nunito") -->
  <style>
    html, body, * { font-family: var(--font-family, "Nunito", sans-serif) !important; }
  </style>

  <!-- ✅ Boot de tema + accesibilidad + tipografía (ANTES de pintar) -->
  <script>
    (function () {
      var r = document.documentElement;
      try {
        // Tema por defecto: CLARO
        var dark = localStorage.getItem('ac_dark');
        if (dark === null || dark === undefined) {
          localStorage.setItem('ac_dark','0');
          dark = '0';
        }
        var isDark = dark === '1';
        r.classList.toggle('theme-dark', isDark);
        r.classList.toggle('theme-light', !isDark);
        // compatibilidad con estilos antiguos si existen
        r.classList.toggle('modo-nocturno', isDark);
        r.style.setProperty('--ac-invert', isDark ? '1' : '0');

        // Accesibilidad base
        var c = parseFloat(localStorage.getItem('ac_contrast_val'));
        if (isNaN(c)) c = (localStorage.getItem('ac_contrast') === '1') ? 1.6 : 1;
        var f = parseFloat(localStorage.getItem('ac_font_scale'));
        if (isNaN(f)) f = (localStorage.getItem('ac_font') === '1') ? 1.35 : 1;
        r.style.setProperty('--ac-contrast', String(Math.min(2, Math.max(0.5, c))));
        r.style.setProperty('--ac-font-scale', String(Math.min(1.6, Math.max(0.9, f))));
        r.style.setProperty('--ac-gray', localStorage.getItem('ac_gray') === '1' ? '1' : '0');
        if (localStorage.getItem('ac_ruler')    === '1') r.classList.add('guia-lectura');

        // 🔄 TIPOGRAFÍA por ÍNDICE (con compatibilidad a flags antiguos)
        var FONTS = [
          '"Nunito", sans-serif',
          '"Atkinson Hyperlegible", system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif',
          '"Merriweather Sans", system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif'
        ];
        function legacyToIndex(){
          var a = localStorage.getItem('ac_fontface')  === '1';
          var b = localStorage.getItem('ac_fontface2') === '1';
          if (a) return 1;
          if (b) return 2;
          return 0;
        }
        var idxStored = localStorage.getItem('ac_font_index');
        var idx = (idxStored !== null) ? parseInt(idxStored) : legacyToIndex();
        if (isNaN(idx) || idx < 0 || idx > 2) idx = 0;
        r.style.setProperty('--font-family', FONTS[idx]);     // ✅ aplica la fuente
        localStorage.setItem('ac_font_index', String(idx));   // persiste índice
        // sincroniza flags antiguos por compat
        localStorage.setItem('ac_fontface',  idx === 1 ? '1' : '0');
        localStorage.setItem('ac_fontface2', idx === 2 ? '1' : '0');
        // Quita clases antiguas si quedaron
        r.classList.remove('tipografia-alt','tipografia-alt2');

      } catch (e) {}
    })();
  </script>

  <!-- Booster TTS early: lectura automática en páginas cuando TTS=auto -->
  <script>
    (function(){
      const TTS_KEY = 'ac_tts';

      function seedOnce(){
        try{ sessionStorage.setItem('ac_tts_seeded','1'); }catch(_){}
        window.removeEventListener('pointerdown', seedOnce, {passive:true});
        window.removeEventListener('keydown', seedOnce);
      }
      window.addEventListener('pointerdown', seedOnce, {passive:true});
      window.addEventListener('keydown', seedOnce);

      function getPageText(){
        const parts = [];
        const q = s => document.querySelector(s);
        parts.push(q('.admin-sidebar, .main-menu, nav[role="navigation"]')?.innerText || '');
        parts.push(q('#accesibilidad-panel')?.innerText || '');
        parts.push(q('main, .container, .content')?.innerText || '');
        const joined = parts.filter(Boolean).join('\n\n').trim();
        return joined || (document.body.innerText || '').trim();
      }

      async function awaitVoices(maxMs=2500){
        return new Promise(res=>{
          if(!('speechSynthesis' in window)) return res(false);
          const has=()=> (speechSynthesis.getVoices()||[]).length>0;
          if(has()) return res(true);
          let done=false;
          const on=()=>{ if(!done && has()){ done=true; speechSynthesis.removeEventListener('voiceschanged',on); res(true);} };
          speechSynthesis.addEventListener('voiceschanged',on);
          setTimeout(()=>{ if(!done){ speechSynthesis.removeEventListener('voiceschanged',on); res(has()); }}, maxMs);
        });
      }
      function pickSpanishVoice(){
        const vs = speechSynthesis.getVoices()||[];
        return vs.find(v=>/es[-_]mx/i.test(v.lang)) || vs.find(v=>/es[-_]es/i.test(v.lang)) || vs.find(v=>/^es\b/i.test(v.lang)) || null;
      }
      async function speakNow(text){
        if(!('speechSynthesis' in window) || !text) return false;
        try{
          speechSynthesis.cancel();
          const ready = await awaitVoices(2200);
          const u = new SpeechSynthesisUtterance(text);
          const v = ready ? pickSpanishVoice() : null;
          u.lang = v?.lang || 'es-MX';
          if (v) u.voice = v;
          u.rate = 1; u.pitch = 1;
          speechSynthesis.speak(u);
          return true;
        }catch(e){ return false; }
      }
      async function retrySpeak(text, tries=6, gap=500){
        for(let i=0;i<tries;i++){
          const ok = await speakNow(text);
          if(ok) return true;
          await new Promise(r=>setTimeout(r,gap));
        }
        return false;
      }
      function autoSpeakHere(){
        try{
          const want   = localStorage.getItem(TTS_KEY)==='auto';
          const seeded = sessionStorage.getItem('ac_tts_seeded')==='1';
          if(!want || !seeded) return;
          retrySpeak(getPageText());
        }catch(_){}
      }
      if(document.readyState==='loading'){
        document.addEventListener('DOMContentLoaded', autoSpeakHere, {once:true});
      }else{
        autoSpeakHere();
      }
    })();
  </script>
</head>

<!-- sin class="darkmode" -->
<body>
  <!-- Navbar dinámico -->
  <div id="navbar-container"></div>

  <!-- Panel Accesibilidad -->
  <nav id="accesibilidad-panel" aria-label="Herramientas de accesibilidad">
    <ul class="ac-grid">
      <!-- Contraste (slider) -->
      <li>
        <div class="ac-card" role="group" aria-labelledby="lbl-contraste">
          <i class='bx bx-adjust' aria-hidden="true"></i>
          <div>
            <div id="lbl-contraste">Contraste</div>
            <div class="ac-field">
              <input id="range-contrast" class="ac-range" type="range" min="0.5" max="2" step="0.05" value="1"
                     aria-label="Ajustar contraste (0.5 a 2)">
              <span id="val-contrast" class="ac-value">1.00×</span>
            </div>
          </div>
        </div>
      </li>

      <!-- Tamaño de letra (slider) -->
      <li>
        <div class="ac-card" role="group" aria-labelledby="lbl-fuente">
          <i class='bx bx-font' aria-hidden="true"></i>
          <div>
            <div id="lbl-fuente">Tamaño de letra</div>
            <div class="ac-field">
              <input id="range-font" class="ac-range" type="range" min="0.9" max="1.6" step="0.02" value="1"
                     aria-label="Ajustar tamaño de letra (0.9 a 1.6)">
              <span id="val-font" class="ac-value">1.00×</span>
            </div>
          </div>
        </div>
      </li>

      <!-- Modo nocturno -->
      <li>
        <button type="button" class="ac-card" id="btn-nocturno" data-pref="dark" aria-pressed="false">
          <i class='bx bx-moon' aria-hidden="true"></i>
          <span>Modo nocturno</span>
        </button>
      </li>

      <!-- Escala de grises -->
      <li>
        <button type="button" class="ac-card" id="btn-grises" data-pref="gray" aria-pressed="false">
          <i class='bx bx-image' aria-hidden="true"></i>
          <span>Escala de grises</span>
        </button>
      </li>

      <!-- Guía de lectura -->
      <li>
        <button type="button" class="ac-card" id="btn-guia" data-pref="ruler" aria-pressed="false">
          <i class='bx bx-book-open' aria-hidden="true"></i>
          <span>Guía de lectura</span>
        </button>
      </li>

      <!-- Tipografía (usa índice 0/1/2) -->
      <li>
        <button type="button" class="ac-card" id="btn-tipografia" data-pref="fontface" aria-pressed="false">
          <i class='bx bx-text' aria-hidden="true"></i>
          <span id="lbl-tipografia">Tipografía</span>
        </button>
      </li>

      <!-- Lector pantalla (global AUTO/Off) -->
      <li>
        <button type="button" class="ac-card" id="btn-lector" data-pref="tts" aria-pressed="false">
          <i class='bx bx-volume-full' aria-hidden="true"></i>
          <span>Lector pantalla</span>
        </button>
      </li>

      <!-- Restablecer -->
      <li>
        <button type="button" class="ac-reset" id="btn-reset">
          <i class='bx bx-reset' aria-hidden="true"></i>
          <span>Restablecer</span>
        </button>
      </li>
    </ul>
  </nav>

  <!-- Scripts comunes -->
  <script src="/js/main-navbar-admin.js" defer></script>
  <script src="/js/accesibilidad-state.js" defer></script>

  <!-- Accesibilidad: sliders + toggles + TTS + reset; el botón Tipografía llama al ciclo global -->
  <script>
    (function(){
      const root = document.documentElement;
      const $  = sel => document.querySelector(sel);
      const get = k => localStorage.getItem(k);
      const set = (k,v) => localStorage.setItem(k, v);
      const del = k => localStorage.removeItem(k);

      // Sliders
      const rangeContrast = $('#range-contrast');
      const rangeFont     = $('#range-font');
      const valContrast   = $('#val-contrast');
      const valFont       = $('#val-font');

      function fmt(x){ return (Math.round(x * 100) / 100).toFixed(2); }
      function applyContrast(v){
        const value = Math.min(2, Math.max(0.5, Number(v) || 1));
        root.style.setProperty('--ac-contrast', String(value));
        if (rangeContrast) rangeContrast.value = value;
        if (valContrast)   valContrast.textContent = fmt(value) + '×';
        set('ac_contrast_val', String(value));
      }
      function applyFontScale(v){
        const value = Math.min(1.6, Math.max(0.9, Number(v) || 1));
        root.style.setProperty('--ac-font-scale', String(value));
        if (rangeFont) rangeFont.value = value;
        if (valFont)   valFont.textContent = fmt(value) + '×';
        set('ac_font_scale', String(value));
      }
      rangeContrast?.addEventListener('input', e => applyContrast(e.target.value));
      rangeFont?.addEventListener('input',     e => applyFontScale(e.target.value));

      // Toggles
      const btnNocturno   = document.getElementById('btn-nocturno');
      const btnGrises     = document.getElementById('btn-grises');
      const btnGuia       = document.getElementById('btn-guia');
      const btnTipografia = document.getElementById('btn-tipografia');
      const lblTipog      = document.getElementById('lbl-tipografia');
      const btnTTS        = document.getElementById('btn-lector');
      const btnReset      = document.getElementById('btn-reset');

      function setPressed(btn, on){ btn?.setAttribute('aria-pressed', on ? 'true' : 'false'); }

      function applyThemeClasses(isDark){
        root.classList.toggle('theme-dark', isDark);
        root.classList.toggle('theme-light', !isDark);
        root.classList.toggle('modo-nocturno', isDark); // compat
        root.style.setProperty('--ac-invert', isDark ? '1' : '0');
      }

      function toggleDark(){
        const on = get('ac_dark') === '1';
        const next = !on;
        set('ac_dark', next ? '1' : '0');
        applyThemeClasses(next);
        setPressed(btnNocturno, next);
      }

      function toggleGray(){
        const on = get('ac_gray') === '1';
        root.style.setProperty('--ac-gray', !on ? '1' : '0');
        set('ac_gray', on ? '0':'1'); setPressed(btnGrises, !on);
      }
      function toggleRuler(){
        const on = get('ac_ruler') === '1';
        root.classList.toggle('guia-lectura', !on);
        set('ac_ruler', on ? '0':'1'); setPressed(btnGuia, !on);
      }

      // Tipografía: usar el ciclo centralizado de /js/accesibilidad-state.js
      function refreshFontLabel(){
        const idx = parseInt(localStorage.getItem('ac_font_index')) || 0;
        if (!lblTipog) return;
        lblTipog.textContent =
          idx === 0 ? 'Tipografía (Normal)' :
          idx === 1 ? 'Tipografía (Atkinson)' :
                      'Tipografía (Merriweather Sans)';
        setPressed(btnTipografia, idx !== 0);
      }
      btnTipografia?.addEventListener('click', function(){
        // usa la API que expone accesibilidad-state.js
        if (window.AccessibilityState?.cycleFont) {
          window.AccessibilityState.cycleFont();
          refreshFontLabel();
        } else {
          // Fallback: ciclo local por índice si por alguna razón no cargó el script global
          const FONTS = [
            '"Nunito", sans-serif',
            '"Atkinson Hyperlegible", system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif',
            '"Merriweather Sans", system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif'
          ];
          let i = parseInt(localStorage.getItem('ac_font_index')) || 0;
          i = (i + 1) % FONTS.length;
          document.documentElement.style.setProperty('--font-family', FONTS[i]);
          localStorage.setItem('ac_font_index', String(i));
          localStorage.setItem('ac_fontface',  i===1 ? '1':'0');
          localStorage.setItem('ac_fontface2', i===2 ? '1':'0');
          refreshFontLabel();
        }
      });

      // Lector de pantalla global (AUTO/Off) + fallback inmediato en esta página
      const TTS_KEY = 'ac_tts';
      function refreshTTSButton(){
        const on = get(TTS_KEY) === 'auto';
        setPressed(btnTTS, on);
        const label = btnTTS?.querySelector('span');
        if (label) label.textContent = on ? 'Lector pantalla (AUTO)' : 'Lector pantalla';
      }
      function toggleTTS(){
        const on = get(TTS_KEY) === 'auto';
        if (on) {
          set(TTS_KEY, 'off');
          try{ speechSynthesis.cancel(); }catch(_){}
        } else {
          set(TTS_KEY, 'auto');
          try{ sessionStorage.setItem('ac_tts_seeded','1'); }catch(_){}
          // Fallback: leer de inmediato esta pantalla de Ajustes
          (function fallbackNow(){
            function getPageText(){
              const parts = [];
              const q = s => document.querySelector(s);
              parts.push(q('.admin-sidebar, .main-menu, nav[role="navigation"]')?.innerText || '');
              parts.push(q('#accesibilidad-panel')?.innerText || '');
              parts.push(q('main, .container, .content')?.innerText || '');
              const joined = parts.filter(Boolean).join('\n\n').trim();
              return joined || (document.body.innerText || '').trim();
            }
            try{
              speechSynthesis.cancel();
              const u = new SpeechSynthesisUtterance(getPageText());
              u.lang = 'es-MX';
              speechSynthesis.speak(u);
            }catch(_){}
          })();
        }
        refreshTTSButton();
      }

      btnNocturno ?.addEventListener('click', toggleDark);
      btnGrises   ?.addEventListener('click', toggleGray);
      btnGuia     ?.addEventListener('click', toggleRuler);
      btnTTS      ?.addEventListener('click', (e)=>{ e.preventDefault(); toggleTTS(); });

      // Restablecer
      function resetAccessibility(){
        applyContrast(1);
        applyFontScale(1);
        root.style.setProperty('--ac-gray', '0');

        // Tema: volver a claro
        set('ac_dark','0');
        applyThemeClasses(false);

        root.classList.remove('guia-lectura');

        // Tipografía -> índice 0 (Nunito)
        document.documentElement.style.setProperty('--font-family','"Nunito", sans-serif');
        localStorage.setItem('ac_font_index','0');
        localStorage.setItem('ac_fontface','0');
        localStorage.setItem('ac_fontface2','0');
        refreshFontLabel();

        setPressed(btnNocturno,   false);
        setPressed(btnGrises,     false);
        setPressed(btnGuia,       false);
        setPressed(btnTipografia, false);

        ['ac_contrast_val','ac_font_scale','ac_gray','ac_ruler','ac_tts'].forEach(del);

        refreshTTSButton();
      }
      btnReset?.addEventListener('click', resetAccessibility);

      // Inicialización
      (function init(){
        if (get('ac_dark') === null) set('ac_dark','0');
        const isDark = get('ac_dark') === '1';
        applyThemeClasses(isDark);
        setPressed(btnNocturno, isDark);

        const c = Number(get('ac_contrast_val') || '1');
        const f = Number(get('ac_font_scale')   || '1');
        applyContrast(c);
        applyFontScale(f);

        if (get('ac_gray')  === '1') { root.style.setProperty('--ac-gray','1'); setPressed(btnGrises, true); }
        if (get('ac_ruler') === '1') { root.classList.add('guia-lectura');      setPressed(btnGuia,   true); }

        refreshFontLabel();
        refreshTTSButton();
      })();

      // Atajo de teclado: Alt + 0 -> Restablecer
      document.addEventListener('keydown', (e) => {
        if (e.altKey && e.key === '0') {
          e.preventDefault();
          resetAccessibility();
        }
      });
    })();
  </script>
</body>
</html>
