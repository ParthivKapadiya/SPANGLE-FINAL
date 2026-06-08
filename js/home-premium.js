(function () {
  'use strict';

  if (!document.body.classList.contains('home')) return;

  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function $(sel, root) {
    return (root || document).querySelector(sel);
  }

  function $$(sel, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(sel));
  }

  /* —— Load curtain —— */
  function initLoadCurtain() {
    var curtain = document.createElement('div');
    curtain.className = 'hero-load-curtain';
    curtain.id = 'hero-load-curtain';
    curtain.setAttribute('aria-hidden', 'true');
    document.body.prepend(curtain);

    function done() {
      document.body.classList.add('is-loaded');
      window.setTimeout(function () {
        if (curtain.parentNode) curtain.parentNode.removeChild(curtain);
      }, 1200);
    }

    if (document.readyState === 'complete') done();
    else window.addEventListener('load', done, { once: true });
    window.setTimeout(done, 3200);
  }

  /* —— Trust strip —— */
  function trustStripIcon(row) {
    if (row && row.icon) return row.icon;
    if (row && row.value) return 'fa-solid fa-chart-line';
    return 'fa-solid fa-award';
  }

  function renderTrustStrip(data) {
    var track = $('#home-trust-track');
    if (!track) return;

    var items = [];
    var stats = (data && data.home && data.home.stats) || [];
    stats.slice(0, 6).forEach(function (row, index) {
      if (!row || !row.label) return;
      var isCredential = index >= 4;
      items.push({
        icon: trustStripIcon(row),
        value: isCredential ? '' : (row.value || ''),
        label: row.label || '',
      });
    });

    function itemHtml(it) {
      return (
        '<div class="home-trust-item">' +
        '<i class="' +
        it.icon +
        '" aria-hidden="true"></i>' +
        (it.value ? '<strong>' + it.value + '</strong>' : '') +
        '<span>' +
        it.label +
        '</span></div>'
      );
    }

    var html = items.map(itemHtml).join('');
    track.innerHTML = html + html;
  }

  /* —— Impact section —— */
  function renderImpactSection(data) {
    var grid = $('#home-impact-grid');
    if (!grid) return;
    var stats = (data && data.home && data.home.stats) || [];
    var rows = stats.slice(0, 4);

    grid.innerHTML = rows
      .slice(0, 4)
      .map(function (row) {
        return (
          '<div class="home-impact-stat home-reveal">' +
          '<strong class="home-impact-value" data-count="' +
          String(row.value || '').replace(/"/g, '') +
          '">' +
          (row.value || '') +
          '</strong>' +
          '<span>' +
          (row.label || '') +
          '</span></div>'
        );
      })
      .join('');

    var bg = $('.home-impact__bg');
    if (bg && data && data.heroSlides && data.heroSlides[0]) {
      bg.style.backgroundImage = "url('" + data.heroSlides[0].src + "')";
    }
  }

  function animateImpactCounters() {
    if (reduced) return;
    var grid = $('#home-impact-grid');
    if (!grid) return;
    var io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          entry.target.querySelectorAll('.home-impact-value[data-count]').forEach(function (el) {
            var raw = el.getAttribute('data-count') || '';
            var match = String(raw).match(/^([\d,.]+)(\+?)(.*)$/);
            if (!match) return;
            var target = parseFloat(match[1].replace(/,/g, ''));
            if (isNaN(target) || target <= 0) return;
            var suffix = (match[2] || '') + (match[3] || '');
            var start = performance.now();
            function frame(now) {
              var p = Math.min((now - start) / 1400, 1);
              var eased = 1 - Math.pow(1 - p, 3);
              el.textContent = String(Math.round(target * eased)) + suffix;
              if (p < 1) requestAnimationFrame(frame);
            }
            requestAnimationFrame(frame);
          });
          io.unobserve(entry.target);
        });
      },
      { threshold: 0.2 }
    );
    io.observe(grid);
  }

  /* —— Project filters —— */
  function initProjectFilters() {
    var filters = $('#home-project-filters');
    var grid = $('#home-project-grid');
    if (!filters || !grid) return;

    filters.addEventListener('click', function (e) {
      var btn = e.target.closest('.home-project-filter');
      if (!btn) return;
      var cat = btn.getAttribute('data-filter') || 'all';
      $$('.home-project-filter', filters).forEach(function (b) {
        b.classList.toggle('is-active', b === btn);
      });
      $$('.project-tile', grid).forEach(function (tile) {
        var tileCat = tile.getAttribute('data-category') || '';
        var show = cat === 'all' || tileCat === cat || (cat === 'interior' && tileCat.indexOf('interior') !== -1);
        tile.style.display = show ? '' : 'none';
      });
    });
  }

  /* —— GSAP reveals (lazy) —— */
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
    if (reduced || !window.gsap) {
      $$('.home-reveal').forEach(function (el) {
        el.classList.add('is-in');
      });
      return;
    }

    if (window.ScrollTrigger) gsap.registerPlugin(ScrollTrigger);

    gsap.utils.toArray('.home-reveal').forEach(function (el) {
      gsap.fromTo(
        el,
        { opacity: 0, y: 32 },
        {
          opacity: 1,
          y: 0,
          duration: 0.9,
          ease: 'power3.out',
          scrollTrigger: {
            trigger: el,
            start: 'top 88%',
            toggleActions: 'play none none none',
          },
        }
      );
    });

    var impactBg = $('.home-impact__bg');
    if (impactBg && window.ScrollTrigger) {
      gsap.to(impactBg, {
        yPercent: 12,
        ease: 'none',
        scrollTrigger: {
          trigger: '.home-impact',
          scrub: true,
        },
      });
    }
  }

  function loadGsapAndInit() {
    if (reduced) {
      $$('.home-reveal').forEach(function (el) {
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
      .catch(function () {
        $$('.home-reveal').forEach(function (el) {
          el.classList.add('is-in');
        });
      });
  }

  /* —— Intersection fallback without GSAP —— */
  function initRevealFallback() {
    if (reduced) return;
    var io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-in');
            io.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.12, rootMargin: '0px 0px -5% 0px' }
    );
    $$('.home-reveal').forEach(function (el) {
      io.observe(el);
    });
  }

  function onSiteData(e) {
    var data = (e && e.detail) || window.__SPANGLE_SITE__;
    if (!data) return;
    renderTrustStrip(data);
    renderImpactSection(data);
    animateImpactCounters();
  }

  initLoadCurtain();
  initProjectFilters();
  initRevealFallback();

  document.addEventListener('spangle:site-data', onSiteData);
  document.addEventListener('spangle:content-updated', function () {
    if (window.__SPANGLE_SITE__) onSiteData({ detail: window.__SPANGLE_SITE__ });
  });

  if (window.__SPANGLE_SITE__) onSiteData({ detail: window.__SPANGLE_SITE__ });

  if ('requestIdleCallback' in window) {
    requestIdleCallback(loadGsapAndInit, { timeout: 2500 });
  } else {
    window.setTimeout(loadGsapAndInit, 800);
  }
})();
