/**
 * Progressive enhancement only — every action also works without JS.
 */
(function () {
  'use strict';

  var root = document.querySelector('.app-root');
  if (!root) { return; }

  // Base URL of the app, derived from this script's own src (…/assets/app.js).
  var appBase = (function () {
    var src = (document.currentScript && document.currentScript.src) || '';
    return src ? src.replace(/assets\/app\.js.*$/, '') : location.pathname;
  })();

  /* ---------------------------------------------------------- theme */

  var STORE = 'rvcman-theme';

  function applyTheme(value) {
    if (value === 'dark' || value === 'light') {
      document.documentElement.setAttribute('data-theme', value);
    } else {
      document.documentElement.removeAttribute('data-theme');
    }
  }

  function currentTheme() {
    try { return localStorage.getItem(STORE) || 'auto'; } catch (e) { return 'auto'; }
  }

  applyTheme(currentTheme());

  root.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-action="toggle-theme"]');
    if (!btn) { return; }

    var systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    var now = currentTheme();
    var next;
    if (now === 'auto') { next = systemDark ? 'light' : 'dark'; }
    else if (now === 'dark') { next = 'light'; }
    else { next = 'dark'; }

    try { localStorage.setItem(STORE, next); } catch (err) {}
    applyTheme(next);
  });

  /* ------------------------------------------------ print and share */

  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-action="print"]')) {
      window.print();
      return;
    }

    var share = e.target.closest('[data-action="share"]');
    if (share) {
      var payload = { title: share.getAttribute('data-title') || document.title, url: location.href };
      if (navigator.share) {
        navigator.share(payload).catch(function () {});
      } else if (navigator.clipboard) {
        navigator.clipboard.writeText(location.href).then(function () {
          toast('คัดลอกลิงก์แล้ว');
        });
      }
    }
  });

  /* ----------------------------------------- destructive confirmation */

  document.addEventListener('submit', function (e) {
    var form = e.target;
    var message = form.getAttribute('data-confirm');
    if (message && !window.confirm(message)) {
      e.preventDefault();
    }
  });

  /* ------------------------------------- repeatable rows in admin forms */

  document.addEventListener('click', function (e) {
    var add = e.target.closest('[data-repeat-add]');
    if (add) {
      var name = add.getAttribute('data-repeat-add');
      var host = document.querySelector('[data-repeat="' + name + '"]');
      var tpl = host && host.querySelector('[data-repeat-template]');
      if (tpl) {
        host.insertBefore(tpl.content.cloneNode(true), tpl);
      }
      return;
    }

    var remove = e.target.closest('[data-repeat-remove]');
    if (remove) {
      var row = remove.closest('[data-repeat-item]');
      if (row) { row.remove(); }
    }
  });

  /* -------------------------------------------------------- toast */

  function toast(message) {
    var el = document.createElement('div');
    el.textContent = message;
    el.style.cssText =
      'position:fixed;left:50%;bottom:calc(var(--tabbar-h) + 16px);transform:translateX(-50%);' +
      'background:var(--text);color:var(--bg);padding:10px 18px;border-radius:999px;' +
      'font-size:.85rem;z-index:100;box-shadow:var(--shadow-lg)';
    document.body.appendChild(el);
    setTimeout(function () { el.remove(); }, 2200);
  }

  /* ------------------------------------------------ offline support */

  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register(appBase + 'sw.js', { scope: appBase }).catch(function () {});
    });
  }
})();
