/* Vehicle Tracker - frontend behavior (vanilla JS) */

(() => {
  'use strict';

  const THEME_KEY = 'vt-theme';
  const API = 'api';

  // ---------- theme ----------
  function getSavedTheme() {
    try {
      return localStorage.getItem(THEME_KEY);
    } catch (e) {
      return null;
    }
  }

  function setTheme(theme) {
    const root = document.documentElement;
    if (theme === 'dark') {
      root.setAttribute('data-theme', 'dark');
      localStorage.setItem(THEME_KEY, 'dark');
    } else if (theme === 'light') {
      root.removeAttribute('data-theme');
      localStorage.setItem(THEME_KEY, 'light');
    } else {
      // system preference
      const dark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
      if (dark) root.setAttribute('data-theme', 'dark');
      else root.removeAttribute('data-theme');
      localStorage.removeItem(THEME_KEY);
    }
    updateThemeIcon();
  }

  function updateThemeIcon() {
    const btn = document.getElementById('themeToggle');
    if (!btn) return;
    const dark = document.documentElement.getAttribute('data-theme') === 'dark';
    btn.textContent = dark ? 'Light mode' : 'Dark mode';
    btn.title = dark ? 'Switch to light mode' : 'Switch to dark mode';
  }

  function toggleTheme() {
    const dark = document.documentElement.getAttribute('data-theme') === 'dark';
    setTheme(dark ? 'light' : 'dark');
  }

  // ---------- init ----------
  function init() {
    const saved = getSavedTheme();
    setTheme(saved || 'system');

    const themeBtn = document.getElementById('themeToggle');
    if (themeBtn) themeBtn.addEventListener('click', toggleTheme);

    // Highlight current nav link
    const path = window.location.pathname;
    document.querySelectorAll('.nav a').forEach((a) => {
      const href = a.getAttribute('href');
      if (href && path.endsWith(href) && href !== 'index.php') {
        a.classList.add('active');
      }
      if (href === 'index.php' && path.endsWith('/') && path.indexOf('index.php') === -1) {
        a.classList.add('active');
      }
    });

    // Delete confirmations
    document.querySelectorAll('[data-delete]').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        if (!confirm(btn.dataset.deleteConfirm || 'Delete this item?')) e.preventDefault();
      });
    });

    // Auto-hide flashes
    setTimeout(() => {
      document.querySelectorAll('.flash').forEach((f) => {
        f.style.opacity = '0';
        setTimeout(() => f.remove(), 300);
      });
    }, 5000);

    // Table row select / highlight (visual only)
    document.querySelectorAll('table tbody tr').forEach((tr) => {
      tr.addEventListener('click', (e) => {
        if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON') return;
        document.querySelectorAll('table tbody tr').forEach((r) => r.classList.remove('ring'));
        tr.classList.add('ring');
      });
    });
  }

  // ---------- toast ----------
  function toast(msg, type = 'info') {
    let el = document.getElementById('vtToast');
    if (!el) {
      el = document.createElement('div');
      el.id = 'vtToast';
      document.body.appendChild(el);
    }
    el.className = `toast ${type}`;
    el.textContent = msg;
    el.classList.add('show');
    setTimeout(() => {
      el.classList.remove('show');
    }, 3000);
  }

  // ---------- JSON fetch helper ----------
  async function apiRequest(url, { method = 'GET', body, idempotencyKey } = {}) {
    const headers = { Accept: 'application/json' };
    const token = localStorage.getItem('vt_token');
    if (token) headers['Authorization'] = 'Bearer ' + token;
    if (body) headers['Content-Type'] = 'application/json';
    if (idempotencyKey) headers['Idempotency-Key'] = idempotencyKey;

    const res = await fetch(url, { method, headers, body: body ? JSON.stringify(body) : undefined });
    let data;
    const ct = res.headers.get('content-type') || '';
    if (ct.indexOf('application/json') !== -1) data = await res.json();
    else data = { text: await res.text() };
    return { ok: res.ok, status: res.status, data };
  }

  // ---------- expose ----------
  window.VT = { apiRequest, toast, setTheme, getSavedTheme };

  // ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
