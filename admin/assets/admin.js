(function () {
  'use strict';

  var root = document.documentElement;
  var app = document.getElementById('adm-app') || document.body;
  var themeBtn = document.getElementById('adm-theme-toggle');
  var menuBtn = document.getElementById('adm-menu-toggle');
  var sidebar = document.getElementById('adm-sidebar');
  var backdrop = document.getElementById('adm-sidebar-backdrop');

  var saved = localStorage.getItem('adm_theme');
  if (saved === 'light') {
    root.setAttribute('data-adm-theme', 'light');
  } else {
    root.setAttribute('data-adm-theme', 'dark');
    if (!saved) localStorage.setItem('adm_theme', 'dark');
  }

  function syncThemeIcon() {
    if (!themeBtn) return;
    var icon = themeBtn.querySelector('i');
    if (!icon) return;
    var isDark = root.getAttribute('data-adm-theme') === 'dark';
    icon.className = isDark ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
  }
  syncThemeIcon();

  function isMobile() {
    return window.matchMedia('(max-width: 900px)').matches;
  }

  var storedSidebar = localStorage.getItem('adm_sidebar');
  var sidebarOpen = isMobile()
    ? false
    : (storedSidebar === null || storedSidebar === 'open');
  if (sidebarOpen) {
    app.classList.add('is-sidebar-open');
    if (menuBtn) menuBtn.setAttribute('aria-expanded', 'true');
  }

  function setSidebarOpen(open) {
    app.classList.toggle('is-sidebar-open', open);
    if (menuBtn) {
      menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
      menuBtn.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    }
    if (backdrop) {
      if (open && isMobile()) {
        backdrop.hidden = false;
        backdrop.classList.add('is-visible');
        backdrop.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
      } else {
        backdrop.classList.remove('is-visible');
        backdrop.setAttribute('aria-hidden', 'true');
        backdrop.hidden = true;
        document.body.style.overflow = '';
      }
    }
    if (!isMobile()) {
      localStorage.setItem('adm_sidebar', open ? 'open' : 'closed');
    }
  }

  function toggleSidebar() {
    setSidebarOpen(!app.classList.contains('is-sidebar-open'));
  }

  if (themeBtn) {
    themeBtn.addEventListener('click', function () {
      var next = root.getAttribute('data-adm-theme') === 'dark' ? 'light' : 'dark';
      root.setAttribute('data-adm-theme', next);
      localStorage.setItem('adm_theme', next);
      syncThemeIcon();
    });
  }

  if (menuBtn && sidebar) {
    menuBtn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      toggleSidebar();
    });
  }

  if (backdrop) {
    backdrop.addEventListener('click', function () {
      setSidebarOpen(false);
    });
  }

  if (sidebar) {
    sidebar.querySelectorAll('.adm-nav-link, .adm-sidebar-foot a').forEach(function (link) {
      link.addEventListener('click', function () {
        if (isMobile()) setSidebarOpen(false);
      });
    });
  }

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && app.classList.contains('is-sidebar-open')) {
      setSidebarOpen(false);
    }
  });

  window.addEventListener('resize', function () {
    if (!isMobile() && backdrop) {
      backdrop.classList.remove('is-visible');
      backdrop.hidden = true;
      document.body.style.overflow = '';
    }
    if (isMobile() && !app.classList.contains('is-sidebar-open')) {
      document.body.style.overflow = '';
    }
  });

  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      var msg = form.getAttribute('data-confirm') || 'Are you sure?';
      if (!window.confirm(msg)) e.preventDefault();
    });
  });
})();
