(function () {
  'use strict';

  var root = document.querySelector('[data-work-projects]');
  var isWorkPage = !!root;

  var archive = document.getElementById('work-archive');
  var pagination = document.getElementById('work-pagination');
  var meta = document.getElementById('work-results-meta');
  var resultsCount = document.getElementById('wrk-results-count');
  var searchInput = document.getElementById('work-search');
  var categorySelect = document.getElementById('work-category');
  var typeSelect = document.getElementById('work-type');
  var sortSelect = document.getElementById('work-sort');
  var filterChips = document.getElementById('work-filter-chips');

  var PER_PAGE = 12;
  var state = { page: 1, search: '', parent: 'all', space: '', sort: 'featured' };
  var allProjects = [];
  var galleryGroups = {};
  var debounceTimer;
  var galleryLb = null;
  var galleryState = { groupKey: '', index: 0, items: [] };

  var SPACE_TYPES = [
    ['bedroom', /\bbedroom\b/i, /\bbed[\s_-]*room\b/i, /diyabedroom/i, /\bmaster[\s_-]*bed/i],
    ['living', /\bliving\b/i, /\blounge\b/i],
    ['kitchen', /\bkitchen\b/i],
    ['mandir', /\bmandir\b/i, /\btemple\b/i, /\bpooja\b/i],
    ['bathroom', /\bbath(room)?\b/i, /\bwash(room)?\b/i, /\btoilet\b/i],
    ['dining', /\bdining\b/i],
    ['foyer', /\bfoyer\b/i, /\bentry\b/i],
    ['office', /\boffice\b/i],
    ['wardrobe', /\bwardrob/i, /\bwordrob/i],
    ['plot', /\bplot\b/i],
    ['front', /\bfront\b/i, /\belevation\b/i, /\bfacade\b/i],
    ['back', /\bback\b/i],
    ['3d', /\b3d\b/i],
  ];

  var SPACE_FILTER_KEYS = SPACE_TYPES.map(function (row) {
    return row[0];
  });

  var PARENT_FILTERS = [
    'all',
    'residential',
    'commercial',
    'interior',
    'industrial',
    'architecture',
  ];

  var SPACES_BY_PARENT = {
    residential: ['3d'],
    interior: ['bedroom', 'living', 'kitchen', 'mandir', 'bathroom', 'dining', 'foyer', 'wardrobe'],
    commercial: ['office', 'foyer', '3d', 'front'],
    industrial: ['3d'],
    architecture: ['plot', 'front', 'back'],
  };

  var SPACE_LABELS = {
    bedroom: 'Bedroom',
    living: 'Living',
    kitchen: 'Kitchen',
    mandir: 'Temple',
    bathroom: 'Bathroom',
    dining: 'Dining',
    foyer: 'Foyer',
    office: 'Office',
    wardrobe: 'Wardrobe',
    plot: 'Plot',
    front: 'Elevation',
    back: 'Rear',
    '3d': '3D',
  };

  var DEFAULT_PARENT_FOR_SPACE = {
    bedroom: 'interior',
    living: 'interior',
    kitchen: 'interior',
    mandir: 'interior',
    bathroom: 'interior',
    dining: 'interior',
    foyer: 'interior',
    wardrobe: 'interior',
    plot: 'architecture',
    front: 'architecture',
    back: 'architecture',
    '3d': 'residential',
    office: 'commercial',
  };

  function isParentFilter(value) {
    return PARENT_FILTERS.indexOf(value) !== -1;
  }

  function isSpaceFilter(category) {
    return SPACE_FILTER_KEYS.indexOf(category) !== -1 || category === 'temple';
  }

  function normalizeSpaceKey(value) {
    if (value === 'temple') return 'mandir';
    return value;
  }

  function parentLabel(parent) {
    if (parent === 'all') return 'All';
    return typeLabel(parent);
  }

  function detectSpaceType(p) {
    var hay = ((p.title || '') + ' ' + (p.slug || '')).toLowerCase();
    var i;
    var j;
    for (i = 0; i < SPACE_TYPES.length; i += 1) {
      for (j = 1; j < SPACE_TYPES[i].length; j += 1) {
        if (SPACE_TYPES[i][j].test(hay)) return SPACE_TYPES[i][0];
      }
    }
    return '';
  }

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
    if (!isLocalHost() && /^uploads\//i.test(rel)) {
      return '/' + rel;
    }
    return publicBase() + '/' + rel;
  }

  function parseStoryFromHtml(html) {
    var out = { challenge: '', approach: '', result: '' };
    if (!html) return out;
    var div = document.createElement('div');
    div.innerHTML = html;
    var paras = [];
    div.querySelectorAll('p').forEach(function (p) {
      var t = (p.textContent || '').trim();
      if (t) paras.push(t);
    });
    if (!paras.length) {
      var plain = (div.textContent || '').trim();
      if (plain) paras = plain.split(/\n{2,}/).map(function (s) { return s.trim(); }).filter(Boolean);
    }
    out.challenge = paras[0] || '';
    out.approach = paras[1] || '';
    out.result = paras[2] || '';
    return out;
  }

  function cardSizeClass(index, isHero) {
    return isHero ? 'wrk-card--hero' : '';
  }

  function galleryGroupKey(p) {
    var space = detectSpaceType(p);
    if (space) return 'space:' + space;
    var slug = String(p.slug || '').toLowerCase();
    var series = slug.replace(/-\d+$/, '').replace(/\.(jpg|jpeg|png|webp)$/i, '');
    if (series && series.length > 2 && !/^\d+$/.test(series)) {
      return 'series:' + series;
    }
    return 'solo:' + slug;
  }

  function galleryGroupLabel(key) {
    if (!key) return 'Project';
    if (key.indexOf('space:') === 0) {
      var name = key.slice(6);
      if (name === 'mandir') return 'Temple & Mandir';
      return name.charAt(0).toUpperCase() + name.slice(1);
    }
    if (key.indexOf('series:') === 0) {
      return key
        .slice(7)
        .replace(/-/g, ' ')
        .replace(/\b\w/g, function (c) {
          return c.toUpperCase();
        });
    }
    return 'Project';
  }

  function rebuildGalleryGroups() {
    galleryGroups = {};
    allProjects.forEach(function (p) {
      var key = p.galleryGroup;
      if (!galleryGroups[key]) galleryGroups[key] = [];
      galleryGroups[key].push(p);
    });
    Object.keys(galleryGroups).forEach(function (key) {
      galleryGroups[key].sort(function (a, b) {
        return (a.sortOrder || 0) - (b.sortOrder || 0);
      });
    });
  }

  function normalizeProject(p) {
    var cat = String(p.projectType || p.category || 'residential').toLowerCase();
    if (cat === 'retail') cat = 'commercial';
    var slug = p.slug || '';
    var story = parseStoryFromHtml(p.bodyHtml || '');
    var normalized = {
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
      area: p.area || '',
      year: p.year || null,
      servicesProvided: p.servicesProvided || '',
      bodyHtml: p.bodyHtml || '',
      storyChallenge: story.challenge,
      storyApproach: story.approach,
      storyResult: story.result,
    };
    normalized.galleryGroup = galleryGroupKey(normalized);
    return normalized;
  }

  function loadProjectsFromSite() {
    var data = window.__SPANGLE_SITE__;
    if (!data || !data.projects || !data.projects.length) return false;
    allProjects = data.projects.map(normalizeProject);
    rebuildGalleryGroups();
    return allProjects.length > 0;
  }

  function projectHaystack(p) {
    return (
      p.title +
      ' ' +
      p.location +
      ' ' +
      p.summary +
      ' ' +
      p.projectType +
      ' ' +
      p.slug +
      ' ' +
      (p.servicesProvided || '') +
      ' ' +
      (p.storyChallenge || '') +
      ' ' +
      (p.storyApproach || '') +
      ' ' +
      (p.storyResult || '')
    ).toLowerCase();
  }

  function is3dParentCategory(parent) {
    return parent === 'residential' || parent === 'commercial' || parent === 'industrial';
  }

  function matchesParentCategory(p, parent) {
    if (parent === 'all') return true;
    if (parent === 'interior') {
      if (detectSpaceType(p) === '3d') return false;
      if (p.projectType === 'interior' || /\binterior\b/i.test(projectHaystack(p))) return true;
      var interiorSpaces = SPACES_BY_PARENT.interior || [];
      return interiorSpaces.indexOf(detectSpaceType(p)) !== -1;
    }
    if (parent === 'industrial') {
      return p.projectType === 'industrial' || /\bindustrial\b/i.test(projectHaystack(p));
    }
    if (parent === 'architecture') {
      if (detectSpaceType(p) === '3d') return false;
      return p.projectType === 'architecture' || /\barchitecture\b/i.test(projectHaystack(p));
    }
    return p.projectType === parent;
  }

  function matchesCategory(p, category) {
    if (category === 'all') return true;
    category = normalizeSpaceKey(category);
    if (isSpaceFilter(category)) {
      return detectSpaceType(p) === category;
    }
    return matchesParentCategory(p, category);
  }

  function countProjectsInSpace(parent, spaceKey) {
    if (spaceKey === '3d' && is3dParentCategory(parent)) {
      return allProjects.filter(function (p) {
        return detectSpaceType(p) === '3d';
      }).length;
    }
    return allProjects.filter(function (p) {
      return matchesParentCategory(p, parent) && detectSpaceType(p) === spaceKey;
    }).length;
  }

  function renderSpaceChips() {
    var panel = document.getElementById('work-space-chips');
    var inner = document.getElementById('work-space-chips-inner');
    if (!panel || !inner) return;

    var spaces = SPACES_BY_PARENT[state.parent];
    if (!spaces || state.parent === 'all') {
      panel.hidden = true;
      panel.classList.remove('is-visible');
      inner.innerHTML = '';
      return;
    }

    var available = spaces.filter(function (spaceKey) {
      return countProjectsInSpace(state.parent, spaceKey) > 0;
    });

    if (!available.length) {
      panel.hidden = true;
      panel.classList.remove('is-visible');
      inner.innerHTML = '';
      return;
    }

    panel.hidden = false;
    panel.classList.add('is-visible');

    var label = document.getElementById('work-space-label');
    if (label) {
      label.textContent = parentLabel(state.parent) + ' · filter by room type';
    }

    var html =
      '<button type="button" class="work-chip wrk-chip wrk-chip--space' +
      (!state.space ? ' is-active' : '') +
      '" data-space="">All rooms</button>';

    available.forEach(function (spaceKey) {
      var count = countProjectsInSpace(state.parent, spaceKey);
      var active = state.space === spaceKey ? ' is-active' : '';
      html +=
        '<button type="button" class="work-chip wrk-chip wrk-chip--space' +
        active +
        '" data-space="' +
        esc(spaceKey) +
        '">' +
        esc(SPACE_LABELS[spaceKey] || typeLabel(spaceKey)) +
        ' <span class="wrk-chip__count">(' +
        count +
        ')</span></button>';
    });

    inner.innerHTML = html;
  }

  function pageSizeForFilter() {
    if (state.space) return 9999;
    return PER_PAGE;
  }

  function filterProjects(list) {
    var q = state.search.toLowerCase();
    return list.filter(function (p) {
      if (state.space === '3d' && is3dParentCategory(state.parent)) {
        if (detectSpaceType(p) !== '3d') return false;
      } else {
        if (!matchesParentCategory(p, state.parent)) return false;
        if (state.space && detectSpaceType(p) !== state.space) return false;
      }
      if (state.type && !matchesCategory(p, state.type)) return false;
      if (!q) return true;
      return projectHaystack(p).indexOf(q) !== -1;
    });
  }

  function syncUrl() {
    if (!window.history || !window.history.replaceState) return;
    var params = new URLSearchParams();
    if (state.search) params.set('q', state.search);
    if (state.parent && state.parent !== 'all') params.set('parent', state.parent);
    if (state.space) params.set('space', state.space);
    if (state.type) params.set('type', state.type);
    if (state.sort && state.sort !== 'featured') params.set('sort', state.sort);
    if (state.page > 1) params.set('page', String(state.page));
    var qs = params.toString();
    var url = window.location.pathname + (qs ? '?' + qs : '');
    window.history.replaceState({}, '', url);
  }

  function syncChips() {
    if (filterChips) {
      filterChips.querySelectorAll('.work-chip').forEach(function (chip) {
        var f = chip.getAttribute('data-filter');
        chip.classList.toggle('is-active', f === state.parent);
      });
    }
    renderSpaceChips();
  }

  function syncCategorySelect() {
    if (!categorySelect) return;
    if (state.space) {
      categorySelect.value = state.parent;
      return;
    }
    categorySelect.value = state.parent || 'all';
  }

  function readUrlState() {
    var params = new URLSearchParams(window.location.search);
    if (params.get('q')) state.search = params.get('q');
    if (params.get('parent')) {
      state.parent = params.get('parent');
    } else if (params.get('category')) {
      var legacy = params.get('category');
      if (isSpaceFilter(legacy)) {
        state.space = normalizeSpaceKey(legacy);
        state.parent = DEFAULT_PARENT_FOR_SPACE[state.space] || 'residential';
      } else if (isParentFilter(legacy)) {
        state.parent = legacy;
      }
    }
    if (params.get('space')) state.space = normalizeSpaceKey(params.get('space'));
    if (params.get('type')) state.type = params.get('type');
    if (params.get('sort')) state.sort = params.get('sort');
    if (params.get('page')) state.page = parseInt(params.get('page'), 10) || 1;
    if (searchInput && state.search) searchInput.value = state.search;
    syncCategorySelect();
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
    } else if (state.sort === 'latest') {
      sorted.sort(function (a, b) {
        var ya = a.year || 0;
        var yb = b.year || 0;
        if (yb !== ya) return yb - ya;
        return (a.sortOrder || 0) - (b.sortOrder || 0);
      });
    }
    return sorted;
  }

  function storyLine(label, text) {
    if (!text) return '';
    return (
      '<p><strong>' +
      esc(label) +
      '</strong>' +
      esc(text) +
      '</p>'
    );
  }

  function viewerStoryBlock(label, text) {
    if (!text) return '';
    return (
      '<section class="work-viewer__block">' +
      '<h3>' +
      esc(label) +
      '</h3>' +
      '<p>' +
      esc(text) +
      '</p></section>'
    );
  }

  function buildViewerFacts(p) {
    var rows = [];
    if (p.location) rows.push(['Location', p.location]);
    if (p.year) rows.push(['Completed', String(p.year)]);
    if (p.area) rows.push(['Area', p.area]);
    rows.push(['Category', typeLabel(p.projectType)]);
    if (p.servicesProvided) rows.push(['Scope', p.servicesProvided]);
    return rows
      .map(function (row) {
        return '<li><span>' + esc(row[0]) + '</span><strong>' + esc(row[1]) + '</strong></li>';
      })
      .join('');
  }

  function buildViewerStory(p) {
    return (
      viewerStoryBlock('Brief', p.summary) +
      viewerStoryBlock('Challenge', p.storyChallenge) +
      viewerStoryBlock('Design approach', p.storyApproach) +
      viewerStoryBlock('Result', p.storyResult)
    );
  }

  function renderCard(p, index) {
    var img = esc(p.heroImage || 'uploads/ENTRY.jpg');
    var title = esc(p.title);
    var loc = esc(p.location || '');
    var scope = esc(p.servicesProvided || typeLabel(p.projectType));
    var sizeCls = cardSizeClass(index, p.isFeatured && index === 0 && state.page === 1);
    var metaItems = [];
    if (loc) metaItems.push('<li>' + loc + '</li>');
    if (p.year) metaItems.push('<li>' + esc(String(p.year)) + '</li>');
    if (p.area) metaItems.push('<li>' + esc(p.area) + '</li>');

    var storyHtml =
      storyLine('Challenge', p.storyChallenge || p.summary) +
      storyLine('Approach', p.storyApproach) +
      storyLine('Result', p.storyResult);

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
      '<article class="work-card wrk-card ' +
      sizeCls +
      '" data-work-cat="' +
      esc(p.projectType) +
      '" data-work-slug="' +
      esc(p.slug) +
      '" data-gallery-group="' +
      esc(p.galleryGroup) +
      '" data-work-open tabindex="0" role="button" aria-label="Open ' +
      esc(p.title) +
      ' — view images and project details">' +
      '<div class="wrk-card__media">' +
      imgTag +
      '<span class="wrk-card__badge">' +
      typeLabel(p.projectType) +
      '</span>' +
      '<div class="wrk-card__hover"><span class="wrk-card__cta">Explore project <span aria-hidden="true">→</span></span></div>' +
      '</div>' +
      '<div class="work-card-body wrk-card__body">' +
      '<h3>' +
      title +
      '</h3>' +
      (metaItems.length ? '<ul class="wrk-card__meta">' + metaItems.join('') + '</ul>' : '') +
      '<p class="wrk-card__scope">' +
      scope +
      '</p>' +
      (storyHtml ? '<div class="wrk-card__story">' + storyHtml + '</div>' : '') +
      '<p class="wrk-card__open-hint">Tap to view images &amp; details</p>' +
      '</div></article>'
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

  function updateResultsMeta(pageCount, total) {
    var detail = parentLabel(state.parent);
    if (state.space) {
      detail += ' · ' + (SPACE_LABELS[state.space] || typeLabel(state.space));
    }
    var text = 'Showing ' + pageCount + ' of ' + total + ' projects';
    if (state.parent !== 'all' || state.space) {
      text += ' in ' + detail;
    }
    if (meta) meta.textContent = text;
    if (resultsCount) {
      resultsCount.classList.add('is-updating');
      resultsCount.textContent = total + ' project' + (total === 1 ? '' : 's');
      requestAnimationFrame(function () {
        resultsCount.classList.remove('is-updating');
      });
    }
    document.dispatchEvent(
      new CustomEvent('spangle:work-results', { detail: { total: total, showing: pageCount } })
    );
  }

  function ensureGalleryLightbox() {
    if (galleryLb) return galleryLb;
    galleryLb = document.createElement('div');
    galleryLb.id = 'work-viewer';
    galleryLb.className = 'work-viewer';
    galleryLb.hidden = true;
    galleryLb.innerHTML =
      '<div class="work-viewer__scrim" data-close="1" role="presentation"></div>' +
      '<div class="work-viewer__sheet" role="dialog" aria-modal="true" aria-labelledby="work-viewer-title">' +
      '<button type="button" class="work-viewer__close" aria-label="Close">&times;</button>' +
      '<div class="work-viewer__layout">' +
      '<div class="work-viewer__visual">' +
      '<div class="work-viewer__stage">' +
      '<button type="button" class="work-viewer__nav work-viewer__nav--prev" aria-label="Previous image"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>' +
      '<figure class="work-viewer__figure"><img src="" alt="" id="work-viewer-main" /></figure>' +
      '<button type="button" class="work-viewer__nav work-viewer__nav--next" aria-label="Next image"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>' +
      '</div>' +
      '<p class="work-viewer__counter" id="work-viewer-counter"></p>' +
      '<div class="work-viewer__thumbs" id="work-viewer-thumbs" role="tablist" aria-label="Related images"></div>' +
      '</div>' +
      '<div class="work-viewer__content">' +
      '<p class="work-viewer__eyebrow" id="work-viewer-eyebrow"></p>' +
      '<h2 class="work-viewer__title" id="work-viewer-title"></h2>' +
      '<p class="work-viewer__subtitle" id="work-viewer-subtitle"></p>' +
      '<ul class="work-viewer__facts" id="work-viewer-facts"></ul>' +
      '<div class="work-viewer__story" id="work-viewer-story"></div>' +
      '<div class="work-viewer__actions">' +
      '<a href="contact.html" class="btn btn-primary work-viewer__enquire">Discuss this project</a>' +
      '<a href="#" class="btn btn-ghost work-viewer__full" id="work-viewer-full">Full case study <span aria-hidden="true">→</span></a>' +
      '</div></div></div></div>';
    document.body.appendChild(galleryLb);

    galleryLb.addEventListener('click', function (e) {
      if (e.target.closest('.work-viewer__full')) return;
      if (e.target.getAttribute('data-close') || e.target.closest('.work-viewer__close')) {
        closeGallery();
        return;
      }
      var thumbBtn = e.target.closest('.work-viewer__thumb');
      if (thumbBtn) {
        var idx = parseInt(thumbBtn.getAttribute('data-gallery-index'), 10);
        if (!isNaN(idx)) {
          galleryState.index = idx;
          renderGallerySlide();
        }
        return;
      }
      if (e.target.closest('.work-viewer__nav--prev')) {
        stepGallery(-1);
        return;
      }
      if (e.target.closest('.work-viewer__nav--next')) {
        stepGallery(1);
      }
    });

    document.addEventListener('keydown', function (e) {
      if (!galleryLb || galleryLb.hidden) return;
      if (e.key === 'Escape') closeGallery();
      if (e.key === 'ArrowLeft') stepGallery(-1);
      if (e.key === 'ArrowRight') stepGallery(1);
    });

    return galleryLb;
  }

  function renderGallerySlide() {
    var lb = ensureGalleryLightbox();
    var items = galleryState.items;
    if (!items.length) return;
    var item = items[galleryState.index];
    var main = lb.querySelector('#work-viewer-main');
    var counter = lb.querySelector('#work-viewer-counter');
    var title = lb.querySelector('#work-viewer-title');
    var subtitle = lb.querySelector('#work-viewer-subtitle');
    var eyebrow = lb.querySelector('#work-viewer-eyebrow');
    var facts = lb.querySelector('#work-viewer-facts');
    var story = lb.querySelector('#work-viewer-story');
    var full = lb.querySelector('#work-viewer-full');
    var thumbs = lb.querySelector('#work-viewer-thumbs');
    var groupLabel = galleryGroupLabel(galleryState.groupKey);

    if (main) {
      main.src = item.heroImage;
      main.alt = item.title || groupLabel;
    }
    if (title) title.textContent = item.title || groupLabel;
    if (subtitle) {
      subtitle.textContent =
        items.length > 1
          ? groupLabel + ' · Image ' + (galleryState.index + 1) + ' of ' + items.length
          : groupLabel;
    }
    if (eyebrow) {
      eyebrow.textContent = items.length > 1 ? groupLabel + ' collection' : typeLabel(item.projectType);
    }
    var hasMultiple = items.length > 1;
    var visual = lb.querySelector('.work-viewer__visual');
    if (visual) visual.classList.toggle('is-single', !hasMultiple);

    if (counter) {
      counter.textContent = hasMultiple ? 'Browse related images in this collection' : '';
      counter.hidden = !hasMultiple;
    }
    if (facts) facts.innerHTML = buildViewerFacts(item);
    if (story) story.innerHTML = buildViewerStory(item);
    if (full) full.setAttribute('href', item.linkUrl || 'work.html');

    if (thumbs) {
      if (!hasMultiple) {
        thumbs.innerHTML = '';
        thumbs.hidden = true;
        thumbs.classList.remove('is-many');
      } else {
        thumbs.hidden = false;
        thumbs.classList.toggle('is-many', items.length > 8);
        thumbs.innerHTML = items
          .map(function (p, idx) {
            var active = idx === galleryState.index ? ' is-active' : '';
            return (
              '<button type="button" class="work-viewer__thumb' +
              active +
              '" role="tab" aria-selected="' +
              (idx === galleryState.index ? 'true' : 'false') +
              '" aria-label="' +
              esc(p.title || 'Image ' + (idx + 1)) +
              '" data-gallery-index="' +
              idx +
              '">' +
              '<img src="' +
              esc(p.heroImage) +
              '" alt="" class="is-loaded" loading="eager" decoding="async" />' +
              '</button>'
            );
          })
          .join('');
        var activeThumb = thumbs.querySelector('.work-viewer__thumb.is-active');
        if (activeThumb && activeThumb.scrollIntoView) {
          activeThumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
      }
    }

    var prev = lb.querySelector('.work-viewer__nav--prev');
    var next = lb.querySelector('.work-viewer__nav--next');
    if (prev) {
      prev.hidden = !hasMultiple;
      prev.disabled = !hasMultiple;
    }
    if (next) {
      next.hidden = !hasMultiple;
      next.disabled = !hasMultiple;
    }

    var content = lb.querySelector('.work-viewer__content');
    if (content) content.scrollTop = 0;
  }

  function openGallery(slug) {
    var project = allProjects.find(function (p) {
      return p.slug === slug;
    });
    if (!project) return;
    var items = galleryGroups[project.galleryGroup] || [project];
    galleryState.groupKey = project.galleryGroup;
    galleryState.items = items;
    galleryState.index = Math.max(
      0,
      items.findIndex(function (p) {
        return p.slug === slug;
      })
    );
    ensureGalleryLightbox();
    renderGallerySlide();
    galleryLb.hidden = false;
    document.body.classList.add('work-gallery-open');
    var closeBtn = galleryLb.querySelector('.work-viewer__close');
    if (closeBtn) closeBtn.focus();
  }

  function closeGallery() {
    if (!galleryLb) return;
    galleryLb.hidden = true;
    document.body.classList.remove('work-gallery-open');
    var main = galleryLb.querySelector('#work-viewer-main');
    if (main) main.removeAttribute('src');
  }

  function stepGallery(delta) {
    var items = galleryState.items;
    if (!items.length) return;
    galleryState.index = (galleryState.index + delta + items.length) % items.length;
    renderGallerySlide();
  }

  var galleryClickBound = false;

  function bindGalleryClicks() {
    if (!archive || galleryClickBound) return;
    galleryClickBound = true;

    archive.addEventListener('click', function (e) {
      var card = e.target.closest('[data-work-open]');
      if (!card) return;
      e.preventDefault();
      openGallery(card.getAttribute('data-work-slug') || '');
    });

    archive.addEventListener('keydown', function (e) {
      if (e.key !== 'Enter' && e.key !== ' ') return;
      var card = e.target.closest('[data-work-open]');
      if (!card) return;
      e.preventDefault();
      openGallery(card.getAttribute('data-work-slug') || '');
    });
  }

  function bindWorkImages() {
    bindGalleryClicks();
    archive.querySelectorAll('.work-card').forEach(function (card, i) {
      card.classList.remove('motion-item');
      card.classList.add('motion-in');
      card.style.setProperty('--motion-i', String(i));
    });
    archive.querySelectorAll('img').forEach(function (img) {
      img.classList.add('is-loaded');
      img.loading = 'lazy';
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
  }

  function render() {
    if (!archive) return;
    archive.classList.add('is-filtering');
    if (!allProjects.length) {
      archive.innerHTML = '<p class="work-empty">No projects yet. Upload images in admin, then run sync.</p>';
      updateResultsMeta(0, 0);
      renderPagination(0);
      return;
    }

    var filtered = sortProjects(filterProjects(allProjects));
    var total = filtered.length;
    var perPage = pageSizeForFilter();
    var totalPages = Math.max(1, Math.ceil(total / perPage));
    if (state.page > totalPages) state.page = totalPages;
    if (state.page < 1) state.page = 1;

    var start = (state.page - 1) * perPage;
    var pageItems = filtered.slice(start, start + perPage);

    if (!pageItems.length) {
      archive.innerHTML = '<p class="work-empty">No projects match your filters.</p>';
      updateResultsMeta(0, total);
      renderPagination(totalPages);
      syncUrl();
      syncChips();
      requestAnimationFrame(function () {
        archive.classList.remove('is-filtering');
      });
      return;
    }

    archive.innerHTML = pageItems.map(function (p, i) {
      return renderCard(p, i);
    }).join('');
    bindWorkImages();
    updateResultsMeta(pageItems.length, total);
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

  function setFilter(opts) {
    opts = opts || {};
    if (opts.parent !== undefined) {
      state.parent = opts.parent || 'all';
      state.space = '';
    }
    if (opts.space !== undefined) {
      state.space = normalizeSpaceKey(opts.space || '');
    }
    if (opts.category !== undefined) {
      var category = opts.category || 'all';
      if (isSpaceFilter(category)) {
        state.space = normalizeSpaceKey(category);
        state.parent = opts.parent || DEFAULT_PARENT_FOR_SPACE[state.space] || 'residential';
      } else {
        state.parent = category;
        state.space = '';
      }
    }
    if (opts.type !== undefined) {
      state.type = opts.type || '';
      if (typeSelect) typeSelect.value = state.type;
    }
    if (opts.sort !== undefined) {
      state.sort = opts.sort || 'featured';
      if (sortSelect) sortSelect.value = state.sort;
    }
    if (opts.search !== undefined && searchInput) {
      state.search = opts.search;
      searchInput.value = opts.search;
    }
    if (opts.page !== undefined) state.page = opts.page;
    else if (
      opts.parent !== undefined ||
      opts.space !== undefined ||
      opts.category !== undefined ||
      opts.type !== undefined ||
      opts.search !== undefined
    ) {
      state.page = 1;
    }
    syncCategorySelect();
    syncChips();
    render();
    var section = document.getElementById('work-projects');
    if (section && (opts.scroll !== false)) {
      section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  function initWorkPage() {
    readUrlState();
    if (!loadProjectsFromSite()) {
      if (archive) archive.innerHTML = '<p class="work-loading">Loading projects…</p>';
      return;
    }
    render();
  }

  function initProjectsLibrary() {
    loadProjectsFromSite();
  }

  function boot() {
    if (isWorkPage) {
      initWorkPage();
    } else {
      initProjectsLibrary();
    }
  }

  if (searchInput) {
    searchInput.addEventListener('input', function () {
      state.search = searchInput.value.trim();
      scheduleRender();
    });
  }
  if (categorySelect) {
    categorySelect.addEventListener('change', function () {
      setFilter({ parent: categorySelect.value, scroll: false });
    });
  }
  if (typeSelect) {
    typeSelect.addEventListener('change', function () {
      state.type = typeSelect.value || '';
      state.page = 1;
      render();
    });
  }
  if (sortSelect) {
    sortSelect.addEventListener('change', function () {
      state.sort = sortSelect.value || 'featured';
      state.page = 1;
      render();
    });
  }

  if (filterChips) {
    filterChips.addEventListener('click', function (e) {
      var chip = e.target.closest('.work-chip');
      if (!chip) return;
      var filter = chip.getAttribute('data-filter') || 'all';
      setFilter({ parent: filter, scroll: false });
    });
  }

  var spaceChips = document.getElementById('work-space-chips');
  if (spaceChips) {
    spaceChips.addEventListener('click', function (e) {
      var chip = e.target.closest('[data-space]');
      if (!chip) return;
      setFilter({ space: chip.getAttribute('data-space') || '', scroll: false });
    });
  }

  window.SpangleWorkProjects = {
    setFilter: setFilter,
    getState: function () {
      return Object.assign({}, state);
    },
    getAll: function () {
      return allProjects.slice();
    },
    getFiltered: function () {
      return sortProjects(filterProjects(allProjects));
    },
    getGalleryGroup: function (slug) {
      var project = allProjects.find(function (p) {
        return p.slug === slug;
      });
      if (!project) return [];
      return (galleryGroups[project.galleryGroup] || [project]).slice();
    },
    openGallery: openGallery,
    closeGallery: closeGallery,
    refresh: function () {
      loadProjectsFromSite();
      render();
    },
  };

  if (window.__SPANGLE_SITE__ && window.__SPANGLE_SITE__.projects) {
    boot();
  } else {
    document.addEventListener('spangle:site-data', boot, { once: true });
    setTimeout(boot, 1500);
  }
})();
