/* ============================================================
   Anger Tools — navigation behavior (am-nav.js)
   ------------------------------------------------------------
   Add to every page (deferred):

     <script src="extra/js/am-nav.js" defer></script>

   1. Opens/closes the mobile menu (button + Escape + outside click)
   2. Marks the active link based on the ?p= query parameter
   3. Fills the date pill in the nav (client-side fallback —
      if your PHP already prints the date there, it is kept)
   ============================================================ */

(function () {
  'use strict';

  var nav = document.querySelector('.am-nav');
  if (!nav) return;

  var toggle = nav.querySelector('.am-nav-toggle');
  var menu = nav.querySelector('.am-nav-menu');

  /* ---------- Mobile menu ---------- */
  function closeMenu() {
    nav.classList.remove('am-nav--open');
    document.body.classList.remove('am-menu-open');
    if (toggle) toggle.setAttribute('aria-expanded', 'false');
  }

  if (toggle && menu) {
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('am-nav--open');
      document.body.classList.toggle('am-menu-open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    document.addEventListener('click', function (e) {
      if (nav.classList.contains('am-nav--open') && !nav.contains(e.target)) {
        closeMenu();
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && nav.classList.contains('am-nav--open')) {
        closeMenu();
        toggle.focus();
      }
    });

    // If the viewport grows past the mobile breakpoint while the
    // menu is open, close it so no scrim or state is left behind
    var mq = window.matchMedia('(min-width: 881px)');
    var onBreakpoint = function () {
      if (mq.matches) closeMenu();
    };
    if (mq.addEventListener) {
      mq.addEventListener('change', onBreakpoint);
    } else if (mq.addListener) {
      mq.addListener(onBreakpoint);
    }
  }

  /* ---------- Active link (matches the ?p= route) ---------- */
  var p = '';
  try {
    p = new URLSearchParams(window.location.search).get('p') || '';
  } catch (err) {
    p = (window.location.search.match(/[?&]p=([^&]+)/) || [])[1] || '';
  }

  var links = nav.querySelectorAll('a.am-nav-link');
  Array.prototype.forEach.call(links, function (a) {
    var href = a.getAttribute('href') || '';
    var linkP = (href.match(/[?&]p=([^&]+)/) || [])[1] || '';
    var isHome = a.classList.contains('am-nav-link--home');

    var active = (p === '' && isHome) || (p !== '' && linkP === p);
    a.classList.toggle('active', active);
    if (active) {
      a.setAttribute('aria-current', 'page');
    } else {
      a.removeAttribute('aria-current');
    }
  });

  /* ---------- Date pill fallback ----------
     If your PHP already echoes the date inside .am-nav-time,
     this does nothing. Otherwise it fills today's date. */
  var timeEl = nav.querySelector('.am-nav-time');
  if (timeEl && !timeEl.textContent.trim()) {
    timeEl.textContent = new Date().toLocaleDateString('en-US', {
      month: 'long',
      day: 'numeric',
      year: 'numeric',
      timeZone: 'America/Denver'
    });
  }
})();

/* ---------- Persistent light/dark theme ---------- */
(function () {
  'use strict';
  var root = document.documentElement;
  var button = document.querySelector('.am-theme-toggle');
  if (!button) return;
  var label = button.querySelector('.am-theme-label');
  var icon = button.querySelector('.am-theme-icon');
  var saved = null;
  try { saved = window.localStorage.getItem('anger-tools-theme'); } catch (err) {}
  var systemDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  var theme = saved === 'light' || saved === 'dark' ? saved : (systemDark ? 'dark' : 'light');

  function applyTheme(nextTheme) {
    theme = nextTheme;
    root.setAttribute('data-theme', theme);
    var isDark = theme === 'dark';
    button.setAttribute('aria-pressed', isDark ? 'true' : 'false');
    button.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
    if (label) label.textContent = isDark ? 'Light mode' : 'Dark mode';
    if (icon) icon.textContent = isDark ? '☀' : '☾';
  }
  applyTheme(theme);
  button.addEventListener('click', function () {
    var nextTheme = theme === 'dark' ? 'light' : 'dark';
    applyTheme(nextTheme);
    try { window.localStorage.setItem('anger-tools-theme', nextTheme); } catch (err) {}
  });
})();
