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

  var navSearch = document.getElementById('adm-nav-search');
  var nav = document.getElementById('adm-nav');
  if (navSearch && nav) {
    navSearch.addEventListener('input', function () {
      var q = navSearch.value.trim().toLowerCase();
      nav.querySelectorAll('.adm-nav-section').forEach(function (section) {
        var visible = 0;
        var next = section.nextElementSibling;
        while (next && !next.classList.contains('adm-nav-section')) {
          if (next.classList.contains('adm-nav-link')) {
            var label = (next.getAttribute('data-nav-label') || next.textContent || '').toLowerCase();
            var show = q === '' || label.indexOf(q) !== -1;
            next.classList.toggle('is-hidden', !show);
            if (show) visible++;
          }
          next = next.nextElementSibling;
        }
        section.classList.toggle('is-hidden', q !== '' && visible === 0);
      });
    });
  }
  function admApplyLimitSelection(checks, limit) {
    var selected = checks.filter(function (c) { return c.checked; });
    var unselected = checks.filter(function (c) { return !c.checked; });
    checks.forEach(function (c) { c.checked = false; });
    var picks = selected.slice(0, limit);
    if (picks.length < limit) {
      picks = picks.concat(unselected.slice(0, limit - picks.length));
    }
    picks.forEach(function (c) { c.checked = true; });
  }

  var bulkPickerSync = new WeakMap();

  function admGalleryCounts(bulkForm) {
    var bulkGrid = bulkForm.querySelector('[data-bulk-grid]');
    var checks = bulkGrid
      ? Array.prototype.slice.call(bulkGrid.querySelectorAll('.adm-bulk-check'))
      : [];
    return {
      total: checks.length,
      selected: checks.filter(function (c) { return c.checked; }).length,
      requested: parseInt(bulkForm.getAttribute('data-bulk-limit') || '0', 10) || 0
    };
  }

  function admUpdateGalleryHint(bulkForm) {
    var hint = document.getElementById('adm-gallery-limit-hint');
    if (!hint || !bulkForm || bulkForm.id !== 'adm-gallery-bulk-form') return;

    var counts = admGalleryCounts(bulkForm);
    var total = counts.total;
    var selected = counts.selected;
    var requested = counts.requested;

    if (total === 0) {
      hint.textContent = 'No gallery images yet — upload some first.';
      return;
    }
    if (selected === total) {
      if (requested > total) {
        hint.textContent = 'All ' + total + ' images selected. You chose ' + requested + ' for home — add more photos or set home limit to ' + total + '.';
      } else if (requested > 0 && selected === requested) {
        hint.textContent = 'All ' + total + ' images selected (' + requested + ' for home). Choose an action below, then Apply.';
      } else {
        hint.textContent = 'All ' + total + ' images selected. Choose an action below, then Apply.';
      }
      return;
    }
    if (selected === 0) {
      hint.textContent = 'Tap a number to select that many images, or All to select every image.';
      return;
    }
    if (requested > total) {
      hint.textContent = selected + ' of ' + total + ' selected. You chose ' + requested + ' — only ' + total + ' image' + (total === 1 ? '' : 's') + ' available.';
      return;
    }
    if (requested > 0 && selected < requested) {
      hint.textContent = selected + ' of ' + requested + ' selected — tap ' + requested + ' or Select all to pick ' + Math.min(requested, total) + '.';
      return;
    }
    hint.textContent = selected + ' image' + (selected === 1 ? '' : 's') + ' selected — choose an action below, then Apply.';
  }

  function admActivateGalleryLimit(limit, picker, selectAllImages) {
    var input = document.getElementById('gallery_select_limit');
    var bulkForm = document.getElementById('adm-gallery-bulk-form');
    if (!input || !bulkForm) return;

    var bulkGrid = bulkForm.querySelector('[data-bulk-grid]');
    var checks = bulkGrid
      ? Array.prototype.slice.call(bulkGrid.querySelectorAll('.adm-bulk-check'))
      : [];
    var total = checks.length;

    if (selectAllImages || limit === 'all') {
      checks.forEach(function (c) { c.checked = true; });
      if (picker) {
        picker.querySelectorAll('.adm-limit-btn').forEach(function (b) {
          b.classList.toggle('is-active', b.getAttribute('data-value') === 'all');
        });
      }
    } else {
      limit = parseInt(String(limit), 10);
      if (!limit || limit < 4) limit = 4;
      if (limit > 24) limit = 24;
      input.value = String(limit);
      bulkForm.setAttribute('data-bulk-limit', String(limit));
      admApplyLimitSelection(checks, limit);
      if (picker) {
        picker.querySelectorAll('.adm-limit-btn').forEach(function (b) {
          var v = b.getAttribute('data-value') || '';
          b.classList.toggle('is-active', v !== 'all' && parseInt(v, 10) === limit);
        });
      }
      var customInput = document.getElementById('gallery_select_custom');
      if (customInput) {
        customInput.value = String(limit);
      }
    }

    var sync = bulkPickerSync.get(bulkForm);
    if (typeof sync === 'function') {
      sync();
    }
    admUpdateGalleryHint(bulkForm);
  }

  var galleryCustomBtn = document.getElementById('gallery_select_custom_btn');
  var galleryCustomInput = document.getElementById('gallery_select_custom');
  if (galleryCustomBtn && galleryCustomInput) {
    galleryCustomBtn.addEventListener('click', function () {
      var picker = document.querySelector('.adm-limit-picker--gallery');
      admActivateGalleryLimit(galleryCustomInput.value, picker);
    });
    galleryCustomInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        galleryCustomBtn.click();
      }
    });
  }

  document.querySelectorAll('.adm-limit-picker').forEach(function (picker) {
    var input = document.getElementById(picker.getAttribute('data-target') || '');
    if (!input) return;
    var projectsFormId = picker.getAttribute('data-projects-form');
    var projectsForm = projectsFormId ? document.getElementById(projectsFormId) : null;
    var bulkFormId = picker.getAttribute('data-bulk-form');
    var bulkForm = bulkFormId ? document.getElementById(bulkFormId) : null;
    picker.querySelectorAll('.adm-limit-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var rawValue = btn.getAttribute('data-value') || '';
        var limit = rawValue === 'all' ? 'all' : parseInt(rawValue, 10);
        if (rawValue !== 'all') {
          input.value = rawValue;
        }
        if (projectsForm && rawValue !== 'all') {
          picker.querySelectorAll('.adm-limit-btn').forEach(function (b) {
            b.classList.toggle('is-active', b === btn);
          });
          projectsForm.setAttribute('data-project-limit', input.value);
          if (typeof window.admSyncHomeProjectsPicker === 'function') {
            window.admSyncHomeProjectsPicker(projectsForm, true);
          }
        }
        if (bulkForm && bulkForm.hasAttribute('data-bulk-picker') && bulkForm.id === 'adm-gallery-bulk-form') {
          admActivateGalleryLimit(limit, picker, rawValue === 'all');
        } else if (bulkForm && bulkForm.hasAttribute('data-bulk-picker')) {
          picker.querySelectorAll('.adm-limit-btn').forEach(function (b) {
            b.classList.toggle('is-active', b === btn);
          });
          bulkForm.setAttribute('data-bulk-limit', input.value);
          var bulkGrid = bulkForm.querySelector('[data-bulk-grid]');
          if (bulkGrid && limit > 0) {
            var checks = Array.prototype.slice.call(bulkGrid.querySelectorAll('.adm-bulk-check'));
            admApplyLimitSelection(checks, limit);
          }
          var sync = bulkPickerSync.get(bulkForm);
          if (typeof sync === 'function') {
            sync();
          }
        }
      });
    });
  });

  function admHomeProjectsLimit(form) {
    var limit = parseInt(form.getAttribute('data-project-limit') || '8', 10);
    if (!limit || limit < 4) limit = 4;
    if (limit > 12) limit = 12;
    return limit;
  }

  function admApplyProjectLimitSelection(checks, limit) {
    admApplyLimitSelection(checks, limit);
  }

  function admSyncHomeProjectsPicker(form, limitChanged) {
    var list = form.querySelector('#adm-home-feature-list');
    if (!list) return;
    var limit = admHomeProjectsLimit(form);
    var status = form.querySelector('#adm-home-projects-status');
    var stepHint = form.querySelector('#adm-home-projects-step-hint');
    var checks = Array.prototype.slice.call(list.querySelectorAll('.adm-home-feature-check'));

    if (limitChanged) {
      admApplyProjectLimitSelection(checks, limit);
    }

    var checked = checks.filter(function (c) { return c.checked; });
    var count = checked.length;
    var wasReady = list.classList.contains('is-order-ready');
    var orderReady = count === limit;

    list.classList.toggle('is-order-ready', orderReady);
    list.classList.toggle('is-at-limit', count >= limit && !orderReady);

    checks.forEach(function (cb) {
      var item = cb.closest('[data-project-item]');
      if (!item) return;
      var selected = cb.checked;
      item.classList.toggle('is-selected', selected);

      var sortInput = item.querySelector('.adm-home-feature-sort-input');
      if (!sortInput) return;

      sortInput.disabled = !(orderReady && selected);
    });

    if (orderReady && (!wasReady || limitChanged)) {
      var order = 0;
      checks.forEach(function (cb) {
        if (!cb.checked) return;
        var sortInput = cb.closest('[data-project-item]').querySelector('.adm-home-feature-sort-input');
        if (sortInput) sortInput.value = String(order);
        order += 1;
      });
    }

    if (status) {
      status.textContent = orderReady
        ? limit + ' projects selected — set order, then save.'
        : limit + ' projects will show on home (' + count + ' of ' + limit + ' selected).';
    }
    if (stepHint) {
      stepHint.textContent = orderReady
        ? 'All ' + limit + ' projects selected — set display order (0 = first), then save.'
        : 'Pick a number above to auto-select ' + limit + ' projects, or tick them manually (' + count + ' of ' + limit + ').';
    }
  }

  window.admSyncHomeProjectsPicker = admSyncHomeProjectsPicker;

  var projectsForm = document.getElementById('adm-home-projects-form');
  if (projectsForm) {
    var projectList = projectsForm.querySelector('#adm-home-feature-list');
    if (projectList) {
      projectList.addEventListener('change', function (e) {
        var cb = e.target.closest('.adm-home-feature-check');
        if (!cb) return;
        var limit = admHomeProjectsLimit(projectsForm);
        var checked = projectList.querySelectorAll('.adm-home-feature-check:checked');
        if (cb.checked && checked.length > limit) {
          cb.checked = false;
          return;
        }
        admSyncHomeProjectsPicker(projectsForm, false);
      });
    }
    admSyncHomeProjectsPicker(projectsForm, false);
    var initLimit = admHomeProjectsLimit(projectsForm);
    var initChecked = projectsForm.querySelectorAll('.adm-home-feature-check:checked').length;
    if (initChecked !== initLimit) {
      admSyncHomeProjectsPicker(projectsForm, true);
    }
  }

  var galleryBulkConfirm = {
    show_on_home: 'Show the selected images on the home page gallery?',
    hide_from_home: 'Hide the selected images from the home page gallery?',
    activate: 'Activate the selected images?',
    deactivate: 'Deactivate the selected images?',
    delete: 'Delete the selected images permanently? This cannot be undone.'
  };

  document.querySelectorAll('[data-bulk-picker]').forEach(function (bulkForm) {
    var bulkGrid = bulkForm.querySelector('[data-bulk-grid]');
    var selectAll = bulkForm.querySelector('[data-bulk-select-all]');
    var clearBtn = bulkForm.querySelector('[data-bulk-clear]');
    var countEl = bulkForm.querySelector('[data-bulk-count]');
    var applyBtn = bulkForm.querySelector('[data-bulk-apply]');
    var actionSelect = bulkForm.querySelector('[data-bulk-action]');

    function bulkChecks() {
      return bulkGrid
        ? Array.prototype.slice.call(bulkGrid.querySelectorAll('.adm-bulk-check'))
        : [];
    }

    function syncBulkConfirm() {
      if (!actionSelect) return;
      var action = actionSelect.value || '';
      bulkForm.setAttribute(
        'data-confirm',
        galleryBulkConfirm[action] || bulkForm.getAttribute('data-confirm-default') || 'Apply this action to the selected items?'
      );
    }

    if (!bulkForm.getAttribute('data-confirm-default')) {
      bulkForm.setAttribute('data-confirm-default', bulkForm.getAttribute('data-confirm') || '');
    }

    function syncBulkPicker() {
      var checks = bulkChecks();
      var checked = checks.filter(function (c) { return c.checked; });
      var count = checked.length;
      var allChecked = checks.length > 0 && count === checks.length;
      var someChecked = count > 0 && !allChecked;

      if (selectAll) {
        selectAll.checked = allChecked;
        selectAll.indeterminate = someChecked;
      }
      if (countEl) {
        countEl.textContent = count + ' selected';
      }
      if (applyBtn) {
        applyBtn.disabled = count === 0;
      }
      checks.forEach(function (cb) {
        var item = cb.closest('[data-bulk-item]');
        if (item) item.classList.toggle('is-selected', cb.checked);
      });
      if (bulkForm.id === 'adm-gallery-bulk-form') {
        admUpdateGalleryHint(bulkForm);
      }
    }

    if (bulkGrid) {
      bulkGrid.addEventListener('change', function (e) {
        if (e.target.classList.contains('adm-bulk-check')) {
          syncBulkPicker();
        }
      });
    }

    if (selectAll) {
      selectAll.addEventListener('change', function () {
        bulkChecks().forEach(function (cb) {
          cb.checked = selectAll.checked;
        });
        if (bulkForm.id === 'adm-gallery-bulk-form' && selectAll.checked) {
          var picker = document.querySelector('.adm-limit-picker--gallery');
          if (picker) {
            picker.querySelectorAll('.adm-limit-btn').forEach(function (b) {
              b.classList.toggle('is-active', b.getAttribute('data-value') === 'all');
            });
          }
        }
        syncBulkPicker();
      });
    }

    if (clearBtn) {
      clearBtn.addEventListener('click', function () {
        bulkChecks().forEach(function (cb) {
          cb.checked = false;
        });
        if (bulkForm.id === 'adm-gallery-bulk-form') {
          var picker = document.querySelector('.adm-limit-picker--gallery');
          if (picker) {
            picker.querySelectorAll('.adm-limit-btn').forEach(function (b) {
              b.classList.remove('is-active');
            });
          }
        }
        syncBulkPicker();
      });
    }

    if (actionSelect) {
      actionSelect.addEventListener('change', syncBulkConfirm);
      syncBulkConfirm();
    }

    bulkPickerSync.set(bulkForm, syncBulkPicker);
    syncBulkPicker();
  });

  document.querySelectorAll('.adm-project-delete-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = btn.getAttribute('data-project-id') || '';
      var title = btn.getAttribute('data-project-title') || 'this project';
      if (!id) return;
      if (!window.confirm('Delete "' + title + '" permanently?')) return;
      var form = document.getElementById('adm-project-delete-form');
      var input = document.getElementById('adm-project-delete-id');
      if (!form || !input) return;
      input.value = id;
      form.submit();
    });
  });

  document.querySelectorAll('.adm-gallery-delete-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = btn.getAttribute('data-gallery-id') || '';
      var label = btn.getAttribute('data-gallery-label') || 'this image';
      if (!id) return;
      if (!window.confirm('Delete "' + label + '" permanently?')) return;
      var form = document.getElementById('adm-gallery-delete-form');
      var input = document.getElementById('adm-gallery-delete-id');
      if (!form || !input) return;
      input.value = id;
      form.submit();
    });
  });
})();
