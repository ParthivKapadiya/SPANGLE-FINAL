(function () {
  'use strict';

  if (!document.body.classList.contains('page-contact')) return;
  if (!document.getElementById('cnt-enquiry-form')) return;

  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var TOTAL_STEPS = 4;

  var PROJECT_ICONS = {
    Residential: 'fa-solid fa-house',
    Commercial: 'fa-solid fa-building',
    'Interior Design': 'fa-solid fa-couch',
    Interior: 'fa-solid fa-couch',
    Construction: 'fa-solid fa-helmet-safety',
    Turnkey: 'fa-solid fa-key',
    Renovation: 'fa-solid fa-hammer',
  };

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
        src.indexOf('contact-premium.js') !== -1
      ) {
        try {
          return new URL(src, window.location.href).href.replace(
            /\/(js\/content-bridge\.js|js\/contact-premium\.js|api\/site-data\.js\.php)(\?.*)?$/i,
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

  function applyHero(page, stats) {
    page = page || {};
    var kicker = $('.cnt-hero__kicker.site-page-kicker');
    var title = $('.cnt-hero__title.site-page-hero-title');
    var lead = $('.cnt-hero__lead.site-page-hero-lead');
    var media = $('.cnt-hero__media');
    if (kicker && page.heroKicker) kicker.textContent = page.heroKicker;
    if (title && page.heroTitle) title.textContent = page.heroTitle;
    if (lead && page.heroLead) lead.textContent = page.heroLead;
    if (media && page.heroImage) {
      media.style.backgroundImage = "url('" + mediaSrc(page.heroImage).replace(/'/g, '%27') + "')";
    }
    var statsEl = $('#cnt-hero-stats');
    if (!statsEl) return;
    var defaults = [
      { value: '150+', label: 'Projects delivered' },
      { value: '16+', label: 'Years experience' },
      { value: '2M+', label: 'Sq ft designed' },
      { value: '98%', label: 'Client satisfaction' },
    ];
    if (stats && stats.length >= 4) {
      defaults = stats.slice(0, 4).map(function (s) {
        return { value: s.value, label: s.label };
      });
    }
    statsEl.innerHTML = defaults
      .map(function (s) {
        return (
          '<div class="cnt-hero-stat cnt-reveal"><strong data-count="' +
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

  function renderSteps(steps) {
    var el = $('#cnt-journey-steps');
    if (!el || !steps.length) return;
    el.innerHTML = steps
      .map(function (step, i) {
        return (
          '<div class="cnt-step-card cnt-reveal">' +
          '<p class="cnt-step-card__num">Step ' +
          (i + 1) +
          '</p>' +
          '<h3>' +
          esc(step.title) +
          '</h3>' +
          '<p>' +
          esc(step.text) +
          '</p></div>'
        );
      })
      .join('');
  }

  function renderProjectTypes(types) {
    var el = $('#cnt-project-types');
    var input = $('#cnt-project-type-input');
    if (!el) return;
    el.innerHTML = types
      .map(function (t) {
        var icon = PROJECT_ICONS[t] || 'fa-solid fa-drafting-compass';
        return (
          '<button type="button" class="cnt-pick" data-value="' +
          esc(t) +
          '"><i class="' +
          icon +
          '" aria-hidden="true"></i>' +
          esc(t) +
          '</button>'
        );
      })
      .join('');
    el.querySelectorAll('.cnt-pick').forEach(function (btn) {
      btn.addEventListener('click', function () {
        el.querySelectorAll('.cnt-pick').forEach(function (b) {
          b.classList.remove('is-selected');
        });
        btn.classList.add('is-selected');
        if (input) input.value = btn.getAttribute('data-value') || '';
      });
    });
  }

  function renderBudgetRanges(ranges) {
    var el = $('#cnt-budget-ranges');
    var input = $('#cnt-budget-input');
    if (!el) return;
    el.innerHTML = ranges
      .map(function (r) {
        return (
          '<button type="button" class="cnt-budget" data-value="' +
          esc(r) +
          '">' +
          esc(r) +
          '</button>'
        );
      })
      .join('');
    el.querySelectorAll('.cnt-budget').forEach(function (btn) {
      btn.addEventListener('click', function () {
        el.querySelectorAll('.cnt-budget').forEach(function (b) {
          b.classList.remove('is-selected');
        });
        btn.classList.add('is-selected');
        if (input) input.value = btn.getAttribute('data-value') || '';
      });
    });
  }

  function renderCards(containerId, items, dark) {
    var el = $(containerId);
    if (!el || !items.length) return;
    el.innerHTML = items
      .map(function (item) {
        return (
          '<div class="cnt-card cnt-reveal">' +
          (item.icon ? '<i class="' + esc(item.icon) + '" aria-hidden="true"></i>' : '') +
          '<h3>' +
          esc(item.title) +
          '</h3>' +
          '<p>' +
          esc(item.text) +
          '</p></div>'
        );
      })
      .join('');
    if (!items.length && el.closest('section')) el.closest('section').hidden = true;
  }

  function renderReasons(reasons) {
    var icons = [
      'fa-solid fa-house-chimney',
      'fa-solid fa-landmark',
      'fa-solid fa-briefcase',
      'fa-solid fa-couch',
      'fa-solid fa-helmet-safety',
      'fa-solid fa-key',
    ];
    renderCards(
      '#cnt-reasons',
      reasons.map(function (r, i) {
        return {
          title: r,
          text: 'Speak with the studio about your brief.',
          icon: icons[i] || 'fa-solid fa-circle',
        };
      })
    );
  }

  function buildWaHref(contact) {
    contact = contact || {};
    var waDigits = String(contact.whatsappDigits || '').replace(/\D/g, '');
    var waMsg = encodeURIComponent(
      contact.whatsappPrefill || 'Hello — I would like to discuss a project.'
    );
    return waDigits ? 'https://wa.me/' + waDigits + '?text=' + waMsg : '';
  }

  function renderFounder(team, quote) {
    var el = $('#cnt-founder');
    if (!el || !team.length) return;
    var founder = team[0];
    var img = founder.image
      ? '<img src="' + esc(mediaSrc(founder.image)) + '" alt="' + esc(founder.name) + '" loading="lazy" width="600" height="750" decoding="async" />'
      : '';
    el.innerHTML =
      '<div class="cnt-founder__img">' +
      img +
      '</div><div>' +
      '<p class="section-eyebrow">Meet the studio</p>' +
      '<h2 class="section-title">' +
      esc(founder.name) +
      '</h2>' +
      '<p class="section-lead">' +
      esc(founder.role || '') +
      '</p>' +
      '<p>' +
      esc(founder.bio || '') +
      '</p>' +
      (quote ? '<blockquote>' + esc(quote) + '</blockquote>' : '') +
      '</div>';
    bindLazyImages(el);
  }

  function renderTestimonials(testimonials) {
    var el = $('#cnt-testimonials');
    if (!el || !testimonials.length) {
      if (el && el.closest('section')) el.closest('section').hidden = true;
      return;
    }
    el.innerHTML = testimonials
      .slice(0, 4)
      .map(function (t) {
        return (
          '<div class="cnt-quote cnt-reveal"><blockquote>“' +
          esc(t.quote) +
          '”</blockquote><cite>' +
          esc(t.authorName || '') +
          (t.authorRole ? ' · ' + esc(t.authorRole) : '') +
          '</cite></div>'
        );
      })
      .join('');
  }

  function renderFaq(page) {
    var el = $('#cnt-faq');
    if (!el) return;
    var items = (page && page.faq && page.faq.items) || [];
    if (!items.length) {
      el.closest('section').hidden = true;
      return;
    }
    el.innerHTML = items
      .map(function (item, i) {
        return (
          '<div class="cnt-faq-item cnt-reveal">' +
          '<button type="button" class="cnt-faq-q" aria-expanded="false" aria-controls="cnt-faq-a-' +
          i +
          '">' +
          esc(item.q) +
          '<span aria-hidden="true">+</span></button>' +
          '<div class="cnt-faq-a" id="cnt-faq-a-' +
          i +
          '">' +
          esc(item.a) +
          '</div></div>'
        );
      })
      .join('');
    el.querySelectorAll('.cnt-faq-q').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var item = btn.closest('.cnt-faq-item');
        var open = item.classList.contains('is-open');
        el.querySelectorAll('.cnt-faq-item').forEach(function (node) {
          node.classList.remove('is-open');
          node.querySelector('.cnt-faq-q').setAttribute('aria-expanded', 'false');
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
    page = page || {};
    var title = $('.cnt-cta__title.site-contact-cta-title');
    var sub = $('.cnt-cta__sub');
    var btn = $('.site-contact-cta-btn');
    var btn2 = $('.site-contact-cta-btn-secondary');
    if (title && copy.contact_cta_title) title.textContent = copy.contact_cta_title;
    if (sub && copy.contact_cta_sub) sub.textContent = copy.contact_cta_sub;
    if (btn && copy.contact_cta_btn_text) btn.textContent = copy.contact_cta_btn_text;
    if (btn && copy.contact_cta_btn_url) btn.setAttribute('href', copy.contact_cta_btn_url);
    if (btn2 && (copy.contact_cta_btn2_text || (page.ctaSecondary && page.ctaSecondary.text))) {
      btn2.textContent = copy.contact_cta_btn2_text || page.ctaSecondary.text;
      btn2.setAttribute('href', copy.contact_cta_btn2_url || (page.ctaSecondary && page.ctaSecondary.url) || '#cnt-enquiry-form');
      btn2.hidden = false;
    }
  }

  function applyWhatsApp(waHref, copy, page) {
    var link = $('#cnt-wa-link');
    var lead = $('.cnt-wa__lead');
    if (lead && (copy.contact_wa_lead || (page && page.whatsappLead))) {
      lead.textContent = copy.contact_wa_lead || page.whatsappLead;
    }
    if (link && waHref) link.setAttribute('href', waHref);
  }

  function applyVisit(page, contact) {
    page = page || {};
    contact = contact || {};
    var parking = $('.cnt-visit-parking');
    var appt = $('.cnt-visit-appointment');
    var title = $('.site-contact-page-title');
    var lead = $('.site-contact-page-lead');
    if (parking && page.visitParking) parking.textContent = page.visitParking;
    if (appt && page.visitAppointment) appt.textContent = page.visitAppointment;
    if (title && contact.contactPageTitle) title.textContent = contact.contactPageTitle;
    if (lead && contact.contactPageLead) lead.textContent = contact.contactPageLead;
    var hours = $('.site-contact-hours');
    if (hours && page.hoursHtml) hours.innerHTML = page.hoursHtml;
  }

  function initMultiStepForm() {
    var form = $('#cnt-enquiry-form');
    if (!form) return;
    var current = 1;
    var progressLabel = $('#cnt-form-step-label');
    var segs = $$('.cnt-form-progress__seg');
    var steps = $$('.cnt-form-step', form);
    var btnBack = $('#cnt-form-back');
    var btnNext = $('#cnt-form-next');
    var btnSubmit = $('#cnt-form-submit');

    function validateStep(n) {
      var step = form.querySelector('.cnt-form-step[data-step="' + n + '"]');
      if (!step) return true;
      var fields = step.querySelectorAll('input, textarea, select');
      var ok = true;
      fields.forEach(function (f) {
        if (f.type === 'hidden' || f.name === '_gotcha' || !f.name && f.id !== 'cnt-area' && f.id !== 'cnt-timeline') return;
        if (f.required && !f.value.trim()) {
          f.reportValidity();
          ok = false;
          return;
        }
        if (f.name && !f.checkValidity()) {
          f.reportValidity();
          ok = false;
        }
      });
      if (n === 2) {
        var pt = $('#cnt-project-type-input');
        if (pt && !pt.value.trim()) {
          ok = false;
          alert('Please select a project type.');
        }
      }
      if (n === 3) {
        var budget = $('#cnt-budget-input');
        if (budget && !budget.value.trim()) {
          ok = false;
          alert('Please select a budget range.');
        }
      }
      return ok;
    }

    function goTo(n) {
      current = n;
      steps.forEach(function (step) {
        step.classList.toggle('is-active', parseInt(step.getAttribute('data-step'), 10) === n);
      });
      segs.forEach(function (seg, i) {
        seg.classList.toggle('is-active', i + 1 === n);
        seg.classList.toggle('is-done', i + 1 < n);
      });
      if (progressLabel) progressLabel.textContent = 'Step ' + n + ' of ' + TOTAL_STEPS;
      if (btnBack) btnBack.hidden = n === 1;
      if (btnNext) btnNext.hidden = n === TOTAL_STEPS;
      if (btnSubmit) btnSubmit.hidden = n !== TOTAL_STEPS;
    }

    if (btnBack) {
      btnBack.addEventListener('click', function () {
        if (current > 1) goTo(current - 1);
      });
    }
    if (btnNext) {
      btnNext.addEventListener('click', function () {
        if (!validateStep(current)) return;
        if (current < TOTAL_STEPS) goTo(current + 1);
      });
    }

    form.addEventListener('submit', function (e) {
      var s;
      for (s = 1; s <= TOTAL_STEPS; s++) {
        if (!validateStep(s)) {
          e.preventDefault();
          goTo(s);
          return;
        }
      }
      var msg = form.querySelector('[name="message"]');
      var area = $('#cnt-area');
      var timeline = $('#cnt-timeline');
      if (msg) {
        var prefix = [];
        if (area && area.value.trim()) prefix.push('Approx. area: ' + area.value.trim());
        if (timeline && timeline.value.trim()) prefix.push('Timeline: ' + timeline.value.trim());
        if (prefix.length) {
          msg.value = prefix.join('\n') + '\n\n' + msg.value.trim();
        }
      }
    });

    try {
      var params = new URLSearchParams(window.location.search);
      var email = params.get('email');
      if (email) {
        var emailInput = form.querySelector('[name="email"]');
        if (emailInput) emailInput.value = email;
      }
    } catch (err) {
      /* ignore */
    }

    goTo(1);
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
      { threshold: 0.08, rootMargin: '0px 0px -5% 0px' }
    );
    $$('.cnt-reveal').forEach(function (el) {
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
      $$('.cnt-reveal').forEach(function (el) {
        el.classList.add('is-in');
      });
      return;
    }
    if (window.ScrollTrigger) gsap.registerPlugin(ScrollTrigger);
    gsap.utils.toArray('.cnt-reveal:not(.is-in)').forEach(function (el) {
      gsap.fromTo(
        el,
        { opacity: 0, y: 32 },
        {
          opacity: 1,
          y: 0,
          duration: 0.9,
          ease: 'power3.out',
          onComplete: function () {
            el.classList.add('is-in');
          },
          scrollTrigger: { trigger: el, start: 'top 92%' },
        }
      );
    });
    var heroMedia = $('.cnt-hero__media');
    if (heroMedia && window.ScrollTrigger) {
      gsap.to(heroMedia, {
        scale: 1.12,
        ease: 'none',
        scrollTrigger: { trigger: '.cnt-hero', start: 'top top', end: 'bottom top', scrub: true },
      });
    }
  }

  function hydrate(data) {
    data = data || window.__SPANGLE_SITE__ || {};
    var page = data.pages && data.pages.contact;
    var contact = data.contact || {};
    var stats = (data.home && data.home.stats) || [];
    var team = data.team || [];
    var testimonials = data.testimonials || [];
    var copy = data.copy || {};

    applyHero(page, stats);
    if (page) {
      renderSteps(page.steps || []);
      renderProjectTypes(page.projectTypes || []);
      renderBudgetRanges(page.budgetRanges || []);
      renderCards('#cnt-trust', page.trustPoints || [], true);
      renderReasons(page.reasons || []);
      renderFounder(team, page.founderQuote || '');
      renderFaq(page);
      applyVisit(page, contact);
      applyWhatsApp(buildWaHref(contact), copy, page);
    }
    renderTestimonials(testimonials);
    applyCta(copy, page);

    var introTitle = $('.cnt-intro-title');
    var introLead = $('.cnt-intro-lead');
    if (introTitle && page && page.introTitle) introTitle.textContent = page.introTitle;
    if (introLead && page && page.introLead) introLead.textContent = page.introLead;

    initMultiStepForm();
    initReveal();
    animateCounters();
    bindLazyImages(document);
    document.dispatchEvent(new CustomEvent('spangle:content-updated'));
  }

  function onData(e) {
    var data = e.detail || e;
    hydrate(data);
  }

  document.addEventListener('spangle:site-data', onData);
  document.addEventListener('spangle:contact-ready', function (e) {
    var data = window.__SPANGLE_SITE__ || {};
    if (e.detail && e.detail.waHref) data._waHref = e.detail.waHref;
    hydrate(data);
  });

  if (window.__SPANGLE_SITE__) hydrate(window.__SPANGLE_SITE__);

  loadScript('https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js')
    .then(function () {
      return loadScript('https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js');
    })
    .then(initGsap)
    .catch(initReveal);
}());
