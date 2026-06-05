(function () {
  'use strict';

  var root = document.querySelector('[data-work-projects]');
  if (!root) return;

  var archive = document.getElementById('work-archive');
  var pagination = document.getElementById('work-pagination');
  var meta = document.getElementById('work-results-meta');
  var searchInput = document.getElementById('work-search');
  var categorySelect = document.getElementById('work-category');
  var typeSelect = document.getElementById('work-type');
  var sortSelect = document.getElementById('work-sort');
  var filterChips = document.getElementById('work-filter-chips');

  var PER_PAGE = 12;
  var state = { page: 1, search: '', category: 'all', type: '', sort: 'featured' };
  var allProjects = [];
  var debounceTimer;

  function esc(s) {
    if (s == null) return '';
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/"/g, '&quot;');
  }

  function typeLabel(t) {
    return esc(String(t || '').replace(/-/g, ' ').replace(/\b\w/g, function (c) {
      return c.toUpperCase();
    }));
  }

  function isLocalHost() {
    var host = window.location.hostname;
    return host === 'localhost' || host === '127.0.0.1';
  }

  function publicBase() {
    var data = window.__SPANGLE_SITE__ || {};
    var base = String(data.publicBase || document.documentElement.getAttribute('data-public-base') || '')
      .trim()
      .replace(/\/$/, '');
    if (base && /localhost|127\.0\.0\.1/i.test(base)) {
      base = '';
    }
    if (base) {
      try {
        var pageHost = window.location.hostname;
        var storedHost = new URL(base).hostname;
        if (pageHost && storedHost && storedHost !== pageHost) {
          base = '';
        }
      } catch (e) {
        base = '';
      }
    }
    if (base) return base;
    var path = window.location.pathname.replace(/\/[^/]*$/, '');
    return (window.location.origin + path).replace(/\/$/, '');
  }

  function imageSrc(path) {
    if (!path) path = 'uploads/ENTRY.jpg';
    var p = String(path).trim();
    if (/^https?:\/\//i.test(p)) return p;
    if (!/^(uploads\/|\.\/)/.test(p)) {
      p = 'uploads/' + p.replace(/^\/+/, '');
    }
    var parts = p.split('/').map(function (seg) {
      try {
        return encodeURIComponent(decodeURIComponent(seg));
      } catch (e) {
        return encodeURIComponent(seg);
      }
    });
    var rel = parts.join('/');
    // Live hosting: root-relative paths ignore a bad publicBase in site.json (e.g. localhostscripts).
    if (!isLocalHost() && /^uploads\//i.test(rel)) {
      return '/' + rel;
    }
    return publicBase() + '/' + rel;
  }

  function normalizeProject(p) {
    var cat = String(p.projectType || p.category || 'residential').toLowerCase();
    if (cat === 'retail') cat = 'commercial';
    var slug = p.slug || '';
    return {
      slug: slug,
      title: p.title || 'Project',
      location: p.location || 'Gujarat, India',
      summary: p.summary || '',
      projectType: cat,
      heroImage: imageSrc(p.heroImage || ''),
      heroSrcset: p.heroSrcset || '',
      heroSizes: p.heroSizes || '(max-width: 960px) 100vw, 33vw',
      linkUrl: p.linkUrl || ('project.php?slug=' + encodeURIComponent(slug)),
      sortOrder: typeof p.sortOrder === 'number' ? p.sortOrder : 0,
      isFeatured: !!p.isFeatured,
    };
  }

  function loadProjectsFromSite() {
    var data = window.__SPANGLE_SITE__;
    if (!data || !data.projects || !data.projects.length) return false;
    allProjects = data.projects.map(normalizeProject);
    return allProjects.length > 0;
  }

  function projectHaystack(p) {
    return (p.title + ' ' + p.location + ' ' + p.summary + ' ' + p.projectType + ' ' + p.slug).toLowerCase();
  }

  function matchesCategory(p, category) {
    if (category === 'all') return true;
    if (category === 'villa') {
      return /\bvilla\b/i.test(projectHaystack(p));
    }
    if (category === 'office') {
      return p.projectType === 'commercial' || /\boffice\b/i.test(projectHaystack(p));
    }
    if (category === 'interior') {
      return p.projectType === 'interior' || /\binterior\b/i.test(projectHaystack(p));
    }
    return p.projectType === category;
  }

  function filterProjects(list) {
    var q = state.search.toLowerCase();
    return list.filter(function (p) {
      if (!matchesCategory(p, state.category)) return false;
      if (state.type && !matchesCategory(p, state.type)) return false;
      if (!q) return true;
      return projectHaystack(p).indexOf(q) !== -1;
    });
  }

  function syncUrl() {
    if (!window.history || !window.history.replaceState) return;
    var params = new URLSearchParams();
    if (state.search) params.set('q', state.search);
    if (state.category && state.category !== 'all') params.set('category', state.category);
    if (state.type) params.set('type', state.type);
    if (state.sort && state.sort !== 'featured') params.set('sort', state.sort);
    if (state.page > 1) params.set('page', String(state.page));
    var qs = params.toString();
    var url = window.location.pathname + (qs ? '?' + qs : '');
    window.history.replaceState({}, '', url);
  }

  function syncChips() {
    if (!filterChips) return;
    filterChips.querySelectorAll('.work-chip').forEach(function (chip) {
      var f = chip.getAttribute('data-filter');
      chip.classList.toggle('is-active', f === state.category);
    });
  }

  function readUrlState() {
    var params = new URLSearchParams(window.location.search);
    if (params.get('q')) state.search = params.get('q');
    if (params.get('category')) state.category = params.get('category');
    if (params.get('type')) state.type = params.get('type');
    if (params.get('sort')) state.sort = params.get('sort');
    if (params.get('page')) state.page = parseInt(params.get('page'), 10) || 1;
    if (searchInput && state.search) searchInput.value = state.search;
    if (categorySelect && state.category) categorySelect.value = state.category;
    if (typeSelect && state.type) typeSelect.value = state.type;
    if (sortSelect && state.sort) sortSelect.value = state.sort;
    syncChips();
  }

  function sortProjects(list) {
    var sorted = list.slice();
    if (state.sort === 'oldest') {
      sorted.reverse();
    } else if (state.sort === 'featured') {
      sorted.sort(function (a, b) {
        var feat = (b.isFeatured ? 1 : 0) - (a.isFeatured ? 1 : 0);
        if (feat !== 0) return feat;
        return (a.sortOrder || 0) - (b.sortOrder || 0);
      });
    }
    return sorted;
  }

  function renderCard(p) {
    var img = esc(p.heroImage || 'uploads/ENTRY.jpg');
    var title = esc(p.title);
    var loc = esc(p.location || '');
    var sum = esc(p.summary || '');
    var link = esc(p.linkUrl || 'work.html');
    var imgTag =
      '<img src="' +
      img +
      '" alt="' +
      title +
      '" class="is-loaded" loading="lazy" width="480" height="360" decoding="async"' +
      ' data-rimg-primary="' +
      img +
      '"' +
      (p.heroSrcset ? ' srcset="' + esc(p.heroSrcset) + '"' : '') +
      (p.heroSizes ? ' sizes="' + esc(p.heroSizes) + '"' : '') +
      ' />';
    return (
      '<a href="' + link + '" class="work-card" data-work-cat="' + esc(p.projectType) + '">' +
      imgTag +
      '<div class="work-card-body"><span>' + typeLabel(p.projectType) + '</span>' +
      '<h3>' + title + '</h3><p>' + (loc + (sum ? ' — ' + sum : '')) + '</p></div></a>'
    );
  }

  function buildPageList(current, total) {
    if (total <= 1) return [];
    if (total <= 7) {
      var all = [];
      for (var i = 1; i <= total; i += 1) all.push(i);
      return all;
    }
    var pages = [];
    var add = function (n) {
      if (n >= 1 && n <= total && pages.indexOf(n) === -1) pages.push(n);
    };
    add(1);
    add(2);
    add(total - 1);
    add(total);
    for (var p = current - 1; p <= current + 1; p += 1) add(p);
    pages.sort(function (a, b) {
      return a - b;
    });
    var out = [];
    for (var j = 0; j < pages.length; j += 1) {
      if (j > 0 && pages[j] - pages[j - 1] > 1) out.push('…');
      out.push(pages[j]);
    }
    return out;
  }

  function goToPage(page) {
    state.page = page;
    render();
    var section = document.getElementById('work-projects');
    if (section) section.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  var paginationClickBound = false;

  function bindPaginationClicks() {
    if (!pagination || paginationClickBound) return;
    paginationClickBound = true;
    pagination.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-page]');
      if (!btn || btn.disabled) return;
      var page = parseInt(btn.getAttribute('data-page'), 10);
      if (!page || page === state.page) return;
      goToPage(page);
    });
  }

  function renderPagination(totalPages) {
    if (!pagination) return;
    bindPaginationClicks();
    if (totalPages <= 1) {
      pagination.innerHTML = '';
      return;
    }
    var current = state.page;
    var items = buildPageList(current, totalPages);
    var html = '';

    html +=
      '<button type="button" class="work-page-btn work-page-nav" data-page="1"' +
      (current === 1 ? ' disabled aria-disabled="true"' : ' aria-label="First page"') +
      '>First</button>';
    html +=
      '<button type="button" class="work-page-btn work-page-nav" data-page="' +
      (current - 1) +
      '"' +
      (current === 1 ? ' disabled aria-disabled="true"' : ' aria-label="Previous page"') +
      '>Prev</button>';

    html += '<ol class="work-page-list" role="list">';
    items.forEach(function (item) {
      if (item === '…') {
        html += '<li class="work-page-ellipsis" aria-hidden="true">…</li>';
        return;
      }
      var isActive = item === current;
      html +=
        '<li><button type="button" class="work-page-num' +
        (isActive ? ' is-active' : '') +
        '" data-page="' +
        item +
        '"' +
        (isActive ? ' aria-current="page"' : ' aria-label="Page ' + item + '"') +
        '>' +
        item +
        '</button></li>';
    });
    html += '</ol>';

    html +=
      '<button type="button" class="work-page-btn work-page-nav" data-page="' +
      (current + 1) +
      '"' +
      (current === totalPages ? ' disabled aria-disabled="true"' : ' aria-label="Next page"') +
      '>Next</button>';
    html +=
      '<button type="button" class="work-page-btn work-page-nav" data-page="' +
      totalPages +
      '"' +
      (current === totalPages ? ' disabled aria-disabled="true"' : ' aria-label="Last page"') +
      '>Last</button>';

    html +=
      '<span class="work-page-summary">Page <strong>' +
      current +
      '</strong> of <strong>' +
      totalPages +
      '</strong></span>';

    pagination.innerHTML = html;
  }

  function bindWorkImages() {
    archive.querySelectorAll('.work-card').forEach(function (card, i) {
      card.classList.remove('motion-item');
      card.classList.add('motion-in');
      card.style.setProperty('--motion-i', String(i));
    });
    archive.querySelectorAll('img').forEach(function (img) {
      img.classList.add('is-loaded');
      img.loading = 'eager';
      img.style.opacity = '1';
      img.style.visibility = 'visible';
      if (window.SpangleContent && window.SpangleContent.attachResponsiveImgFallback) {
        window.SpangleContent.attachResponsiveImgFallback(
          img,
          img.getAttribute('data-rimg-primary'),
          'uploads/ENTRY.jpg'
        );
      }
    });
    document.dispatchEvent(new CustomEvent('spangle:work-archive-rendered'));
    if (window.SpangleMotion && window.SpangleMotion.refresh) {
      window.SpangleMotion.refresh();
    }
    archive.querySelectorAll('.work-card').forEach(function (card) {
      card.classList.remove('motion-item');
      card.classList.add('motion-in');
    });
  }

  function render() {
    if (!archive) return;
    archive.classList.add('is-filtering');
    if (!allProjects.length) {
      archive.innerHTML = '<p class="work-empty">No projects yet. Upload images in admin, then run sync.</p>';
      if (meta) meta.textContent = '0 projects';
      renderPagination(0);
      return;
    }

    var filtered = sortProjects(filterProjects(allProjects));
    var total = filtered.length;
    var totalPages = Math.max(1, Math.ceil(total / PER_PAGE));
    if (state.page > totalPages) state.page = totalPages;
    if (state.page < 1) state.page = 1;

    var start = (state.page - 1) * PER_PAGE;
    var pageItems = filtered.slice(start, start + PER_PAGE);

    if (!pageItems.length) {
      archive.innerHTML = '<p class="work-empty">No projects match your filters.</p>';
      if (meta) meta.textContent = '0 projects';
      renderPagination(totalPages);
      return;
    }

    archive.innerHTML = pageItems.map(renderCard).join('');
    bindWorkImages();
    if (meta) {
      meta.textContent = 'Showing ' + pageItems.length + ' of ' + total + ' projects';
    }
    renderPagination(totalPages);
    syncUrl();
    syncChips();
    requestAnimationFrame(function () {
      archive.classList.remove('is-filtering');
    });
  }

  function scheduleRender() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(function () {
      state.page = 1;
      render();
    }, 220);
  }

  function init() {
    readUrlState();
    if (!loadProjectsFromSite()) {
      archive.innerHTML = '<p class="work-loading">Loading projects…</p>';
      return;
    }
    render();
  }

  if (searchInput) {
    searchInput.addEventListener('input', function () {
      state.search = searchInput.value.trim();
      scheduleRender();
    });
  }
  [categorySelect, typeSelect, sortSelect].forEach(function (el) {
    if (!el) return;
    el.addEventListener('change', function () {
      state.category = categorySelect ? categorySelect.value : 'all';
      state.type = typeSelect ? typeSelect.value : '';
      state.sort = sortSelect ? sortSelect.value : 'latest';
      state.page = 1;
      syncChips();
      render();
    });
  });

  if (filterChips) {
    filterChips.addEventListener('click', function (e) {
      var chip = e.target.closest('.work-chip');
      if (!chip) return;
      var filter = chip.getAttribute('data-filter') || 'all';
      state.category = filter;
      state.page = 1;
      if (categorySelect) categorySelect.value = filter;
      render();
    });
  }

  if (window.__SPANGLE_SITE__ && window.__SPANGLE_SITE__.projects) {
    init();
  } else {
    document.addEventListener('spangle:site-data', init, { once: true });
    setTimeout(init, 1500);
  }
})();
