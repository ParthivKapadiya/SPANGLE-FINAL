(function () {
  'use strict';

  if (!document.body.classList.contains('page-services')) return;

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
        src.indexOf('services-premium.js') !== -1
      ) {
        try {
          return new URL(src, window.location.href).href.replace(
            /\/(js\/content-bridge\.js|js\/page-content\.js|js\/services-premium\.js|api\/site-data\.js\.php)(\?.*)?$/i,
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
      base = (window.__SPANGLE_SITE__ && window.__SPANGLE_SITE__.publicBase) || appBase();
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

  var SERVICE_ICONS = [
    'fa-solid fa-file-signature',
    'fa-solid fa-couch',
    'fa-solid fa-helmet-safety',
    'fa-solid fa-key',
    'fa-solid fa-drafting-compass',
    'fa-solid fa-map',
    'fa-solid fa-cube',
  ];

  var FAQ_FALLBACK = {
    eyebrow: 'Questions',
    title: 'Before you enquire',
    items: [
      {
        q: 'How much does a typical project cost?',
        a: 'Cost depends on site, scope, and finish level. After an initial consultation and site study, we provide a phased estimate aligned to your brief and timeline.',
      },
      {
        q: 'How long does the full process take?',
        a: 'Timelines vary by project type — approvals, construction, and interiors each have distinct phases. We share a milestone calendar at engagement.',
      },
      {
        q: 'Do you handle plan approvals and sanctions?',
        a: 'Yes. We prepare drawings for local plan sanctioning and coordinate with authorities so compliance is handled within the studio.',
      },
      {
        q: 'Can you manage construction on site?',
        a: 'Yes. Our civil and project management teams supervise quality, vendors, RFIs, and snag lists through handover.',
      },
      {
        q: 'What is included in a turnkey package?',
        a: 'Design, approvals, construction, interiors, procurement, and handover under one contract — single point of contact from brief to keys.',
      },
      {
        q: 'Do you offer interior design only?',
        a: 'Yes. Spatial planning, materials, joinery drawings, FF&E, and execution supervision — standalone or integrated with architecture.',
      },
    ],
  };

  var PROCESS_FALLBACK = [
    { title: 'Consultation', description: 'Site, scope, budget, and calendar — aligned before design begins.' },
    { title: 'Site study', description: 'Survey, constraints, climate logic, and feasibility mapped to your brief.' },
    { title: 'Concept', description: 'Massing, zoning, and spatial strategy presented for sign-off.' },
    { title: 'Design development', description: 'Working drawings, 3D studies, materials, and tender-ready packages.' },
    { title: 'Approvals', description: 'Plan sanctioning, authority coordination, and compliance documentation.' },
    { title: 'Construction', description: 'Site administration, quality checks, and vendor coordination on site.' },
    { title: 'Interior execution', description: 'Joinery, finishes, FF&E, and styling aligned with the design intent.' },
    { title: 'Handover', description: 'Snag resolution, documentation, and keys — space ready to occupy.' },
  ];

  function serviceIcon(index, service) {
    var text = ((service.eyebrow || '') + ' ' + (service.title || '')).toLowerCase();
    if (text.indexOf('interior') >= 0) return 'fa-solid fa-couch';
    if (text.indexOf('turnkey') >= 0) return 'fa-solid fa-key';
    if (text.indexOf('construct') >= 0 || text.indexOf('civil') >= 0) return 'fa-solid fa-helmet-safety';
    if (text.indexOf('approval') >= 0 || text.indexOf('sanction') >= 0) return 'fa-solid fa-file-signature';
    if (text.indexOf('3d') >= 0 || text.indexOf('visual') >= 0) return 'fa-solid fa-cube';
    if (text.indexOf('plan') >= 0 || text.indexOf('layout') >= 0) return 'fa-solid fa-map';
    if (text.indexOf('engineer') >= 0) return 'fa-solid fa-drafting-compass';
    return SERVICE_ICONS[index % SERVICE_ICONS.length];
  }

  function isTurnkey(service) {
    var text = ((service.eyebrow || '') + ' ' + (service.title || '')).toLowerCase();
    return text.indexOf('turnkey') >= 0;
  }

  function bindLazyImages(root) {
    var scope = root || document;
    scope.querySelectorAll('img[loading="lazy"]:not(.is-loaded)').forEach(function (img) {
      if (img.complete && img.naturalWidth > 0) {
        img.classList.add('is-loaded');
        return;
      }
      img.addEventListener('load', function onLoad() {
        img.classList.add('is-loaded');
        img.removeEventListener('load', onLoad);
      });
      img.addEventListener('error', function onErr() {
        img.classList.add('is-loaded');
        img.removeEventListener('error', onErr);
      });
    });
  }

  function setText(sel, value) {
    var el = $(sel);
    if (el && value) el.textContent = value;
  }

  function applySectionHeadings(page) {
    if (!page) return;
    setText('.site-services-ecosystem-eyebrow', page.ecosystemEyebrow);
    setText('.site-services-ecosystem-title', page.ecosystemTitle);
    setText('.site-services-ecosystem-intro', page.ecosystemIntro);
    setText('.site-services-compare-eyebrow', page.compare && page.compare.eyebrow);
    setText('.site-services-compare-title', page.compare && page.compare.title);
    setText('.site-services-compare-intro', page.compare && page.compare.intro);
    setText('.site-services-process-eyebrow', page.processEyebrow);
    setText('.site-services-process-title', page.processTitle);
    setText('.site-services-process-intro', page.processIntro);
    setText('.site-services-impact-eyebrow', page.impactEyebrow);
    setText('.site-services-impact-title', page.impactTitle);
    setText('.site-services-cases-eyebrow', page.casesEyebrow);
    setText('.site-services-cases-title', page.casesTitle);
    setText('.site-services-cases-intro', page.casesIntro);
    var casesLink = $('.site-services-cases-link');
    if (casesLink) {
      if (page.casesLinkText) {
        casesLink.innerHTML = esc(page.casesLinkText) + ' <span aria-hidden="true">→</span>';
      }
      if (page.casesLinkUrl) casesLink.setAttribute('href', page.casesLinkUrl);
    }
    var processLink = $('.site-services-process-link');
    if (processLink) {
      if (page.processLinkText) {
        processLink.innerHTML = esc(page.processLinkText) + ' <span aria-hidden="true">→</span>';
      }
      if (page.processLinkUrl) processLink.setAttribute('href', page.processLinkUrl);
    }
  }

  function applyHero(page) {
    if (!page) return;
    var k = $('.svc-hero__kicker.site-page-kicker');
    var t = $('.svc-hero__title.site-page-hero-title');
    var l = $('.svc-hero__lead.site-page-hero-lead');
    if (k && page.kicker) k.textContent = page.kicker;
    if (t && page.title) t.textContent = page.title;
    if (l && page.lead) l.textContent = page.lead;
    var media = $('.svc-hero__media');
    if (media && page.heroImage) {
      media.style.backgroundImage = "url('" + mediaSrc(page.heroImage).replace(/'/g, '%27') + "')";
    }
  }

  function renderHeroStats(data) {
    var wrap = $('#svc-hero-stats');
    if (!wrap) return;
    var stats = (data.home && data.home.stats) || [];
    if (!stats.length) return;
    wrap.innerHTML = stats
      .slice(0, 4)
      .map(function (row) {
        return (
          '<div class="svc-hero-stat svc-reveal">' +
          '<strong data-count="' +
          esc(row.value) +
          '">' +
          esc(row.value) +
          '</strong><span>' +
          esc(row.label) +
          '</span></div>'
        );
      })
      .join('');
  }

  function renderEcosystem(items) {
    var grid = $('#svc-ecosystem-grid');
    var flow = $('#svc-flow');
    if (!grid || !items || !items.length) return;

    grid.innerHTML = items
      .map(function (s, i) {
        return (
          '<article class="svc-eco-card svc-reveal">' +
          '<i class="' +
          esc(serviceIcon(i, s)) +
          '" aria-hidden="true"></i>' +
          '<h3>' +
          esc(s.title) +
          '</h3>' +
          '<p>' +
          esc(s.shortDescription || '') +
          '</p></article>'
        );
      })
      .join('');

    if (!flow) return;
    var nodes = items
      .map(function (s, i) {
        var arrow = i < items.length - 1 ? '<span class="svc-flow__arrow" aria-hidden="true">→</span>' : '';
        return (
          '<span class="svc-flow__node">' +
          '<i class="' +
          esc(serviceIcon(i, s)) +
          '" aria-hidden="true"></i>' +
          esc(s.title) +
          '</span>' +
          arrow
        );
      })
      .join('');
    flow.innerHTML = nodes;
  }

  function renderServiceDetails(items, page) {
    var section = $('#svc-detail-section');
    if (!section || !items || !items.length) return;
    var linkText = (page && page.detailLinkText) || 'View related work';
    var linkUrl = (page && page.detailLinkUrl) || 'work.html';

    section.innerHTML = items
      .map(function (s, i) {
        var hero = isTurnkey(s) ? ' svc-detail--hero' : '';
        var reverse = i % 2 === 1 && !hero ? ' svc-detail--reverse' : '';
        var img = s.image ? mediaSrc(s.image) : '';
        var badge = isTurnkey(s) ? '<span class="svc-detail__badge">Flagship offering</span>' : '';
        return (
          '<article class="svc-detail' +
          hero +
          reverse +
          ' svc-reveal" id="svc-block-' +
          i +
          '">' +
          '<div class="svc-detail__inner">' +
          '<div class="svc-detail__copy">' +
          '<p class="section-eyebrow">' +
          esc(s.eyebrow || s.number || '') +
          '</p>' +
          '<h2 class="section-title">' +
          esc(s.detailTitle || s.title) +
          '</h2>' +
          '<p class="svc-detail__lead">' +
          esc(s.detailLead1 || s.shortDescription || '') +
          '</p>' +
          '<div class="svc-detail__meta">' +
          '<div class="svc-detail__panel">' +
          '<h4>What you receive</h4>' +
          '<p>' +
          esc(s.detailLead2 || s.shortDescription || '') +
          '</p></div>' +
          '<div class="svc-detail__panel">' +
          '<h4>Expected outcome</h4>' +
          '<p>' +
          esc(s.shortDescription || s.detailLead1 || '') +
          '</p></div></div>' +
          '<a href="' +
          esc(linkUrl) +
          '" class="text-link">' +
          esc(linkText) +
          ' <span aria-hidden="true">→</span></a>' +
          '</div>' +
          '<div class="svc-detail__visual">' +
          badge +
          (img
            ? '<img src="' +
              esc(img) +
              '" alt="' +
              esc(s.detailTitle || s.title) +
              '" width="800" height="600" loading="lazy" decoding="async" data-img-fallback="uploads/1228_HARESHBHAI_LIVING_3.jpg" />'
            : '') +
          '</div></div></article>'
        );
      })
      .join('');

    bindLazyImages(section);
  }

  var COMPARE_US_FALLBACK = [
    'Single studio for design, approvals, build & interiors',
    'Director-led accountability through handover',
    'Drawings prepared for local plan sanctioning',
    'Transparent phases with shared documentation',
    '3D visualization before work begins on site',
  ];

  var COMPARE_THEM_FALLBACK = [
    'Separate architect, contractor & interior vendors',
    'Accountability gaps between design and site',
    'Approval paperwork handled by the client',
    'Scope changes without clear sign-off',
    'Decisions made on site without prior study',
  ];

  function renderCompare(page, data) {
    var wrap = $('#svc-compare');
    if (!wrap) return;
    var cmp = (page && page.compare) || {};
    var usTitle = cmp.usTitle || (data && data.siteName) || 'Archevo Design';
    var themTitle = cmp.themTitle || 'Traditional approach';
    var usItems = cmp.usItems && cmp.usItems.length ? cmp.usItems : COMPARE_US_FALLBACK;
    var themItems = cmp.themItems && cmp.themItems.length ? cmp.themItems : COMPARE_THEM_FALLBACK;
    wrap.innerHTML =
      '<div class="svc-compare-col svc-compare-col--us svc-reveal">' +
      '<h3>' +
      esc(usTitle) +
      '</h3><ul>' +
      usItems
        .map(function (text) {
          return '<li><i class="fa-solid fa-check" aria-hidden="true"></i> ' + esc(text) + '</li>';
        })
        .join('') +
      '</ul></div>' +
      '<div class="svc-compare-col svc-compare-col--them svc-reveal">' +
      '<h3>' +
      esc(themTitle) +
      '</h3><ul>' +
      themItems
        .map(function (text) {
          return '<li><i class="fa-solid fa-minus" aria-hidden="true"></i> ' + esc(text) + '</li>';
        })
        .join('') +
      '</ul></div>';
  }

  function renderProcess(data) {
    var track = $('#svc-process-track');
    if (!track) return;
    var steps = (data.processSteps || []).filter(function (s) {
      return !s.context || s.context === 'both' || s.context === 'page';
    });
    if (!steps.length) {
      steps = PROCESS_FALLBACK.slice();
    }
    track.style.setProperty('--process-steps', String(steps.length));
    track.innerHTML = steps
      .map(function (s, i) {
        return (
          '<article class="svc-process-step svc-reveal">' +
          '<div class="svc-process-step__dot">' +
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

  function renderImpact(data) {
    var grid = $('#svc-impact-grid');
    var bg = $('.svc-impact__bg');
    if (!grid) return;
    var stats = (data.home && data.home.stats) || [];
    if (!stats.length) return;
    var page = data.pages && data.pages.services;
    var impactSrc = (page && (page.impactImage || page.heroImage)) || '';
    if (bg && impactSrc) {
      bg.style.backgroundImage = "url('" + mediaSrc(impactSrc).replace(/'/g, '%27') + "')";
    }
    grid.innerHTML = stats
      .slice(0, 4)
      .map(function (row) {
        return (
          '<div class="svc-impact-stat svc-reveal">' +
          '<strong data-count="' +
          esc(row.value) +
          '">' +
          esc(row.value) +
          '</strong><span>' +
          esc(row.label) +
          '</span></div>'
        );
      })
      .join('');
  }

  function renderCaseStudies(data) {
    var grid = $('#svc-cases-grid');
    if (!grid || !data.projects || !data.projects.length) return;
    var featured = data.projects.filter(function (p) {
      return p.isFeatured;
    });
    var picks = (featured.length ? featured : data.projects).slice(0, 3);
    grid.innerHTML = picks
      .map(function (p) {
        var img = p.heroImage ? mediaSrc(p.heroImage) : '';
        var meta = [p.category, p.location, p.year].filter(Boolean).join(' · ');
        return (
          '<article class="svc-case-card svc-reveal">' +
          '<div class="svc-case-card__img">' +
          (img
            ? '<img src="' +
              esc(img) +
              '" alt="' +
              esc(p.title) +
              '" width="600" height="412" loading="lazy" decoding="async" />'
            : '') +
          '</div>' +
          '<div class="svc-case-card__body">' +
          (meta ? '<p class="svc-case-card__meta">' + esc(meta) + '</p>' : '') +
          '<h3>' +
          esc(p.title) +
          '</h3>' +
          '<p>' +
          esc(p.summary || '') +
          '</p>' +
          '<a href="' +
          esc(p.linkUrl || 'work.html') +
          '">View project <span aria-hidden="true">→</span></a>' +
          '</div></article>'
        );
      })
      .join('');
    bindLazyImages(grid);
  }

  function renderTestimonials(data, page) {
    var track = $('#svc-trust-track');
    if (!track || !data.testimonials || !data.testimonials.length) return;
    var te = $('.site-services-testimonials-eyebrow');
    var tt = $('.site-services-testimonials-title');
    if (te && page && page.testimonialsEyebrow) te.textContent = page.testimonialsEyebrow;
    if (tt && page && page.testimonialsTitle) tt.textContent = page.testimonialsTitle;
    track.innerHTML = data.testimonials
      .slice(0, 4)
      .map(function (t) {
        return (
          '<figure class="svc-trust-card svc-reveal">' +
          '<div class="svc-trust-card__stars" aria-hidden="true">★★★★★</div>' +
          '<blockquote>&ldquo;' +
          esc(t.quote) +
          '&rdquo;</blockquote>' +
          '<p class="quote-name">' +
          esc(t.authorName) +
          '</p>' +
          (t.authorRole ? '<p class="quote-role">' + esc(t.authorRole) + '</p>' : '') +
          '</figure>'
        );
      })
      .join('');
  }

  function renderFaq(page) {
    var list = $('#svc-faq-list');
    if (!list) return;
    var faq = (page && page.faq) || {};
    var items = faq.items && faq.items.length ? faq.items : FAQ_FALLBACK.items;
    var eyebrow = $('.site-services-faq-eyebrow');
    var title = $('.site-services-faq-title');
    if (eyebrow) eyebrow.textContent = faq.eyebrow || FAQ_FALLBACK.eyebrow;
    if (title) title.textContent = faq.title || FAQ_FALLBACK.title;
    list.innerHTML = items
      .map(function (item, i) {
        return (
          '<div class="svc-faq-item svc-reveal' +
          (i === 0 ? ' is-open' : '') +
          '">' +
          '<button type="button" class="svc-faq-q" aria-expanded="' +
          (i === 0 ? 'true' : 'false') +
          '">' +
          esc(item.q) +
          '<i class="fa-solid fa-chevron-down" aria-hidden="true"></i></button>' +
          '<div class="svc-faq-a"><p>' +
          esc(item.a) +
          '</p></div></div>'
        );
      })
      .join('');
    list.querySelectorAll('.svc-faq-q').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var item = btn.closest('.svc-faq-item');
        var open = item.classList.contains('is-open');
        list.querySelectorAll('.svc-faq-item').forEach(function (el) {
          el.classList.remove('is-open');
          el.querySelector('.svc-faq-q').setAttribute('aria-expanded', 'false');
        });
        if (!open) {
          item.classList.add('is-open');
          btn.setAttribute('aria-expanded', 'true');
        }
      });
    });
  }

  function applyConsultOpen(el, url) {
    if (!el) return;
    var href = String(url || el.getAttribute('href') || '').trim() || 'contact.html';
    if (href === '#' || href === 'contact.html' || href.indexOf('contact') !== -1) {
      el.setAttribute('data-consult-open', '');
    } else {
      el.removeAttribute('data-consult-open');
    }
  }

  function applyCta(copy, page) {
    copy = copy || {};
    page = page || {};
    var eyebrow = $('.site-services-cta-eyebrow');
    var title = $('.site-services-cta-title');
    var lead = $('.site-services-cta-lead');
    var btn = $('.site-services-cta-btn');
    var btn2 = $('.site-services-cta-btn-secondary');
    var sub = $('.svc-cta__sub');
    if (eyebrow && copy.services_cta_eyebrow) eyebrow.textContent = copy.services_cta_eyebrow;
    if (title && copy.services_cta_title) title.textContent = copy.services_cta_title;
    if (lead && copy.services_cta_lead) lead.textContent = copy.services_cta_lead;
    if (btn && copy.services_cta_btn_text) btn.textContent = copy.services_cta_btn_text;
    if (btn && copy.services_cta_btn_url) btn.setAttribute('href', copy.services_cta_btn_url);
    if (btn2 && page.ctaSecondary && page.ctaSecondary.text) {
      btn2.textContent = page.ctaSecondary.text;
      btn2.setAttribute('href', page.ctaSecondary.url || 'contact.html');
      btn2.hidden = false;
      applyConsultOpen(btn2, page.ctaSecondary.url || 'contact.html');
    } else if (btn2 && copy.services_cta_btn2_text) {
      btn2.textContent = copy.services_cta_btn2_text;
      btn2.setAttribute('href', copy.services_cta_btn2_url || 'contact.html');
      btn2.hidden = false;
      applyConsultOpen(btn2, copy.services_cta_btn2_url || 'contact.html');
    }
    if (sub && (copy.services_cta_sub || (page && page.ctaSub))) {
      sub.textContent = copy.services_cta_sub || page.ctaSub;
    }
    applyConsultOpen(btn, copy.services_cta_btn_url);
  }

  function animateCounters() {
    $$('.svc-hero-stat [data-count], .svc-impact-stat [data-count]').forEach(function (el) {
      var raw = el.getAttribute('data-count') || '';
      var match = raw.match(/([\d,.]+)/);
      if (!match) return;
      var target = parseFloat(match[1].replace(/,/g, ''));
      if (isNaN(target)) return;
      var suffix = raw.slice(match[0].length);
      var prefix = raw.slice(0, raw.indexOf(match[1]));
      var io = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;
            var start = performance.now();
            var duration = 1400;
            function frame(now) {
              var p = Math.min((now - start) / duration, 1);
              var eased = 1 - Math.pow(1 - p, 3);
              var val = Math.round(target * eased);
              el.textContent = prefix + val.toLocaleString() + suffix;
              if (p < 1) requestAnimationFrame(frame);
            }
            requestAnimationFrame(frame);
            io.unobserve(el);
          });
        },
        { threshold: 0.4 }
      );
      io.observe(el);
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
    $$('.svc-reveal').forEach(function (el) {
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
      $$('.svc-reveal').forEach(function (el) {
        el.classList.add('is-in');
      });
      return;
    }
    if (window.ScrollTrigger) gsap.registerPlugin(ScrollTrigger);

    gsap.utils.toArray('.svc-reveal:not(.is-in)').forEach(function (el) {
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

    var heroMedia = $('.svc-hero__media');
    if (heroMedia && window.ScrollTrigger) {
      gsap.to(heroMedia, {
        yPercent: 14,
        scale: 1,
        ease: 'none',
        scrollTrigger: {
          trigger: '.svc-hero',
          start: 'top top',
          end: 'bottom top',
          scrub: true,
        },
      });
    }
  }

  function loadGsap() {
    if (reduced) {
      $$('.svc-reveal').forEach(function (el) {
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
    var page = data.pages && data.pages.services;
    applyHero(page);
    applySectionHeadings(page);
    renderHeroStats(data);
    if (page && page.items) {
      renderEcosystem(page.items);
      renderServiceDetails(page.items, page);
    }
    renderCompare(page, data);
    renderProcess(data);
    renderImpact(data);
    renderCaseStudies(data);
    renderTestimonials(data, page);
    renderFaq(page);
    applyCta(data.copy, page);
    animateCounters();
    initRevealFallback();
    bindLazyImages($('#main'));
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
      $$('.svc-reveal:not(.is-in)').forEach(function (el) {
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
