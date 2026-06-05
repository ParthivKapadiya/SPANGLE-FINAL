(function () {
  'use strict';

  var hero = document.querySelector('.hero--premium');
  if (!hero) return;

  var slider = document.getElementById('hero-slider');
  var video = document.getElementById('hero-video');
  var impactRoot = document.getElementById('hero-impact-panel') || document.getElementById('hero-glass-stats');
  var impactGrid = document.getElementById('hero-impact-grid');
  var headlineEl = document.getElementById('hero-headline-display');
  var seoTitle = document.querySelector('.site-hero-title');
  var previewRoot = null;
  var scrollBtn = document.getElementById('hero-scroll-luxury');

  var DEFAULT_HEADLINES = [
    'Architecture That Outlives Generations',
    'Designing Legacies In Concrete And Light',
    'Where Vision Becomes Landmark',
    'Spaces Built To Inspire',
  ];

  var headlines = DEFAULT_HEADLINES.slice();
  var headlineIndex = 0;
  var headlineTimer = null;

  function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  }

  function stripHtml(html) {
    var tmp = document.createElement('div');
    tmp.innerHTML = html;
    return (tmp.textContent || tmp.innerText || '').replace(/\s+/g, ' ').trim();
  }

  function impactLabel(label) {
    var map = {
      'projects completed': 'Projects Delivered',
      'projects delivered': 'Projects Delivered',
      'years experience': 'Years Experience',
      'years in practice': 'Years Experience',
      'sq ft delivered': 'Sq Ft Designed',
      'sq ft designed': 'Sq Ft Designed',
      'client satisfaction': 'Client Satisfaction',
      'client commissions': 'Projects Delivered',
    };
    var key = String(label || '').toLowerCase().trim();
    return map[key] || label || '';
  }

  function impactLabelShort(label) {
    var map = {
      'projects completed': 'Projects',
      'projects delivered': 'Projects',
      'years experience': 'Years',
      'years in practice': 'Years',
      'sq ft delivered': 'Sq Ft',
      'sq ft designed': 'Sq Ft',
      'client satisfaction': 'Satisfaction',
      'client commissions': 'Projects',
    };
    var key = String(label || '').toLowerCase().trim();
    var long = impactLabel(label);
    return map[key] || (long && long.length > 10 ? long.split(' ')[0] : long) || '';
  }

  function impactLabelHtml(label) {
    var long = impactLabel(label);
    var short = impactLabelShort(label);
    return (
      '<span class="hero-impact-label--long">' +
      long +
      '</span><span class="hero-impact-label--short">' +
      short +
      '</span>'
    );
  }

  /* —— Scroll parallax —— */
  function initScrollParallax() {
    if (prefersReducedMotion() || !slider) return;
    var ticking = false;
    window.addEventListener(
      'scroll',
      function () {
        if (ticking) return;
        ticking = true;
        requestAnimationFrame(function () {
          var y = Math.min(window.scrollY * 0.16, 100);
          slider.style.transform = 'translate3d(0, ' + y + 'px, 0)';
          ticking = false;
        });
      },
      { passive: true }
    );
  }

  /* —— Mouse depth parallax (desktop) —— */
  function initMouseParallax() {
    if (prefersReducedMotion() || window.matchMedia('(max-width: 900px)').matches) return;
    var mediaWrap = hero.querySelector('.hero-media-wrap');
    var content = hero.querySelector('.hero-content');
    var impact = impactRoot;
    var ticking = false;
    var mx = 0;
    var my = 0;

    function apply() {
      if (mediaWrap) {
        mediaWrap.style.setProperty('--hero-mx', mx * 0.018 + 'px');
        mediaWrap.style.setProperty('--hero-my', my * 0.012 + 'px');
      }
      if (content) {
        content.style.setProperty('--hero-content-x', mx * -0.006 + 'px');
        content.style.setProperty('--hero-content-y', my * -0.004 + 'px');
      }
      if (impact) {
        impact.style.setProperty('--hero-impact-x', mx * 0.008 + 'px');
        impact.style.setProperty('--hero-impact-y', my * 0.005 + 'px');
      }
      ticking = false;
    }

    hero.addEventListener(
      'mousemove',
      function (e) {
        var rect = hero.getBoundingClientRect();
        mx = (e.clientX - rect.left - rect.width / 2) / rect.width;
        my = (e.clientY - rect.top - rect.height / 2) / rect.height;
        if (!ticking) {
          ticking = true;
          requestAnimationFrame(apply);
        }
      },
      { passive: true }
    );

    hero.addEventListener('mouseleave', function () {
      mx = 0;
      my = 0;
      requestAnimationFrame(apply);
    });
  }

  /* —— Luxury cursor —— */
  function initLuxuryCursor() {
    if (prefersReducedMotion() || window.matchMedia('(max-width: 900px)').matches) return;
    var ring = document.createElement('div');
    ring.className = 'hero-cursor-ring';
    document.body.appendChild(ring);
    var visible = false;
    var rx = 0;
    var ry = 0;
    var tx = 0;
    var ty = 0;
    var raf = null;

    function loop() {
      rx += (tx - rx) * 0.18;
      ry += (ty - ry) * 0.18;
      ring.style.left = rx + 'px';
      ring.style.top = ry + 'px';
      raf = requestAnimationFrame(loop);
    }

    hero.addEventListener(
      'mouseenter',
      function () {
        visible = true;
        ring.classList.add('is-active');
        if (!raf) raf = requestAnimationFrame(loop);
      },
      { passive: true }
    );

    hero.addEventListener(
      'mouseleave',
      function () {
        visible = false;
        ring.classList.remove('is-active', 'is-hover');
      },
      { passive: true }
    );

    hero.addEventListener(
      'mousemove',
      function (e) {
        if (!visible) return;
        tx = e.clientX;
        ty = e.clientY;
      },
      { passive: true }
    );

    hero.addEventListener(
      'mouseover',
      function (e) {
        if (e.target.closest('a, button, [data-magnetic]')) ring.classList.add('is-hover');
        else ring.classList.remove('is-hover');
      },
      { passive: true }
    );
  }

  /* —— Rotating headlines (word-level — prevents mid-word breaks) —— */
  function wrapWords(text) {
    return text
      .split(/\s+/)
      .filter(Boolean)
      .map(function (word, i) {
        return (
          '<span class="hero-word" style="--word-i:' +
          i +
          '"><span class="hero-word-inner">' +
          word +
          '</span></span>'
        );
      })
      .join(' ');
  }

  function showHeadline(index) {
    if (!headlineEl) return;
    var text = headlines[index % headlines.length];
    if (!text) return;

    headlineEl.classList.remove('is-visible');
    headlineEl.classList.add('is-exit');

    window.setTimeout(function () {
      headlineEl.innerHTML = wrapWords(text);
      headlineEl.classList.remove('is-exit');
      void headlineEl.offsetWidth;
      headlineEl.classList.add('is-visible');
      if (seoTitle) seoTitle.textContent = text;
    }, prefersReducedMotion() ? 0 : 320);
  }

  function startHeadlineRotation() {
    if (!headlineEl || headlines.length < 2 || prefersReducedMotion()) {
      if (headlineEl && headlines[0]) {
        headlineEl.innerHTML = wrapWords(headlines[0]);
        headlineEl.classList.add('is-visible');
      }
      return;
    }
    showHeadline(0);
    headlineTimer = window.setInterval(function () {
      headlineIndex = (headlineIndex + 1) % headlines.length;
      showHeadline(headlineIndex);
    }, 5200);
  }

  function setHeadlines(list) {
    if (!list || !list.length) return;
    headlines = list.filter(Boolean);
    headlineIndex = 0;
    if (headlineTimer) window.clearInterval(headlineTimer);
    startHeadlineRotation();
  }

  /* —— Video —— */
  function initVideo() {
    if (!video) return;
    var src = video.getAttribute('data-src');
    if (!src) return;
    video.src = src;
    video.load();
    var play = function () {
      hero.classList.add('is-video-active');
      video.play().catch(function () {
        hero.classList.remove('is-video-active');
      });
    };
    if (video.readyState >= 2) play();
    else video.addEventListener('canplay', play, { once: true });
    video.addEventListener('error', function () {
      hero.classList.remove('is-video-active');
    });
  }

  /* —— Lazy slides —— */
  function lazyHeroSlides() {
    if (!slider) return;
    var slides = slider.querySelectorAll('img.hero-slide');
    slides.forEach(function (img, i) {
      if (i === 0) return;
      var src = img.getAttribute('src');
      if (!src) return;
      img.removeAttribute('src');
      img.dataset.lazySrc = src;
    });
    function loadNext(index) {
      var slide = slides[index];
      if (!slide || slide.getAttribute('src')) return;
      var lazy = slide.dataset.lazySrc;
      if (lazy) slide.setAttribute('src', lazy);
    }
    document.addEventListener('spangle:hero-slide-change', function (e) {
      var idx = e.detail && e.detail.index;
      if (typeof idx === 'number') {
        loadNext((idx + 1) % slides.length);
        loadNext((idx + 2) % slides.length);
      }
    });
  }

  /* —— Impact panel —— */
  function renderImpactPanel(stats) {
    if (!impactGrid || !stats || !stats.length) return;
    impactGrid.innerHTML = stats
      .slice(0, 4)
      .map(function (row) {
        return (
          '<div class="hero-impact-item hero-glass-stat">' +
          '<strong class="hero-glass-value" data-count="' +
          String(row.value || '').replace(/"/g, '') +
          '">' +
          (row.value || '') +
          '</strong>' +
          impactLabelHtml(row.label) +
          '</div>'
        );
      })
      .join('');
    observeImpactStats();
  }

  function renderGlassStats(stats) {
    renderImpactPanel(stats);
  }

  function animateStat(el) {
    var raw = el.getAttribute('data-count') || el.textContent;
    var match = String(raw).match(/^([\d,.]+)(\+?)(.*)$/);
    if (!match) return;
    var target = parseFloat(match[1].replace(/,/g, ''));
    if (isNaN(target) || target <= 0) return;
    var suffix = (match[2] || '') + (match[3] || '');
    var start = performance.now();
    var duration = 1400;
    function frame(now) {
      var p = Math.min((now - start) / duration, 1);
      var eased = 1 - Math.pow(1 - p, 3);
      el.textContent = String(Math.round(target * eased)) + suffix;
      if (p < 1) requestAnimationFrame(frame);
    }
    requestAnimationFrame(frame);
  }

  function observeImpactStats() {
    if (!impactRoot || prefersReducedMotion()) return;
    var io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          entry.target.querySelectorAll('.hero-glass-value[data-count]').forEach(animateStat);
          io.unobserve(entry.target);
        });
      },
      { threshold: 0.15 }
    );
    io.observe(impactRoot);
  }

  /* —— Project preview —— */
  function getPreviewRoot() {
    return document.getElementById('hero-project-preview');
  }

  function escHtml(str) {
    return String(str || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  var PREVIEW_FALLBACKS = [
    'uploads/ENTRY.jpg',
    'uploads/1228_HARESHBHAI_LIVING_5.jpg',
    'uploads/1159-VISALBHAI%20RAMPARIYA-5.jpg',
    'uploads/LIVING%2001.jpg',
  ];

  function encodePathSegments(path) {
    return String(path || '')
      .trim()
      .replace(/\\/g, '/')
      .split('/')
      .map(function (seg) {
        if (!seg) return seg;
        try {
          return encodeURIComponent(decodeURIComponent(seg));
        } catch (e) {
          return encodeURIComponent(seg);
        }
      })
      .join('/');
  }

  function resolveMedia(path, data) {
    if (!path) return '';
    var p = String(path).trim().replace(/\\/g, '/');
    if (/^https?:\/\//i.test(p)) return p;
    if (!/^uploads\//i.test(p)) p = 'uploads/' + p.replace(/^\//, '');
    p = encodePathSegments(p);
    var base = data && data.publicBase ? String(data.publicBase).replace(/\/$/, '') : '';
    if (!base || base.indexOf('localhostapi') !== -1 || base.indexOf('localhostscripts') !== -1) {
      return p;
    }
    return base + '/' + p;
  }

  function cleanSlideAlt(alt) {
    return (
      String(alt || '')
        .replace(/\s+/g, ' ')
        .trim() || 'Featured interior'
    );
  }

  function fileKey(path) {
    var name = String(path || '').split('/').pop() || '';
    try {
      name = decodeURIComponent(name);
    } catch (e) {
      /* keep */
    }
    return name.toLowerCase().replace(/[^a-z0-9]+/g, '');
  }

  function findProjectForImage(imagePath, projects) {
    if (!imagePath || !projects || !projects.length) return null;
    var key = fileKey(imagePath);
    if (!key) return null;
    var match = null;
    projects.forEach(function (p) {
      if (!p || !p.heroImage) return;
      var pKey = fileKey(p.heroImage);
      if (!pKey) return;
      if (pKey === key || pKey.indexOf(key) !== -1 || key.indexOf(pKey) !== -1) {
        if (!match || isUsableProjectTitle(p.title)) match = p;
      }
    });
    return match;
  }

  function formatProjectType(p) {
    var t = p.projectType || p.category || '';
    if (!t) return 'Residential';
    return t.charAt(0).toUpperCase() + t.slice(1).replace(/-/g, ' ');
  }

  function isUsableProjectTitle(title) {
    var t = String(title || '').trim();
    return t.length > 2 && !/^\d+$/.test(t);
  }

  function getActiveSlideIndex() {
    var slides = document.querySelectorAll('#hero-slider img.hero-slide');
    for (var i = 0; i < slides.length; i++) {
      if (slides[i].classList.contains('active')) return i;
    }
    return 0;
  }

  function getSlideImageUrl(index, data) {
    data = data || window.__SPANGLE_SITE__ || {};
    var domSlides = document.querySelectorAll('#hero-slider img.hero-slide');
    var dom = domSlides[index];
    if (dom) {
      if (dom.currentSrc && dom.currentSrc.indexOf('data:') !== 0) return dom.currentSrc;
      if (dom.src && dom.src.indexOf('data:') !== 0 && !dom.src.endsWith('/')) return dom.src;
      if (dom.dataset.lazySrc) return resolveMedia(dom.dataset.lazySrc, data);
    }
    var cms = (data.heroSlides || [])[index];
    if (cms && cms.src) return resolveMedia(cms.src, data);
    return resolveMedia(PREVIEW_FALLBACKS[index % PREVIEW_FALLBACKS.length], data);
  }

  function getSlideSourceAt(index, data) {
    data = data || window.__SPANGLE_SITE__ || {};
    var cmsSlides = data.heroSlides || [];
    var alt = cmsSlides[index] ? cmsSlides[index].alt || '' : '';
    var domSlides = document.querySelectorAll('#hero-slider img.hero-slide');
    if (!alt && domSlides[index]) alt = domSlides[index].getAttribute('alt') || '';
    return {
      src: getSlideImageUrl(index, data),
      alt: alt,
    };
  }

  function buildPreviewCard(opts) {
    var img = opts.img
      ? '<img src="' +
        escHtml(opts.img) +
        '" alt="' +
        escHtml(opts.title || 'Featured project') +
        '" width="320" height="180" loading="eager" decoding="async" fetchpriority="low" />'
      : '';
    return (
      '<a class="hero-preview-card" href="' +
      escHtml(opts.href) +
      '">' +
      '<div class="hero-preview-thumb">' +
      img +
      '</div>' +
      '<div class="hero-preview-body">' +
      '<p class="hero-preview-kicker">Featured Project</p>' +
      '<p class="hero-preview-title">' +
      escHtml(opts.title) +
      '</p>' +
      '<p class="hero-preview-meta">' +
      escHtml(opts.meta) +
      '</p>' +
      '</div></a>'
    );
  }

  function wirePreviewImage(root, data) {
    var img = root && root.querySelector('.hero-preview-thumb img');
    if (!img || img.getAttribute('data-preview-wired')) return;
    img.setAttribute('data-preview-wired', '1');
    var step = 0;
    img.addEventListener('error', function () {
      if (step >= PREVIEW_FALLBACKS.length) return;
      img.src = resolveMedia(PREVIEW_FALLBACKS[step], data);
      step += 1;
    });
  }

  function renderPreviewAtIndex(index, data) {
    var root = getPreviewRoot();
    if (!root) return;
    data = data || window.__SPANGLE_SITE__ || {};
    var projects = data.projects || [];

    var slide = getSlideSourceAt(index, data);
    if (!slide || !slide.src) {
      slide = { src: resolveMedia(PREVIEW_FALLBACKS[0], data), alt: 'Entry foyer interior' };
    }

    var img = slide.src;
    var matched = findProjectForImage(slide.src, projects);
    var title = cleanSlideAlt(slide.alt);
    var href = 'work.html';
    var meta = 'Rajkot, Gujarat · Residential';

    if (matched) {
      if (isUsableProjectTitle(matched.title)) title = matched.title;
      href = matched.linkUrl || href;
      meta = [matched.location, matched.area || '', formatProjectType(matched)].filter(Boolean).join(' · ') || meta;
    }

    root.innerHTML = buildPreviewCard({ img: img, href: href, title: title, meta: meta });
    wirePreviewImage(root, data);
  }

  function renderProjectPreview(projects, data) {
    renderPreviewAtIndex(getActiveSlideIndex(), data);
  }

  function onHeroSlideChange(e) {
    var idx = e.detail && e.detail.index;
    if (typeof idx !== 'number') return;
    renderPreviewAtIndex(idx, window.__SPANGLE_SITE__);
  }

  /* —— Magnetic + ripple CTAs —— */
  function initCtaInteractions() {
    var buttons = hero.querySelectorAll('[data-magnetic], .hero-actions .btn');
    buttons.forEach(function (btn) {
      btn.addEventListener(
        'mousemove',
        function (e) {
          if (window.matchMedia('(max-width: 900px)').matches) return;
          var rect = btn.getBoundingClientRect();
          var x = e.clientX - rect.left;
          var y = e.clientY - rect.top;
          var dx = (x - rect.width / 2) * 0.12;
          var dy = (y - rect.height / 2) * 0.12;
          btn.style.transform = 'translate3d(' + dx + 'px, ' + dy + 'px, 0)';
          btn.style.setProperty('--ripple-x', (x / rect.width) * 100 + '%');
          btn.style.setProperty('--ripple-y', (y / rect.height) * 100 + '%');
        },
        { passive: true }
      );
      btn.addEventListener('mouseleave', function () {
        btn.style.transform = '';
      });
      btn.addEventListener('click', function (e) {
        btn.classList.add('is-ripple');
        window.setTimeout(function () {
          btn.classList.remove('is-ripple');
        }, 450);
      });
    });
  }

  /* —— Luxury scroll —— */
  function initScrollLuxury() {
    if (!scrollBtn) return;
    scrollBtn.addEventListener('click', function () {
      var target = document.getElementById('about') || document.querySelector('.stats-bar, .section');
      if (!target) return;
      target.scrollIntoView({ behavior: prefersReducedMotion() ? 'auto' : 'smooth', block: 'start' });
    });
  }

  /* —— CMS data —— */
  function onSiteData(e) {
    var data = (e && e.detail) || window.__SPANGLE_SITE__;
    if (!data) return;

    if (data.home && data.home.heroTitleHtml) {
      var cmsLine = stripHtml(data.home.heroTitleHtml);
      if (cmsLine) {
        var merged = [cmsLine];
        DEFAULT_HEADLINES.forEach(function (h) {
          if (h !== cmsLine) merged.push(h);
        });
        setHeadlines(merged);
      }
    }

    if (data.home && data.home.stats) renderImpactPanel(data.home.stats);
    renderProjectPreview(data.projects, data);

    if (video && data.home && data.home.heroVideoUrl) {
      video.setAttribute('data-src', data.home.heroVideoUrl);
      initVideo();
    }
  }

  document.addEventListener('spangle:site-data', onSiteData);
  document.addEventListener('spangle:content-updated', function () {
    if (window.__SPANGLE_SITE__) onSiteData({ detail: window.__SPANGLE_SITE__ });
  });
  document.addEventListener('spangle:hero-slide-change', onHeroSlideChange);
  document.addEventListener('spangle:hero-slides-rendered', function () {
    renderPreviewAtIndex(getActiveSlideIndex(), window.__SPANGLE_SITE__);
  });

  initScrollParallax();
  initMouseParallax();
  initLuxuryCursor();
  initCtaInteractions();
  initScrollLuxury();
  lazyHeroSlides();
  observeImpactStats();
  startHeadlineRotation();

  if (window.__SPANGLE_SITE__) onSiteData({ detail: window.__SPANGLE_SITE__ });

  window.SpangleHero = {
    renderGlassStats: renderGlassStats,
    renderImpactPanel: renderImpactPanel,
    renderProjectPreview: renderProjectPreview,
    setHeadlines: setHeadlines,
    getDefaultHeadlines: function () {
      return DEFAULT_HEADLINES.slice();
    },
  };
})();
