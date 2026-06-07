(function () {
  'use strict';

  if (!document.body.classList.contains('page-studio')) return;

  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

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
        src.indexOf('studio-premium.js') !== -1
      ) {
        try {
          return new URL(src, window.location.href).href.replace(
            /\/(js\/content-bridge\.js|js\/page-content\.js|js\/studio-premium\.js|api\/site-data\.js\.php)(\?.*)?$/i,
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
    var base;
    if (isLocalHost()) {
      base = appBase();
    } else {
      var data = window.__SPANGLE_SITE__ || {};
      base = data.publicBase || appBase();
    }
    base = String(base).replace(/\/$/, '');
    var parts = p.split('/').map(function (seg) {
      try {
        return encodeURIComponent(decodeURIComponent(seg));
      } catch (e) {
        return encodeURIComponent(seg);
      }
    });
    return base + '/' + parts.join('/');
  }

  var VALUE_ICONS = [
    'fa-solid fa-shield-halved',
    'fa-solid fa-lightbulb',
    'fa-solid fa-hammer',
    'fa-solid fa-eye',
    'fa-solid fa-heart',
    'fa-solid fa-people-group',
  ];

  var CULTURE_LABELS = [
    'Site visits',
    'Design reviews',
    'Material selection',
    'Client meetings',
    'Construction monitoring',
    'Behind the scenes',
  ];

  function renderHeroStats(data) {
    var wrap = $('#studio-hero-stats');
    if (!wrap) return;
    var stats = (data.home && data.home.stats) || [];
    if (!stats.length) return;
    wrap.innerHTML = stats
      .slice(0, 4)
      .map(function (row) {
        return (
          '<div class="studio-hero-stat studio-reveal">' +
          '<strong>' +
          esc(row.value) +
          '</strong><span>' +
          esc(row.label) +
          '</span></div>'
        );
      })
      .join('');
  }

  function renderWhyGrid() {
    var grid = $('#studio-why-grid');
    if (!grid || grid.children.length) return;
    var items = [
      ['01', 'Integrated Design + Build', 'Architecture, engineering, interiors, and site execution aligned from day one — not stitched together later.'],
      ['02', 'Civil Engineering Expertise', 'Structural thinking and buildability embedded in every drawing — fewer surprises on site.'],
      ['03', 'Interior Design Excellence', 'Space planning, materials, and atmosphere composed with the same rigour as the shell.'],
      ['04', 'Turnkey Delivery', 'One accountable studio from brief to keys — procurement, labour, and handover under one roof.'],
      ['05', 'Plan Approval Support', 'Drawings and submissions prepared for local sanctioning requirements across Gujarat.'],
      ['06', 'Single Point Responsibility', 'One director-led team — no disappearing contractors, no lost accountability mid-project.'],
    ];
    grid.innerHTML = items
      .map(function (row) {
        return (
          '<article class="studio-why-item studio-reveal">' +
          '<span class="studio-why-item__num">' +
          row[0] +
          '</span><h3>' +
          esc(row[1]) +
          '</h3><p>' +
          esc(row[2]) +
          '</p></article>'
        );
      })
      .join('');
  }

  function renderValues(data) {
    var grid = $('#studio-values-grid');
    if (!grid) return;
    var awards = data.awards || [];
    if (awards.length) {
      grid.innerHTML = awards
        .map(function (a, i) {
          return (
            '<article class="studio-value-card studio-reveal">' +
            '<i class="' +
            esc(a.icon || VALUE_ICONS[i % VALUE_ICONS.length]) +
            '" aria-hidden="true"></i>' +
            '<h3>' +
            esc(a.title) +
            '</h3><p>' +
            esc(a.subtitle || '') +
            '</p></article>'
          );
        })
        .join('');
      return;
    }
    var defaults = [
      ['Integrity', 'Honest scope, transparent budgets, and commitments we stand behind on every site.'],
      ['Innovation', 'Contemporary solutions tuned for Gujarat\'s climate, culture, and construction realities.'],
      ['Craftsmanship', 'Materials, joinery, and details executed with patience — not rushed for photographs.'],
      ['Transparency', 'Clear phases, shared documentation, and sign-off moments you can trust.'],
      ['Commitment', 'Directors on site when it matters — from first survey to final handover.'],
      ['Collaboration', 'Clients, authorities, and contractors aligned through one studio voice.'],
    ];
    grid.innerHTML = defaults
      .map(function (row, i) {
        return (
          '<article class="studio-value-card studio-reveal">' +
          '<i class="' +
          VALUE_ICONS[i] +
          '" aria-hidden="true"></i>' +
          '<h3>' +
          esc(row[0]) +
          '</h3><p>' +
          esc(row[1]) +
          '</p></article>'
        );
      })
      .join('');
  }

  function founderPortraitSrc(founder, page, data) {
    if (founder && founder.image) return mediaSrc(founder.image, data);
    if (page && page.philosophyImage) return mediaSrc(page.philosophyImage, data);
    if (page && page.heroImage) return mediaSrc(page.heroImage, data);
    if (page && page.stripImages && page.stripImages[0]) return mediaSrc(page.stripImages[0], data);
    return 'uploads/1228_HARESHBHAI_LIVING_3.jpg';
  }

  function renderFounder(data, page) {
    var block = document.getElementById('founder');
    if (!block) return;
    var team = data.team || [];
    var founder = team[0] || { name: 'Jay P. Rathood', role: 'Director', bio: '' };

    var portrait = $('#studio-founder-portrait');
    var quote = $('.site-studio-pullquote');
    var quoteText = (page && page.pullquote) || (quote && quote.textContent) || '';
    var src = founderPortraitSrc(founder, page, data);
    var alt = founder.name + ', ' + (founder.role || 'Director') + ' at Archevo Design';

    if (portrait) {
      var img = portrait.querySelector('.studio-founder__photo');
      if (img) {
        img.setAttribute('src', src);
        img.setAttribute('alt', alt);
      } else {
        portrait.innerHTML =
          '<img src="' +
          esc(src) +
          '" class="studio-founder__photo site-studio-philosophy-image" alt="' +
          esc(alt) +
          '" loading="lazy" decoding="async" data-img-fallback="uploads/1212-ARVINDBHAI%20PARMAR_FRONT-2.jpg" />' +
          '<p class="studio-founder__caption"><span id="studio-founder-caption-name">' +
          esc(founder.name) +
          '</span> · <span id="studio-founder-caption-role">' +
          esc(founder.role || 'Director') +
          '</span></p>';
      }
    }

    var q = $('#studio-founder-quote');
    if (q && quoteText) q.textContent = quoteText.replace(/^["“]|["”]$/g, '');

    var name = $('#studio-founder-name');
    var role = $('#studio-founder-role');
    var bio = $('#studio-founder-bio');
    var capName = $('#studio-founder-caption-name');
    var capRole = $('#studio-founder-caption-role');
    if (name) name.textContent = founder.name;
    if (role) role.textContent = founder.role;
    if (bio && founder.bio) bio.textContent = founder.bio;
    if (capName) capName.textContent = founder.name;
    if (capRole) capRole.textContent = founder.role || 'Director';
  }

  function renderTeam(data) {
    var grid = $('#studio-team-grid');
    if (!grid || !data.team || !data.team.length) return;

    var eyebrow = $('.site-team-eyebrow');
    var title = $('.site-team-title');
    if (eyebrow && data.home && data.home.teamEyebrow) eyebrow.textContent = data.home.teamEyebrow;
    if (title && data.home && data.home.teamTitle) title.textContent = data.home.teamTitle;

    grid.innerHTML = data.team
      .map(function (m) {
        var avatar = m.image
          ? '<img src="' + esc(mediaSrc(m.image, data)) + '" alt="" loading="lazy" decoding="async" />'
          : '<div class="team-avatar" role="img" aria-label="' + esc(m.name) + '">' + esc(m.initials || m.name.charAt(0)) + '</div>';
        var links = '';
        if (m.linkedin) {
          links =
            '<div class="studio-team-card__links"><a href="' +
            esc(m.linkedin) +
            '" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn"><i class="fab fa-linkedin-in" aria-hidden="true"></i></a></div>';
        }
        return (
          '<article class="studio-team-card studio-reveal">' +
          avatar +
          '<h3>' +
          esc(m.name) +
          '</h3><p class="team-role">' +
          esc(m.role) +
          '</p><p>' +
          esc(m.bio) +
          '</p>' +
          links +
          '</article>'
        );
      })
      .join('');
  }

  function renderProcess(data) {
    var track = $('#studio-process-track');
    if (!track || !data.processSteps || !data.processSteps.length) return;
    var steps = data.processSteps
      .filter(function (s) {
        return !s.context || s.context === 'both' || s.context === 'page';
      })
      .slice(0, 7);
    track.style.setProperty('--process-steps', String(Math.max(steps.length, 1)));
    track.innerHTML = steps
      .map(function (s, i) {
        return (
          '<article class="studio-process-step studio-reveal">' +
          '<div class="studio-process-step__dot">' +
          String(i + 1).padStart(2, '0') +
          '</div><h3>' +
          esc(s.title) +
          '</h3><p>' +
          esc(s.description) +
          '</p></article>'
        );
      })
      .join('');
  }

  var CULTURE_FALLBACKS = [
    'uploads/1213-SANJAYSINH%20JADEJA_PLOT-62-1.jpg',
    'uploads/1228_HARESHBHAI_LIVING_4.jpg',
    'uploads/058-PRAKASHBHAI%20TANK-3D-2.jpg',
  ];

  var CULTURE_ERROR_FALLBACKS = [
    'uploads/1212-ARVINDBHAI%20PARMAR_BACK-2.jpg',
    'uploads/LIVING%2002.jpg',
    'uploads/066-UPENDRASINH-3D-3.jpg',
  ];

  function bindStudioLazyImages(root) {
    var scope = root || document;
    scope.querySelectorAll('img[loading="lazy"]:not(.is-loaded)').forEach(function (img) {
      if (img.complete && img.naturalWidth > 0) {
        img.classList.add('is-loaded');
        return;
      }
      img.addEventListener('load', function onStudioImgLoad() {
        img.classList.add('is-loaded');
        img.removeEventListener('load', onStudioImgLoad);
      });
      img.addEventListener('error', function onStudioImgError() {
        img.classList.add('is-loaded');
        img.removeEventListener('error', onStudioImgError);
      });
    });
  }

  function renderCulture(page, data) {
    var grid = $('#studio-culture-grid');
    if (!grid) return;
    var images = (page && page.stripImages) || [];
    images = images.filter(Boolean);
    if (!images.length) {
      var strip = $('.site-studio-strip');
      if (strip) {
        strip.querySelectorAll('img').forEach(function (img) {
          if (img.getAttribute('src')) images.push(img.getAttribute('src'));
        });
      }
    }
    if (!images.length) images = CULTURE_FALLBACKS.slice();
    images = images.slice(0, 3);

    grid.innerHTML = images
      .map(function (src, i) {
        var url = mediaSrc(src);
        var fallback = mediaSrc(CULTURE_ERROR_FALLBACKS[i % CULTURE_ERROR_FALLBACKS.length]);
        var label = CULTURE_LABELS[i % CULTURE_LABELS.length];
        return (
          '<figure class="studio-reveal is-in">' +
          '<img src="' +
          esc(url) +
          '" alt="' +
          esc(label) +
          '" width="600" height="750" loading="lazy" decoding="async" data-img-fallback="' +
          esc(fallback) +
          '" onerror="if(this.dataset.fallbackApplied)return;this.dataset.fallbackApplied=1;this.src=\'' +
          esc(fallback).replace(/'/g, '%27') +
          '\'" />' +
          '<figcaption>' +
          esc(label) +
          '</figcaption></figure>'
        );
      })
      .join('');
    bindStudioLazyImages(grid);
  }

  function renderImpact(data) {
    var grid = $('#studio-impact-grid');
    var bg = $('.studio-impact__bg');
    if (!grid) return;
    var stats = (data.home && data.home.stats) || [];
    if (!stats.length) return;

    grid.innerHTML = stats
      .slice(0, 4)
      .map(function (row) {
        return (
          '<div class="studio-impact-stat studio-reveal">' +
          '<strong class="studio-impact-value" data-count="' +
          esc(String(row.value).replace(/"/g, '')) +
          '">' +
          esc(row.value) +
          '</strong><span>' +
          esc(row.label) +
          '</span></div>'
        );
      })
      .join('');

    if (bg && data.pages && data.pages.studio && data.pages.studio.heroImage) {
      bg.style.backgroundImage = "url('" + mediaSrc(data.pages.studio.heroImage, data).replace(/'/g, '%27') + "')";
    }
  }

  function renderTrust(data) {
    var track = $('#studio-trust-track');
    if (!track || !data.testimonials || !data.testimonials.length) return;

    var eyebrow = $('.site-testimonials-eyebrow');
    var title = $('.site-testimonials-title');
    if (eyebrow && data.home && data.home.testimonialsEyebrow) eyebrow.textContent = data.home.testimonialsEyebrow;
    if (title && data.home && data.home.testimonialsTitle) title.textContent = data.home.testimonialsTitle;

    track.innerHTML = data.testimonials
      .slice(0, 4)
      .map(function (t) {
        return (
          '<figure class="studio-trust-card studio-reveal">' +
          '<blockquote>' +
          esc(t.quote) +
          '</blockquote>' +
          '<figcaption><span class="quote-name">' +
          esc(t.authorName) +
          '</span> · <span class="quote-role">' +
          esc(t.authorRole) +
          '</span></figcaption></figure>'
        );
      })
      .join('');
  }

  function applyHeroMedia(page, data) {
    var media = $('.studio-hero__media');
    if (!media || !page || !page.heroImage) return;
    media.style.backgroundImage = "url('" + mediaSrc(page.heroImage, data).replace(/'/g, '%27') + "')";
  }

  function animateCounters() {
    if (reduced) return;
    var grids = $$('#studio-impact-grid, #studio-hero-stats');
    grids.forEach(function (grid) {
      var io = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;
            entry.target.querySelectorAll('[data-count]').forEach(function (el) {
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
        { threshold: 0.25 }
      );
      io.observe(grid);
    });
  }

  function initRevealFallback() {
    var io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-in');
            io.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.1, rootMargin: '0px 0px -6% 0px' }
    );
    $$('.studio-reveal').forEach(function (el) {
      io.observe(el);
    });
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
    if (reduced || !window.gsap) {
      $$('.studio-reveal').forEach(function (el) {
        el.classList.add('is-in');
      });
      return;
    }
    if (window.ScrollTrigger) gsap.registerPlugin(ScrollTrigger);

    gsap.utils.toArray('.studio-reveal:not(.is-in)').forEach(function (el) {
      gsap.fromTo(
        el,
        { opacity: 0, y: 36 },
        {
          opacity: 1,
          y: 0,
          duration: 0.95,
          ease: 'power3.out',
          onComplete: function () {
            el.classList.add('is-in');
          },
          scrollTrigger: {
            trigger: el,
            start: 'top 90%',
            toggleActions: 'play none none none',
          },
        }
      );
    });

    var heroMedia = $('.studio-hero__media');
    if (heroMedia && window.ScrollTrigger) {
      gsap.to(heroMedia, {
        yPercent: 14,
        scale: 1,
        ease: 'none',
        scrollTrigger: {
          trigger: '.studio-hero',
          start: 'top top',
          end: 'bottom top',
          scrub: true,
        },
      });
    }
  }

  function loadGsap() {
    if (reduced) {
      $$('.studio-reveal').forEach(function (el) {
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

  function hydrate(data) {
    if (!data) return;
    var page = data.pages && data.pages.studio;
    applyHeroMedia(page, data);
    renderHeroStats(data);
    renderWhyGrid();
    renderValues(data);
    renderFounder(data, page);
    renderTeam(data);
    renderProcess(data);
    renderCulture(page, data);
    renderImpact(data);
    renderTrust(data);
    if (page && page.philosophyImage) {
      var storyImg = $('#studio-story-image');
      if (storyImg) storyImg.setAttribute('src', mediaSrc(page.philosophyImage, data));
    }
    animateCounters();
    initRevealFallback();
    bindStudioLazyImages($('#main'));
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
      $$('.studio-reveal:not(.is-in)').forEach(function (el) {
        el.classList.add('is-in');
      });
    }, 2200);
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
}());
