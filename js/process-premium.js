(function () {
  'use strict';

  if (!document.body.classList.contains('page-process')) return;

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
        src.indexOf('process-premium.js') !== -1
      ) {
        try {
          return new URL(src, window.location.href).href.replace(
            /\/(js\/content-bridge\.js|js\/page-content\.js|js\/process-premium\.js|api\/site-data\.js\.php)(\?.*)?$/i,
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

  function bindLazyImages(root) {
    (root || document).querySelectorAll('img[loading="lazy"]:not(.is-loaded)').forEach(function (img) {
      if (img.complete && img.naturalWidth > 0) {
        img.classList.add('is-loaded');
        return;
      }
      img.addEventListener('load', function onL() {
        img.classList.add('is-loaded');
        img.removeEventListener('load', onL);
      });
      img.addEventListener('error', function onE() {
        img.classList.add('is-loaded');
        img.removeEventListener('error', onE);
      });
    });
  }

  var PHASE_FALLBACK = [
    {
      label: 'Phase 01',
      title: 'Discovery & feasibility',
      description: 'Site survey, aspirations workshop, budget alignment, and concept massing.',
      goals: 'Align brief, site constraints, and budget before design begins.',
      deliverables: 'Feasibility note, spatial strategy, and milestone calendar.',
      clientInputs: 'Site documents, aspirations, and budget range.',
      duration: '2–4 weeks',
      outcome: 'Agreed spatial strategy and engagement scope.',
    },
    {
      label: 'Phase 02',
      title: 'Concept design',
      description: 'Zoning-compliant plans, key sections, and indicative materials.',
      goals: 'Establish massing, façade direction, and spatial flow.',
      deliverables: 'Concept plans, 3D views, and material intent boards.',
      clientInputs: 'Feedback on layouts and aesthetic direction.',
      duration: '3–6 weeks',
      outcome: 'Locked floor plans and façade direction.',
    },
    {
      label: 'Phase 03',
      title: 'Design development',
      description: 'Services coordination, joinery design, lighting intent, and cost checks.',
      goals: 'Develop tender-ready documentation with coordinated services.',
      deliverables: 'Working drawings, BOQs, and specification schedules.',
      clientInputs: 'Material selections and scope sign-offs.',
      duration: '6–10 weeks',
      outcome: 'Tender-ready package with cost alignment.',
    },
    {
      label: 'Phase 04',
      title: 'Approvals',
      description: 'Plan sanctioning, authority coordination, and compliance documentation.',
      goals: 'Secure statutory approvals without client paperwork burden.',
      deliverables: 'Sanction drawings, compliance submissions, and approval tracking.',
      clientInputs: 'Title documents and authority fees as needed.',
      duration: '4–12 weeks',
      outcome: 'Approved plans ready for construction.',
    },
    {
      label: 'Phase 05',
      title: 'Construction',
      description: 'Site administration, RFIs, vendor coordination, and quality checks.',
      goals: 'Protect design intent through disciplined site execution.',
      deliverables: 'Site reports, inspection logs, and snag lists.',
      clientInputs: 'Timely decisions on RFIs and variations.',
      duration: 'Project-dependent',
      outcome: 'Built shell aligned to approved drawings.',
    },
    {
      label: 'Phase 06',
      title: 'Interior execution',
      description: 'Joinery, finishes, FF&E installation, and styling coordination.',
      goals: 'Deliver atmosphere and detail as documented in design.',
      deliverables: 'Installation supervision, mock-ups, and styling.',
      clientInputs: 'Final selections and access for installation.',
      duration: '8–16 weeks',
      outcome: 'Interiors completed to specification.',
    },
    {
      label: 'Phase 07',
      title: 'Handover',
      description: 'Snag resolution, documentation, and keys — space ready to occupy.',
      goals: 'Close the project with clarity and complete documentation.',
      deliverables: 'O&M binders, warranties, and final photography.',
      clientInputs: 'Snag walkthrough and final sign-off.',
      duration: '2–4 weeks',
      outcome: 'Defect-free handover and occupied space.',
    },
  ];

  var FAQ_FALLBACK = {
    eyebrow: 'Questions',
    title: 'About our process',
    items: [
      { q: 'How long does a project take?', a: 'Timelines depend on scope — residential builds typically run 12–18 months turnkey; interiors may complete in 4–8 months. We share a milestone calendar at engagement.' },
      { q: 'When are approvals required?', a: 'Plan sanctioning follows schematic lock. We prepare drawings and coordinate with local authorities so compliance stays within the studio.' },
      { q: 'How often will I receive updates?', a: 'Weekly site reports during construction, shared documentation at every phase gate, and director access for key decisions.' },
      { q: 'Can you manage turnkey projects?', a: 'Yes. Design, approvals, construction, interiors, and handover under one contract — single point of contact throughout.' },
      { q: 'What if changes are needed mid-project?', a: 'Changes are documented with scope, cost, and timeline impact before work proceeds — no surprises on site.' },
    ],
  };

  function mergePhases(cmsSteps) {
    cmsSteps = (cmsSteps || []).filter(function (s) {
      var c = (s.context || 'both').toLowerCase();
      return c === 'both' || c === 'page';
    });
    return PHASE_FALLBACK.map(function (fb, i) {
      var cms = cmsSteps[i];
      if (!cms) return fb;
      return {
        label: cms.label || fb.label,
        title: cms.title || fb.title,
        description: cms.description || fb.description,
        goals: fb.goals,
        deliverables: fb.deliverables,
        clientInputs: fb.clientInputs,
        duration: fb.duration,
        outcome: cms.description || fb.outcome,
      };
    });
  }

  function applyHero(page) {
    if (!page) return;
    var k = $('.proc-hero__kicker.site-page-kicker');
    var t = $('.proc-hero__title.site-page-hero-title');
    var l = $('.proc-hero__lead.site-page-hero-lead');
    if (k && page.kicker) k.textContent = page.kicker;
    if (t && page.title) t.textContent = page.title;
    if (l && page.lead) l.textContent = page.lead;
    var media = $('.proc-hero__media');
    if (media && page.heroImage) {
      media.style.backgroundImage = "url('" + mediaSrc(page.heroImage).replace(/'/g, '%27') + "')";
    }
  }

  function renderHeroStats(data) {
    var wrap = $('#proc-hero-stats');
    if (!wrap) return;
    var stats = (data.home && data.home.stats) || [];
    if (!stats.length) return;
    wrap.innerHTML = stats
      .slice(0, 4)
      .map(function (row) {
        return (
          '<div class="proc-hero-stat proc-reveal"><strong data-count="' +
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

  function renderTrust() {
    var grid = $('#proc-trust-grid');
    if (!grid) return;
    var items = [
      ['fa-solid fa-comments', 'Clear communication', 'Weekly updates, shared tools, and one accountable director through every phase.'],
      ['fa-solid fa-flag-checkered', 'Defined milestones', 'Sign-off gates at every stage — decisions captured once, not lost across threads.'],
      ['fa-solid fa-chart-line', 'Budget control', 'Phased estimates, transparent variations, and cost checks before work begins on site.'],
      ['fa-solid fa-shield-halved', 'Quality assurance', 'Site inspections, mock-up reviews, and snag lists until handover is complete.'],
      ['fa-solid fa-user-check', 'Single point responsibility', 'One studio for design, approvals, build, and interiors — no vendor gaps.'],
      ['fa-solid fa-clock', 'On-time delivery', 'Milestone calendars, vendor coordination, and proactive issue resolution on site.'],
    ];
    grid.innerHTML = items
      .map(function (row) {
        return (
          '<article class="proc-trust-card proc-reveal"><i class="' +
          row[0] +
          '" aria-hidden="true"></i><h3>' +
          esc(row[1]) +
          '</h3><p>' +
          esc(row[2]) +
          '</p></article>'
        );
      })
      .join('');
  }

  function renderFramework(data) {
    var wrap = $('#proc-framework');
    var title = $('#proc-framework-title');
    if (!wrap) return;
    var brand = (data && data.siteName) || 'Archevo Design';
    if (title) title.textContent = brand + ' Project Framework';
    var steps = ['Discover', 'Define', 'Design', 'Develop', 'Deliver', 'Celebrate'];
    wrap.innerHTML = steps
      .map(function (label, i) {
        return (
          '<div class="proc-framework__step proc-reveal"><span class="proc-framework__letter">' +
          label.charAt(0) +
          '</span><span class="proc-framework__label">' +
          esc(label) +
          '</span></div>'
        );
      })
      .join('');
  }

  function renderTimeline(data) {
    var list = $('#proc-timeline');
    if (!list) return;
    var page = data.pages && data.pages.process;
    var te = $('.site-process-timeline-eyebrow');
    var tt = $('.site-process-timeline-title');
    if (te && page && page.timelineEyebrow) te.textContent = page.timelineEyebrow;
    if (tt && page && page.timelineTitle) tt.textContent = page.timelineTitle;
    var phases = mergePhases(data.processSteps);
    list.innerHTML = phases
      .map(function (p, i) {
        return (
          '<div class="proc-phase proc-reveal' +
          (i === 0 ? ' is-open' : '') +
          '">' +
          '<button type="button" class="proc-phase__toggle" aria-expanded="' +
          (i === 0 ? 'true' : 'false') +
          '">' +
          '<span class="proc-phase__num">' +
          esc(p.label) +
          '</span>' +
          '<div class="proc-phase__head"><h3>' +
          esc(p.title) +
          '</h3><p>' +
          esc(p.description) +
          '</p></div>' +
          '<i class="fa-solid fa-chevron-down proc-phase__icon" aria-hidden="true"></i></button>' +
          '<div class="proc-phase__body"><div class="proc-phase__grid">' +
          '<div class="proc-phase__panel"><h4>Goals</h4><p>' +
          esc(p.goals) +
          '</p></div>' +
          '<div class="proc-phase__panel"><h4>Deliverables</h4><p>' +
          esc(p.deliverables) +
          '</p></div>' +
          '<div class="proc-phase__panel"><h4>Client inputs</h4><p>' +
          esc(p.clientInputs) +
          '</p></div>' +
          '<div class="proc-phase__panel"><h4>Duration</h4><p>' +
          esc(p.duration) +
          '</p></div>' +
          '<div class="proc-phase__panel" style="grid-column:1/-1"><h4>Expected outcome</h4><p>' +
          esc(p.outcome) +
          '</p></div></div></div></div>'
        );
      })
      .join('');
    list.querySelectorAll('.proc-phase__toggle').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var phase = btn.closest('.proc-phase');
        var open = phase.classList.contains('is-open');
        list.querySelectorAll('.proc-phase').forEach(function (el) {
          el.classList.remove('is-open');
          el.querySelector('.proc-phase__toggle').setAttribute('aria-expanded', 'false');
        });
        if (!open) {
          phase.classList.add('is-open');
          btn.setAttribute('aria-expanded', 'true');
        }
      });
    });
  }

  function renderSplit(page) {
    if (!page) return;
    var se = $('.site-process-split-eyebrow');
    var st = $('.site-process-split-title');
    var leads = document.querySelectorAll('.site-process-split-lead');
    var si = $('.site-process-split-image');
    if (se && page.splitEyebrow) se.textContent = page.splitEyebrow;
    if (st && page.splitTitle) st.textContent = page.splitTitle;
    if (leads[0] && page.splitLead1) leads[0].textContent = page.splitLead1;
    if (leads[1] && page.splitLead2) leads[1].textContent = page.splitLead2;
    if (si && page.splitImage) si.setAttribute('src', mediaSrc(page.splitImage));
    bindLazyImages($('.proc-split'));
  }

  function renderJourney(data) {
    var wrap = $('#proc-journey');
    if (!wrap) return;
    var brand = (data && data.siteName) || 'Archevo Design';
    wrap.innerHTML =
      '<div class="proc-journey-col proc-reveal"><h3>What you do</h3><ul>' +
      '<li><i class="fa-solid fa-check" aria-hidden="true"></i> Share site, aspirations, and budget at consultation</li>' +
      '<li><i class="fa-solid fa-check" aria-hidden="true"></i> Review concepts and approve phase gates</li>' +
      '<li><i class="fa-solid fa-check" aria-hidden="true"></i> Select materials and finishes at defined milestones</li>' +
      '<li><i class="fa-solid fa-check" aria-hidden="true"></i> Walk through snag list before handover</li>' +
      '</ul></div>' +
      '<div class="proc-journey-col proc-journey-col--studio proc-reveal"><h3>What ' +
      esc(brand) +
      ' does</h3><ul>' +
      '<li><i class="fa-solid fa-check" aria-hidden="true"></i> Site study, drawings, 3D, and approvals</li>' +
      '<li><i class="fa-solid fa-check" aria-hidden="true"></i> Vendor coordination and site supervision</li>' +
      '<li><i class="fa-solid fa-check" aria-hidden="true"></i> Weekly progress reports and documentation</li>' +
      '<li><i class="fa-solid fa-check" aria-hidden="true"></i> Quality checks through keys and photography</li>' +
      '</ul></div>';
  }

  function renderFeatureGrid(id, items) {
    var grid = $(id);
    if (!grid) return;
    grid.innerHTML = items
      .map(function (row) {
        return (
          '<article class="proc-feature-card proc-reveal"><i class="' +
          row[0] +
          '" aria-hidden="true"></i><h3>' +
          esc(row[1]) +
          '</h3><p>' +
          esc(row[2]) +
          '</p></article>'
        );
      })
      .join('');
  }

  function renderQuality() {
    renderFeatureGrid('#proc-quality-grid', [
      ['fa-solid fa-person-digging', 'Site visits', 'Director-led inspections at critical construction milestones.'],
      ['fa-solid fa-people-arrows', 'Vendor coordination', 'Shortlisted contractors aligned to drawings and specifications.'],
      ['fa-solid fa-chart-simple', 'Progress tracking', 'Shared milestone calendar with weekly status updates.'],
      ['fa-solid fa-clipboard-check', 'Quality checks', 'Mock-up reviews, material verification, and snag resolution.'],
      ['fa-solid fa-folder-open', 'Documentation', 'Drawings, RFIs, and O&M binders maintained through handover.'],
      ['fa-solid fa-wrench', 'Issue resolution', 'Proactive RFIs and on-site decisions before they become delays.'],
    ]);
  }

  function renderTools() {
    renderFeatureGrid('#proc-tools-grid', [
      ['fa-solid fa-cube', '3D visualization', 'Massing, interiors, and walkthroughs before work begins.'],
      ['fa-solid fa-swatchbook', 'Material boards', 'Tactile palettes and FF&E schedules for sign-off.'],
      ['fa-solid fa-drafting-compass', 'Working drawings', 'Tender-ready packages contractors can build from.'],
      ['fa-solid fa-video', 'Site monitoring', 'Regular site photography and inspection logs.'],
      ['fa-solid fa-file-lines', 'Progress reports', 'Weekly summaries with decisions and next steps.'],
      ['fa-solid fa-comments', 'Client reviews', 'Structured feedback captured at every phase gate.'],
    ]);
  }

  function renderRisk() {
    renderFeatureGrid('#proc-risk-grid', [
      ['fa-solid fa-indian-rupee-sign', 'Budget transparency', 'Phased estimates and documented variations.'],
      ['fa-solid fa-file-signature', 'Approval support', 'Authority coordination handled within the studio.'],
      ['fa-solid fa-truck-field', 'Vendor management', 'Accountable supply chain under one contract.'],
      ['fa-solid fa-helmet-safety', 'Construction monitoring', 'Site rhythm held through director oversight.'],
      ['fa-solid fa-book', 'Documentation', 'Single source of truth for drawings and decisions.'],
      ['fa-solid fa-bezier-curve', 'Design coordination', 'Architecture, structure, and interiors aligned on site.'],
    ]);
  }

  function renderDurations() {
    var grid = $('#proc-durations');
    if (!grid) return;
    var items = [
      ['12–18 mo', 'Residential', 'Design, approvals, build & interiors'],
      ['8–14 mo', 'Commercial', 'Shell, services & fit-out delivery'],
      ['4–8 mo', 'Interiors', 'Planning through installation'],
      ['14–24 mo', 'Turnkey', 'Single contract, brief to keys'],
    ];
    grid.innerHTML = items
      .map(function (row) {
        return (
          '<article class="proc-duration-card proc-reveal"><strong>' +
          esc(row[0]) +
          '</strong><span>' +
          esc(row[1]) +
          '</span><p>' +
          esc(row[2]) +
          '</p></article>'
        );
      })
      .join('');
  }

  function renderCaseStudy(data) {
    var wrap = $('#proc-case-study');
    if (!wrap || !data.projects || !data.projects.length) return;
    var p = data.projects.filter(function (x) {
      return x.isFeatured;
    })[0] || data.projects[0];
    var img = p.heroImage ? mediaSrc(p.heroImage) : '';
    wrap.innerHTML =
      '<div class="proc-case proc-reveal">' +
      '<div class="proc-case__visual">' +
      (img ? '<img src="' + esc(img) + '" alt="' + esc(p.title) + '" width="800" height="550" loading="lazy" decoding="async" />' : '') +
      '</div><div class="proc-case__copy">' +
      '<p class="section-eyebrow">' +
      esc(p.category || 'Case study') +
      '</p>' +
      '<h3>' +
      esc(p.title) +
      '</h3>' +
      '<p>' +
      esc(p.summary || '') +
      '</p>' +
      '<div class="proc-case__meta">' +
      '<div class="proc-case__block"><h4>Challenge</h4><p>Deliver a cohesive design-build journey with clear milestones and quality on site.</p></div>' +
      '<div class="proc-case__block"><h4>Outcome</h4><p>' +
      esc(p.summary || 'Successful handover with documentation and client sign-off.') +
      '</p></div></div>' +
      '<a href="' +
      esc(p.linkUrl || 'work.html') +
      '" class="text-link">View full project <span aria-hidden="true">→</span></a></div></div>';
    bindLazyImages(wrap);
  }

  function renderImpact(data) {
    var grid = $('#proc-impact-grid');
    var bg = $('.proc-impact__bg');
    if (!grid) return;
    var stats = ((data.home && data.home.stats) || []).slice(0, 4);
    stats.push({ value: '95%', label: 'On-time delivery' });
    if (bg && data.pages && data.pages.process && data.pages.process.heroImage) {
      bg.style.backgroundImage = "url('" + mediaSrc(data.pages.process.heroImage).replace(/'/g, '%27') + "')";
    }
    grid.innerHTML = stats
      .map(function (row) {
        return (
          '<div class="proc-impact-stat proc-reveal"><strong data-count="' +
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

  function renderTestimonials(data) {
    var track = $('#proc-trust-track');
    if (!track || !data.testimonials || !data.testimonials.length) return;
    track.innerHTML = data.testimonials
      .slice(0, 4)
      .map(function (t) {
        return (
          '<figure class="proc-quote-card proc-reveal"><blockquote>&ldquo;' +
          esc(t.quote) +
          '&rdquo;</blockquote><p class="quote-name"><strong>' +
          esc(t.authorName) +
          '</strong></p>' +
          (t.authorRole ? '<p class="quote-role">' + esc(t.authorRole) + '</p>' : '') +
          '</figure>'
        );
      })
      .join('');
  }

  function renderFaq(page) {
    var list = $('#proc-faq-list');
    if (!list) return;
    var faq = (page && page.faq) || {};
    var items = faq.items && faq.items.length ? faq.items : FAQ_FALLBACK.items;
    var eyebrow = $('.site-process-faq-eyebrow');
    var title = $('.site-process-faq-title');
    if (eyebrow) eyebrow.textContent = faq.eyebrow || FAQ_FALLBACK.eyebrow;
    if (title) title.textContent = faq.title || FAQ_FALLBACK.title;
    list.innerHTML = items
      .map(function (item, i) {
        return (
          '<div class="proc-faq-item proc-reveal' +
          (i === 0 ? ' is-open' : '') +
          '"><button type="button" class="proc-faq-q" aria-expanded="' +
          (i === 0 ? 'true' : 'false') +
          '">' +
          esc(item.q) +
          '<i class="fa-solid fa-chevron-down" aria-hidden="true"></i></button><div class="proc-faq-a"><p>' +
          esc(item.a) +
          '</p></div></div>'
        );
      })
      .join('');
    list.querySelectorAll('.proc-faq-q').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var item = btn.closest('.proc-faq-item');
        var open = item.classList.contains('is-open');
        list.querySelectorAll('.proc-faq-item').forEach(function (el) {
          el.classList.remove('is-open');
          el.querySelector('.proc-faq-q').setAttribute('aria-expanded', 'false');
        });
        if (!open) {
          item.classList.add('is-open');
          btn.setAttribute('aria-expanded', 'true');
        }
      });
    });
  }

  function applyCta(copy, page) {
    copy = copy || {};
    var eyebrow = $('.site-process-cta-eyebrow');
    var title = $('.proc-cta__title.site-process-cta-title');
    var sub = $('.proc-cta__sub');
    var lead = $('.proc-cta__lead.site-process-cta-text');
    var btn = $('.site-process-cta-btn');
    var btn2 = $('.site-process-cta-btn-secondary');
    if (eyebrow && copy.process_cta_eyebrow) eyebrow.textContent = copy.process_cta_eyebrow;
    if (title && copy.process_cta_title) title.textContent = copy.process_cta_title;
    if (sub && copy.process_cta_sub) sub.textContent = copy.process_cta_sub;
    if (lead && copy.process_cta_text) lead.textContent = copy.process_cta_text;
    if (btn && copy.process_cta_btn_text) btn.textContent = copy.process_cta_btn_text;
    if (btn && copy.process_cta_btn_url) btn.setAttribute('href', copy.process_cta_btn_url);
    if (btn2 && (copy.process_cta_btn2_text || (page && page.ctaSecondary && page.ctaSecondary.text))) {
      btn2.textContent = copy.process_cta_btn2_text || page.ctaSecondary.text;
      btn2.setAttribute('href', copy.process_cta_btn2_url || (page && page.ctaSecondary && page.ctaSecondary.url) || 'contact.html');
      btn2.hidden = false;
    }
  }

  function animateCounters() {
    $$('[data-count]').forEach(function (el) {
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
            function frame(now) {
              var p = Math.min((now - start) / 1400, 1);
              var val = Math.round(target * (1 - Math.pow(1 - p, 3)));
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

  function initReveal() {
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
    $$('.proc-reveal').forEach(function (el) {
      io.observe(el);
    });
  }

  function loadScript(src) {
    return new Promise(function (resolve, reject) {
      var s = document.createElement('script');
      s.src = src;
      s.onload = resolve;
      s.onerror = reject;
      document.body.appendChild(s);
    });
  }

  function initGsap() {
    if (reduced || !window.gsap) {
      $$('.proc-reveal').forEach(function (el) {
        el.classList.add('is-in');
      });
      return;
    }
    if (window.ScrollTrigger) gsap.registerPlugin(ScrollTrigger);
    gsap.utils.toArray('.proc-reveal:not(.is-in)').forEach(function (el) {
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
          scrollTrigger: { trigger: el, start: 'top 90%' },
        }
      );
    });
    var heroMedia = $('.proc-hero__media');
    if (heroMedia && window.ScrollTrigger) {
      gsap.to(heroMedia, {
        yPercent: 14,
        scale: 1,
        ease: 'none',
        scrollTrigger: { trigger: '.proc-hero', start: 'top top', end: 'bottom top', scrub: true },
      });
    }
  }

  function loadGsap() {
    if (reduced) {
      $$('.proc-reveal').forEach(function (el) {
        el.classList.add('is-in');
      });
      return;
    }
    loadScript('https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js')
      .then(function () {
        return loadScript('https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js');
      })
      .then(initGsap)
      .catch(initReveal);
  }

  function hydrate(data) {
    if (!data) return;
    var page = data.pages && data.pages.process;
    applyHero(page);
    renderHeroStats(data);
    renderTrust();
    renderFramework(data);
    renderSplit(page);
    renderTimeline(data);
    renderJourney(data);
    renderQuality();
    renderTools();
    renderDurations();
    renderRisk();
    renderCaseStudy(data);
    renderImpact(data);
    renderTestimonials(data);
    renderFaq(page);
    applyCta(data.copy, page);
    animateCounters();
    initReveal();
    bindLazyImages($('#main'));
  }

  document.addEventListener('spangle:site-data', function (e) {
    hydrate(e.detail || window.__SPANGLE_SITE__);
  });
  document.addEventListener('spangle:content-updated', function () {
    if (window.__SPANGLE_SITE__) hydrate(window.__SPANGLE_SITE__);
  });
  if (window.__SPANGLE_SITE__) hydrate(window.__SPANGLE_SITE__);

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadGsap);
  } else {
    loadGsap();
  }

  window.setTimeout(function () {
    $$('.proc-reveal:not(.is-in)').forEach(function (el) {
      el.classList.add('is-in');
    });
  }, 2200);
}());
