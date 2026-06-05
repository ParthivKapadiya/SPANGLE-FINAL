(function () {
  'use strict';

  if (!document.body.classList.contains('page-work')) return;

  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var heroTimer;
  var heroIndex = 0;
  var revealObserver = null;

  function $(sel, root) {
    return (root || document).querySelector(sel);
  }

  function $$(sel, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(sel));
  }

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function isLocalHost() {
    var h = (window.location && window.location.hostname) || '';
    return h === 'localhost' || h === '127.0.0.1';
  }

  function appBase() {
    var scripts = document.getElementsByTagName('script');
    var i;
    var src;
    for (i = scripts.length - 1; i >= 0; i--) {
      src = scripts[i].getAttribute('src') || '';
      if (
        src.indexOf('content-bridge') !== -1 ||
        src.indexOf('site-data.js') !== -1 ||
        src.indexOf('page-content.js') !== -1 ||
        src.indexOf('work-premium.js') !== -1
      ) {
        try {
          return new URL(src, window.location.href).href.replace(
            /\/(js\/content-bridge\.js|js\/page-content\.js|js\/work-premium\.js|api\/site-data\.js\.php)(\?.*)?$/i,
            ''
          );
        } catch (e) {
          break;
        }
      }
    }
    return (
      window.location.origin + window.location.pathname.replace(/\/[^/]*$/, '')
    ).replace(/\/$/, '');
  }

  function mediaSrc(path) {
    if (!path) return '';
    var p = String(path).trim();
    if (/^https?:\/\//i.test(p)) return p;
    if (/^(uploads\/|\.\/)/.test(p)) return p;
    var base = isLocalHost()
      ? appBase()
      : (window.__SPANGLE_SITE__ && window.__SPANGLE_SITE__.publicBase) || appBase();
    base = String(base).replace(/\/$/, '');
    return (
      base +
      '/' +
      p.split('/').map(function (seg) {
        try {
          return encodeURIComponent(decodeURIComponent(seg));
        } catch (e) {
          return encodeURIComponent(seg);
        }
      }).join('/')
    );
  }

  function typeLabel(t) {
    return String(t || '')
      .replace(/-/g, ' ')
      .replace(/\b\w/g, function (c) {
        return c.toUpperCase();
      });
  }

  function featuredProjects(projects) {
    var featured = projects.filter(function (p) {
      return p.isFeatured;
    });
    return featured.length ? featured : projects.slice(0, 5);
  }

  function applyHero(page, data) {
    if (!page) return;
    var k = $('.wrk-hero__kicker');
    var t = $('.wrk-hero__title');
    var l = $('.wrk-hero__lead');
    if (k && page.kicker) k.textContent = page.kicker;
    if (t && page.title) t.innerHTML = String(page.title).replace(/\n/g, '<br />');
    if (l && page.lead) l.textContent = page.lead;

    var slides = featuredProjects(data.projects || []);
    if (!slides.length && page.heroImage) {
      slides = [{ heroImage: page.heroImage, title: page.title || 'Work' }];
    }
    initHeroSlideshow(slides, page.heroImage);
    renderHeroStats(data);
  }

  function renderHeroStats(data) {
    var grid = $('#wrk-hero-stats');
    if (!grid) return;
    var stats = (data.stats && data.stats.length >= 4)
      ? data.stats.slice(0, 4)
      : [
          { value: '150+', label: 'Projects' },
          { value: '16+', label: 'Years' },
          { value: '2M+', label: 'Sq Ft Designed' },
          { value: '98%', label: 'Client Satisfaction' },
        ];
    grid.innerHTML = stats
      .map(function (s) {
        return (
          '<div class="wrk-hero-stat wrk-reveal"><strong data-count="' +
          esc(s.value) +
          '">' +
          esc(s.value) +
          '</strong><span>' +
          esc(s.label) +
          '</span></div>'
        );
      })
      .join('');
  }

  function initHeroSlideshow(slides, fallbackImage) {
    var container = $('#wrk-hero-slides');
    var progress = $('#wrk-hero-progress');
    if (!container || !slides.length) return;

    container.innerHTML = slides
      .map(function (p, i) {
        var img = mediaSrc(p.heroImage || fallbackImage || '');
        return (
          '<div class="wrk-hero__slide' +
          (i === 0 ? ' is-active' : '') +
          '" style="background-image:url(\'' +
          img.replace(/'/g, '%27') +
          '\');" role="img" aria-label="' +
          esc(p.title || 'Project') +
          '"></div>'
        );
      })
      .join('');

    if (progress && slides.length > 1) {
      progress.innerHTML = slides
        .map(function (_, i) {
          return (
            '<button type="button" class="wrk-hero__dot' +
            (i === 0 ? ' is-active' : '') +
            '" data-slide="' +
            i +
            '" aria-label="Slide ' +
            (i + 1) +
            '"><span class="wrk-hero__dot-fill"></span></button>'
          );
        })
        .join('');
      progress.addEventListener('click', function (e) {
        var dot = e.target.closest('[data-slide]');
        if (!dot) return;
        goToSlide(parseInt(dot.getAttribute('data-slide'), 10));
        restartHeroTimer(slides.length);
      });
    }

    heroIndex = 0;
    if (slides.length > 1 && !reduced) {
      restartHeroTimer(slides.length);
    }
  }

  function goToSlide(n) {
    var slideEls = $$('.wrk-hero__slide');
    var dots = $$('.wrk-hero__dot');
    if (!slideEls.length) return;
    slideEls[heroIndex].classList.remove('is-active');
    if (dots[heroIndex]) dots[heroIndex].classList.remove('is-active');
    heroIndex = ((n % slideEls.length) + slideEls.length) % slideEls.length;
    slideEls[heroIndex].classList.add('is-active');
    if (dots[heroIndex]) dots[heroIndex].classList.add('is-active');
  }

  function restartHeroTimer(count) {
    clearInterval(heroTimer);
    heroTimer = setInterval(function () {
      goToSlide(heroIndex + 1);
    }, 6000);
  }

  function renderFeatured(projects) {
    var card = $('#wrk-featured-card');
    if (!card || !projects.length) return;
    var p = projects.find(function (x) {
      return x.isFeatured;
    }) || projects[0];
    var meta = [];
    if (p.location) meta.push('<li>' + esc(p.location) + '</li>');
    if (p.area) meta.push('<li>' + esc(p.area) + '</li>');
    if (p.year) meta.push('<li>' + esc(String(p.year)) + '</li>');
    var scope = p.servicesProvided || typeLabel(p.projectType);
    var story = p.summary || '';
    card.innerHTML =
      '<div class="wrk-featured__media">' +
      '<img src="' +
      esc(mediaSrc(p.heroImage)) +
      '" alt="' +
      esc(p.title) +
      '" loading="eager" width="900" height="600" decoding="async" />' +
      '<span class="wrk-featured__badge">' +
      esc(typeLabel(p.projectType)) +
      '</span></div>' +
      '<div class="wrk-featured__copy">' +
      (meta.length ? '<ul class="wrk-featured__meta">' + meta.join('') + '</ul>' : '') +
      '<h3 class="wrk-featured__title">' +
      esc(p.title) +
      '</h3>' +
      '<p class="wrk-featured__scope">' +
      esc(scope) +
      '</p>' +
      (story ? '<p class="wrk-featured__story">' + esc(story) + '</p>' : '') +
      '<a href="' +
      esc(p.linkUrl || 'work.html') +
      '" class="wrk-featured__cta">View case study <span aria-hidden="true">→</span></a>' +
      '</div>';
    card.classList.add('wrk-reveal');
  }

  function renderImpact(data) {
    var grid = $('#wrk-impact-grid');
    if (!grid) return;
    var stats = data.stats && data.stats.length
      ? data.stats
      : [
          { value: '150+', label: 'Projects' },
          { value: '2M+', label: 'Sq Ft' },
          { value: '16+', label: 'Years' },
          { value: '50+', label: 'Happy Clients' },
        ];
    grid.innerHTML = stats
      .map(function (s) {
        return (
          '<div class="wrk-impact-stat wrk-reveal is-ready">' +
          '<strong data-count="' +
          esc(s.value) +
          '">' +
          esc(s.value) +
          '</strong>' +
          '<span>' +
          esc(s.label) +
          '</span></div>'
        );
      })
      .join('');
    registerReveals(grid);
  }

  var CATEGORIES = [
    { filter: 'residential', label: 'Residential', icon: 'fa-house' },
    { filter: 'commercial', label: 'Commercial', icon: 'fa-building' },
    { filter: 'villa', label: 'Villa', icon: 'fa-landmark' },
    { filter: 'office', label: 'Office', icon: 'fa-briefcase' },
    { filter: 'hospitality', label: 'Hospitality', icon: 'fa-hotel' },
    { filter: 'interior', label: 'Interiors', icon: 'fa-couch' },
    { filter: 'architecture', label: 'Architecture', icon: 'fa-drafting-compass' },
  ];

  function countByCategory(projects, filter) {
    if (!window.SpangleWorkProjects) {
      return projects.filter(function (p) {
        var cat = (p.projectType || p.category || '').toLowerCase();
        if (filter === 'all') return true;
        if (filter === 'villa') return /\bvilla\b/i.test((p.title || '') + ' ' + (p.location || ''));
        if (filter === 'office') return cat === 'commercial' || /\boffice\b/i.test(p.title || '');
        if (filter === 'interior') return cat === 'interior';
        if (filter === 'architecture') return cat === 'architecture';
        return cat === filter;
      }).length;
    }
    var prev = window.SpangleWorkProjects.getState();
    window.SpangleWorkProjects.setFilter({ category: filter, scroll: false });
    var n = window.SpangleWorkProjects.getFiltered().length;
    window.SpangleWorkProjects.setFilter({
      category: prev.category,
      type: prev.type,
      sort: prev.sort,
      search: prev.search,
      page: prev.page,
      scroll: false,
    });
    return n;
  }

  function renderCategories(projects) {
    var grid = $('#wrk-cat-grid');
    if (!grid) return;
    grid.innerHTML = CATEGORIES.map(function (cat) {
      var sample = projects.find(function (p) {
        return (p.projectType || '').toLowerCase() === cat.filter || (cat.filter === 'villa' && /\bvilla\b/i.test(p.title || ''));
      });
      var img = sample && sample.heroImage ? mediaSrc(sample.heroImage) : '';
      var count = countByCategory(projects, cat.filter);
      return (
        '<button type="button" class="wrk-cat-card wrk-reveal" data-filter="' +
        esc(cat.filter) +
        '" style="--wrk-cat-img:url(\'' +
        (img ? img.replace(/'/g, '%27') : '') +
        '\')">' +
        '<span class="wrk-cat-card__label">' +
        esc(cat.label) +
        '</span>' +
        '<span class="wrk-cat-card__count">' +
        count +
        ' project' +
        (count === 1 ? '' : 's') +
        '</span></button>'
      );
    }).join('');

    grid.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-filter]');
      if (!btn || !window.SpangleWorkProjects) return;
      window.SpangleWorkProjects.setFilter({ category: btn.getAttribute('data-filter') });
    });
  }

  function renderTestimonials(data) {
    var track = $('#wrk-trust-track');
    if (!track) return;
    var items = data.testimonials || [];
    if (!items.length) {
      items = [
        { quote: 'Archevo handled architecture and interiors as one team — communication was clear at every milestone.', authorName: 'Residential client', authorRole: 'Rajkot' },
        { quote: 'The design quality exceeded our expectations, and execution stayed on schedule through handover.', authorName: 'Commercial client', authorRole: 'Gujarat' },
        { quote: 'Single-point responsibility made our turnkey project straightforward — we always knew who to call.', authorName: 'Villa commission', authorRole: 'Western India' },
      ];
    }
    track.innerHTML = items
      .slice(0, 3)
      .map(function (t) {
        return (
          '<article class="wrk-testimonial wrk-reveal is-ready">' +
          '<blockquote>&ldquo;' +
          esc(t.quote) +
          '&rdquo;</blockquote>' +
          '<cite><strong>' +
          esc(t.authorName || '') +
          '</strong>' +
          esc(t.authorRole || '') +
          '</cite></article>'
        );
      })
      .join('');
    registerReveals(track);
  }

  function timelineYearMarkup(label, count) {
    return (
      '<div class="wrk-timeline__year wrk-reveal is-ready">' +
      '<span class="wrk-timeline__dot" aria-hidden="true"></span>' +
      '<span class="wrk-timeline__label">' +
      esc(label) +
      '</span>' +
      '<span class="wrk-timeline__count">' +
      esc(count) +
      '</span></div>'
    );
  }

  function renderTimeline(projects, data) {
    var track = $('#wrk-timeline-track');
    if (!track) return;
    var byYear = {};
    projects.forEach(function (p) {
      if (!p.year) return;
      byYear[p.year] = (byYear[p.year] || 0) + 1;
    });
    var years = Object.keys(byYear)
      .map(Number)
      .sort(function (a, b) {
        return a - b;
      });

    if (years.length) {
      track.classList.remove('wrk-timeline--milestones');
      track.innerHTML = years
        .map(function (y) {
          var n = byYear[y];
          return timelineYearMarkup(
            String(y),
            n + ' project' + (n === 1 ? '' : 's')
          );
        })
        .join('');
      registerReveals(track);
      return;
    }

    var stats = (data && data.stats) || [];
    var milestones = stats.length
      ? stats.slice(0, 4).map(function (s) {
          return { label: s.value, count: s.label };
        })
      : [
          { label: String(projects.length || '150') + '+', count: 'Projects delivered' },
          { label: '16+', count: 'Years experience' },
          { label: '2M+', count: 'Sq ft designed' },
          { label: '50+', count: 'Happy clients' },
        ];

    track.classList.add('wrk-timeline--milestones');
    track.innerHTML = milestones.map(function (m) {
      return timelineYearMarkup(m.label, m.count);
    }).join('');
    registerReveals(track);
  }

  var TRUST_FALLBACK = [
    { icon: 'fa-solid fa-layer-group', title: 'Single Point Responsibility', body: 'One studio accountable from concept through handover — no fragmented vendors.' },
    { icon: 'fa-solid fa-pen-ruler', title: 'Architecture + Interiors', body: 'Structure, space planning, and interior detail conceived as one coherent story.' },
    { icon: 'fa-solid fa-helmet-safety', title: 'Construction Coordination', body: 'Site supervision and contractor alignment so design intent survives on site.' },
    { icon: 'fa-solid fa-key', title: 'Turnkey Delivery', body: 'End-to-end project management — approvals, build, finishes, and final handover.' },
    { icon: 'fa-solid fa-file-signature', title: 'Plan Approvals', body: 'Municipal drawings, compliance checks, and authority submissions handled in-house.' },
  ];

  function renderTrust(page) {
    var grid = $('#wrk-trust-grid');
    if (!grid) return;
    var items = TRUST_FALLBACK;
    if (page && page.trustItems && page.trustItems.length) {
      items = page.trustItems;
    }
    grid.innerHTML = items
      .map(function (item) {
        return (
          '<article class="wrk-trust-card wrk-reveal is-ready">' +
          '<div class="wrk-trust-card__icon"><i class="' +
          esc(item.icon || 'fa-solid fa-check') +
          '" aria-hidden="true"></i></div>' +
          '<h3>' +
          esc(item.title) +
          '</h3>' +
          '<p>' +
          esc(item.body) +
          '</p></article>'
        );
      })
      .join('');
    registerReveals(grid);
  }

  function applyCta(copy, page) {
    var c = copy || {};
    var p = page || {};
    function setText(sel, val) {
      var el = $(sel);
      if (el && val) el.textContent = val;
    }
    function setHtml(sel, val) {
      var el = $(sel);
      if (el && val) el.innerHTML = String(val).replace(/\n/g, '<br />');
    }
    setText('.site-work-cta-eyebrow', p.ctaEyebrow || c.work_cta_final_eyebrow);
    setText('.site-work-cta-final-title', p.ctaTitle || c.work_cta_final_title);
    setHtml('.site-work-cta-final-sub', p.ctaSub || c.work_cta_final_sub);
    setText('.site-work-cta-text', c.work_cta_text);
    setText('.site-work-cta-final-btn', c.work_cta_final_btn_text || c.work_cta_btn_text);
    setText('.site-work-cta-btn-secondary', c.work_cta_final_btn2_text);
    var btn = $('.site-work-cta-final-btn');
    if (btn && c.work_cta_btn_url) btn.setAttribute('href', c.work_cta_btn_url);
    setText('.site-work-featured-eyebrow', p.featuredEyebrow);
    setText('.site-work-stats-eyebrow', p.statsEyebrow);
    setText('.site-work-stats-title', p.statsTitle);
    setText('.site-work-categories-eyebrow', p.categoriesEyebrow);
    setText('.site-work-categories-title', p.categoriesTitle);
    setText('.site-work-testimonials-eyebrow', p.testimonialsEyebrow);
    setText('.site-work-testimonials-title', p.testimonialsTitle);
    setText('.site-work-timeline-eyebrow', p.timelineEyebrow);
    setText('.site-work-timeline-title', p.timelineTitle);
    setText('.site-work-timeline-intro', p.timelineIntro);
    setText('.site-work-trust-eyebrow', p.trustEyebrow);
    setText('.site-work-trust-title', p.trustTitle);
  }

  function parseCountValue(raw) {
    var s = String(raw || '').trim();
    var m = s.match(/^([\d,.]+)(.*)$/);
    if (!m) return { num: 0, suffix: s, decimals: 0 };
    var numStr = m[1].replace(/,/g, '');
    var suffix = m[2] || '';
    var decimals = (numStr.split('.')[1] || '').length;
    return { num: parseFloat(numStr) || 0, suffix: suffix, decimals: decimals };
  }

  function animateCounters() {
    if (reduced) return;
    $$('[data-count]').forEach(function (el) {
      var raw = el.getAttribute('data-count');
      var parsed = parseCountValue(raw);
      if (!parsed.num) return;
      var start = 0;
      var duration = 1600;
      var startTime = null;
      function tick(ts) {
        if (!startTime) startTime = ts;
        var p = Math.min((ts - startTime) / duration, 1);
        var eased = 1 - Math.pow(1 - p, 3);
        var current = start + (parsed.num - start) * eased;
        var shown =
          parsed.decimals > 0
            ? current.toFixed(parsed.decimals)
            : Math.round(current).toLocaleString();
        el.textContent = shown + parsed.suffix;
        if (p < 1) requestAnimationFrame(tick);
      }
      requestAnimationFrame(tick);
    });
  }

  function showReveal(el) {
    if (!el) return;
    el.classList.add('is-in', 'is-ready');
    el.style.removeProperty('opacity');
    el.style.removeProperty('transform');
  }

  function registerReveals(root) {
    var scope = root || document;
    var nodes = $$('.wrk-reveal:not(.is-in)', scope);
    if (!nodes.length) return;

    nodes.forEach(function (el) {
      if (el.classList.contains('is-ready')) {
        showReveal(el);
        return;
      }
      if (reduced || !window.gsap || !window.ScrollTrigger) {
        showReveal(el);
        return;
      }
      if (el.dataset.wrkRevealBound === '1') return;
      el.dataset.wrkRevealBound = '1';
      gsap.fromTo(
        el,
        { opacity: 0, y: 36 },
        {
          opacity: 1,
          y: 0,
          duration: 0.95,
          ease: 'power3.out',
          onComplete: function () {
            showReveal(el);
          },
          scrollTrigger: {
            trigger: el,
            start: 'top 92%',
            toggleActions: 'play none none none',
          },
        }
      );
    });

    if (reduced || !window.gsap) {
      return;
    }

    if (!revealObserver && 'IntersectionObserver' in window) {
      revealObserver = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              showReveal(entry.target);
              revealObserver.unobserve(entry.target);
            }
          });
        },
        { threshold: 0.08, rootMargin: '0px 0px -4% 0px' }
      );
    }

    if (revealObserver) {
      nodes.forEach(function (el) {
        if (!el.classList.contains('is-in') && el.dataset.wrkRevealBound !== '1') {
          revealObserver.observe(el);
        }
      });
    }
  }

  function initRevealFallback() {
    registerReveals(document);
  }

  function loadScript(src) {
    return new Promise(function (resolve, reject) {
      var s = document.createElement('script');
      s.src = src;
      s.defer = true;
      s.onload = resolve;
      s.onerror = reject;
      document.body.appendChild(s);
    });
  }

  function initGsap() {
    if (window.ScrollTrigger) gsap.registerPlugin(ScrollTrigger);
    registerReveals(document);

    var heroSlides = $('#wrk-hero-slides');
    if (heroSlides && window.ScrollTrigger) {
      gsap.to(heroSlides, {
        yPercent: 10,
        ease: 'none',
        scrollTrigger: {
          trigger: '.wrk-hero',
          start: 'top top',
          end: 'bottom top',
          scrub: true,
        },
      });
    }
  }

  function loadGsap() {
    if (reduced) {
      $$('.wrk-reveal').forEach(function (el) {
        el.classList.add('is-in');
      });
      return;
    }
    var base = 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/';
    loadScript(base + 'gsap.min.js')
      .then(function () {
        return loadScript(base + 'ScrollTrigger.min.js');
      })
      .then(initGsap)
      .catch(initRevealFallback);
  }

  function applyCtaBackground(page) {
    var bg = $('.wrk-cta__bg');
    if (!bg) return;
    var img = (page && page.heroImage) || 'uploads/054-KANTILAL-3D-6.jpg';
    var src = mediaSrc(img);
    if (src) {
      bg.style.setProperty('--wrk-cta-image', "url('" + src.replace(/'/g, '%27') + "')");
    }
  }

  function hydrate(data) {
    if (!data) return;
    var page = data.pages && data.pages.work;
    var projects = data.projects || [];
    applyHero(page, data);
    renderFeatured(projects);
    renderImpact(data);
    renderCategories(projects);
    renderTestimonials(data);
    renderTimeline(projects, data);
    renderTrust(page);
    applyCta(data.copy, page);
    applyCtaBackground(page);
    animateCounters();
    showReveal($('.wrk-cta__inner'));
    registerReveals(document);
  }

  function onData(e) {
    hydrate(e.detail || window.__SPANGLE_SITE__);
  }

  document.addEventListener('spangle:site-data', onData);
  document.addEventListener('spangle:content-updated', function () {
    if (window.__SPANGLE_SITE__) hydrate(window.__SPANGLE_SITE__);
  });

  if (window.__SPANGLE_SITE__) hydrate(window.__SPANGLE_SITE__);

  function revealSafetyNet() {
    window.setTimeout(function () {
      $$('.wrk-reveal:not(.is-in)').forEach(showReveal);
    }, 1800);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      loadGsap();
      revealSafetyNet();
    });
  } else {
    loadGsap();
    revealSafetyNet();
  }
})();
