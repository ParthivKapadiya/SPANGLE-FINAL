/**
 * Sets data-public-base for XAMPP / localhost before deferred site scripts run.
 */
(function () {
  'use strict';

  var host = window.location.hostname;
  if (host !== 'localhost' && host !== '127.0.0.1') {
    return;
  }

  var root = document.documentElement;
  var scripts = document.getElementsByTagName('script');
  var i;
  var src;
  var base;

  for (i = scripts.length - 1; i >= 0; i--) {
    src = scripts[i].getAttribute('src') || '';
    if (src.indexOf('content-bridge') !== -1 || src.indexOf('site-data.js') !== -1) {
      try {
        base = new URL(src, window.location.href).href.replace(
          /\/(js\/content-bridge\.js|api\/site-data\.js\.php)(\?.*)?$/i,
          ''
        );
        root.setAttribute('data-public-base', base);
        return;
      } catch (e) {
        break;
      }
    }
  }

  base = window.location.origin + window.location.pathname.replace(/\/[^/]*$/, '');
  root.setAttribute('data-public-base', base.replace(/\/$/, ''));
})();
