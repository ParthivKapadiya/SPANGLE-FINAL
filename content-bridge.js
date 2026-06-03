(function () {
  'use strict';

  var STORAGE_KEY = 'spangle_site_data_v1';
  var DEFAULT_BASE = 'https://www.archevoinfra.com';

  function isLocalHost() {
    var h = (window.location && window.location.hostname) || '';
    return h === 'localhost' || h === '127.0.0.1';
  }

  function $(sel, root) {
    return (root || document).querySelector(sel);
  }
  function $$(sel, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(sel));
  }

  function esc(s) {
    if (s == null) return '';
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/"/g, '&quot;');
  }

  function appBase() {
    var scripts = document.getElementsByTagName('script');
    for (var i = scripts.length - 1; i >= 0; i--) {
      var src = scripts[i].getAttribute('src') || '';
      if (src.indexOf('content-bridge') !== -1 || src.indexOf('site-data.js') !== -1) {
        try {
          return new URL(src, window.location.href).href.replace(/\/(js\/content-bridge\.js|api\/site-data\.js\.php)(\?.*)?$/i, '');
        } catch (e) {
          break;
        }
      }
    }
    var path = window.location.pathname.replace(/\/[^/]*$/, '');
    return window.location.origin + path;
  }

  function loadJson() {
    if (window.__SPANGLE_SITE__ && typeof window.__SPANGLE_SITE__ === 'object') {
      return Promise.resolve(window.__SPANGLE_SITE__);
    }

    var base = appBase();
    var apiUrl = base + '/api/public-content.php?_=' + Date.now();

    return fetch(apiUrl, { credentials: 'same-origin', cache: 'no-store' })
      .then(function (r) {
        if (!r.ok) throw new Error('api');
        return r.json();
      })
      .then(function (data) {
        if (data && data.ok === false) throw new Error('api');
        return data;
      })
      .catch(function () {
        return fetch(base + '/content/site.json?_=' + Date.now(), { credentials: 'same-origin', cache: 'no-store' })
          .then(function (r2) {
            if (!r2.ok) throw new Error('site.json');
            return r2.json();
          });
      })
      .catch(function () {
        try {
          var raw = localStorage.getItem(STORAGE_KEY);
          if (raw) return JSON.parse(raw);
        } catch (e) {
          /* ignore */
        }
        return null;
      });
  }

  function baseUrl(data) {
    if (isLocalHost()) {
      var local = appBase();
      if (local) {
        return local.replace(/\/$/, '');
      }
      if (typeof window !== 'undefined' && window.location && /^https?:/i.test(window.location.protocol)) {
        return (
          window.location.origin +
          window.location.pathname.replace(/\/[^/]*$/, '')
        ).replace(/\/$/, '');
      }
    }

    var b = (data && data.publicBase) || '';
    b = String(b).trim().replace(/\/$/, '');
    if (!b && typeof window !== 'undefined' && window.location && /^https?:/i.test(window.location.protocol)) {
      b = (
        window.location.origin +
        window.location.pathname.replace(/\/[^/]*$/, '')
      ).replace(/\/$/, '');
    }
    return b || DEFAULT_BASE;
  }

  function categoryLabel(cat) {
    var c = String(cat || '').toLowerCase();
    if (c === 'residential') return 'Residential';
    if (c === 'commercial') return 'Commercial';
    if (c === 'retail') return 'Retail';
    return c ? c.charAt(0).toUpperCase() + c.slice(1) : 'Project';
  }

  function applyContactAndSocial(data) {
    var c = data.contact || {};
    var phone = c.phoneE164 || '';
    var phoneDisp = c.phoneDisplay || phone;
    var email = c.email || '';
    var addr = c.addressLine || '';
    var waDigits = String(c.whatsappDigits || '').replace(/\D/g, '');
    var waMsg = encodeURIComponent(c.whatsappPrefill || 'Hello Archevo Design');
    var waHref = waDigits ? 'https://wa.me/' + waDigits + '?text=' + waMsg : '';

    $$('a[href^="tel:"]').forEach(function (a) {
      if (phone) a.setAttribute('href', 'tel:' + phone.replace(/\s/g, ''));
      if (phoneDisp && a.closest('.contact-list')) a.textContent = phoneDisp;
    });
    $$('a[href^="mailto:"]').forEach(function (a) {
      if (email) {
        a.setAttribute('href', 'mailto:' + email);
        if (a.closest('.contact-list')) a.textContent = email;
      }
    });

    var addrText = $('.site-address-text');
    if (addrText && addr) addrText.textContent = addr;

    var titleEl = $('.site-contact-section-title');
    if (titleEl && c.contactSectionTitle) titleEl.textContent = c.contactSectionTitle;

    var leadEl = $('.site-contact-section-lead');
    if (leadEl && c.contactSectionLead) leadEl.textContent = c.contactSectionLead;

    var pageTitle = $('.site-contact-page-title');
    if (pageTitle && c.contactPageTitle) pageTitle.textContent = c.contactPageTitle;
    var pageLead = $('.site-contact-page-lead');
    if (pageLead && c.contactPageLead) pageLead.textContent = c.contactPageLead;

    var waLi = $('.site-whatsapp-link');
    if (waLi && waHref) {
      waLi.setAttribute('href', waHref);
      waLi.setAttribute('rel', 'noopener noreferrer');
      waLi.textContent = 'WhatsApp';
    }

    var social = data.social || {};
    var labels = {
      instagram: 'Instagram',
      facebook: 'Facebook',
      youtube: 'YouTube'
    };
    Object.keys(labels).forEach(function (net) {
      var url = social[net];
      if (!url || !String(url).trim()) return;
      $$('.social-row a[href]').forEach(function (a) {
        var icon = a.querySelector('.fa-' + (net === 'facebook' ? 'facebook-f' : net));
        if (icon) {
          a.setAttribute('href', url);
          a.setAttribute('aria-label', labels[net]);
        }
      });
    });

    $$('.enquiry-form').forEach(function (form) {
      if (!email) return;
      var act = form.getAttribute('action') || '';
      if (act.indexOf('formsubmit.co') !== -1) {
        form.setAttribute('action', 'https://formsubmit.co/' + encodeURIComponent(email));
      }
    });

    return { waHref: waHref, email: email };
  }

  function injectJsonLd(data) {
    var b = baseUrl(data);
    var c = data.contact || {};
    var script = document.createElement('script');
    script.type = 'application/ld+json';
    script.textContent = JSON.stringify({
      '@context': 'https://schema.org',
      '@type': 'ProfessionalService',
      name: data.siteName || 'Archevo Design',
      description: (data.seo && data.seo.organizationDescription) || '',
      url: b,
      image: (data.seo && data.seo.defaultOgImage) || '',
      telephone: c.phoneE164 || '',
      email: c.email || '',
      address: {
        '@type': 'PostalAddress',
        streetAddress: c.addressLine || '',
        addressCountry: 'IN'
      },
      areaServed: { '@type': 'Country', name: 'India' },
      sameAs: Object.keys(data.social || {})
        .map(function (k) {
          return data.social[k];
        })
        .filter(function (u) {
          return u && String(u).trim();
        })
    });
    document.head.appendChild(script);
  }

  function injectWaFab(waHref) {
    if (!waHref) return;
    if ($('.whatsapp-fab')) return;
    var a = document.createElement('a');
    a.className = 'whatsapp-fab';
    a.href = waHref;
    a.target = '_blank';
    a.rel = 'noopener noreferrer';
    a.setAttribute('aria-label', 'Chat on WhatsApp');
    a.innerHTML = '<span class="whatsapp-fab-inner"><i class="fab fa-whatsapp" aria-hidden="true"></i></span>';
    document.body.appendChild(a);
  }

  function applyMaps(data) {
    var url = data.maps && data.maps.embedUrl;
    if (!url || !String(url).trim()) return;
    var title = (data.maps && data.maps.title) || 'Map';
    $$('.site-map-embed').forEach(function (wrap) {
      wrap.innerHTML = '';
      var iframe = document.createElement('iframe');
      iframe.className = 'site-map-iframe';
      iframe.src = url.trim();
      iframe.title = title;
      iframe.loading = 'lazy';
      iframe.referrerPolicy = 'no-referrer-when-downgrade';
      iframe.setAttribute('allowfullscreen', '');
      wrap.appendChild(iframe);
    });
  }

  function applyHomeCopy(data) {
    var h = data.home || {};
    var eyebrow = $('.site-hero-eyebrow');
    if (eyebrow && h.heroEyebrow) eyebrow.textContent = h.heroEyebrow;
    var title = $('.site-hero-title');
    if (title && h.heroTitleHtml) title.innerHTML = h.heroTitleHtml;
    var lead = $('.site-hero-lead');
    if (lead && h.heroLead) lead.textContent = h.heroLead;

    if (h.stats && h.stats.length) {
      var stats = $$('.stats-bar .stat');
      h.stats.forEach(function (row, i) {
        if (!stats[i]) return;
        var v = stats[i].querySelector('.stat-value');
        var l = stats[i].querySelector('.stat-label');
        if (v && row.value != null) v.textContent = row.value;
        if (l && row.label) l.textContent = row.label;
      });
    }

    var abEyebrow = $('.site-about-eyebrow');
    if (abEyebrow && h.aboutEyebrow) abEyebrow.textContent = h.aboutEyebrow;
    var abTitle = $('.site-about-title');
    if (abTitle && h.aboutTitle) abTitle.textContent = h.aboutTitle;
    var abWrap = $('.site-about-lead-wrap');
    if (abWrap && h.aboutLeadHtml) abWrap.innerHTML = h.aboutLeadHtml;

    var ge = $('.site-gallery-eyebrow');
    if (ge && h.galleryEyebrow) ge.textContent = h.galleryEyebrow;
    var gt = $('.site-gallery-title');
    if (gt && h.galleryTitle) gt.textContent = h.galleryTitle;
    var gi = $('.site-gallery-intro');
    if (gi && h.galleryIntro) gi.textContent = h.galleryIntro;

    var pe = $('.site-projects-eyebrow');
    if (pe && h.projectsEyebrow) pe.textContent = h.projectsEyebrow;
    var pt = $('.site-projects-title');
    if (pt && h.projectsTitle) pt.textContent = h.projectsTitle;
    var pi = $('.site-projects-intro');
    if (pi && h.projectsIntro) pi.textContent = h.projectsIntro;
  }

  function renderHomeProjects(data) {
    var grid = $('#home-project-grid');
    if (!grid || !data.projects || !data.projects.length) return;

    var highlights = data.projects.filter(function (p) {
      return p.homeHighlight;
    });
    if (!highlights.length) highlights = data.projects.slice(0, 4);

    var layoutClass = function (layout, idx, total) {
      var L = String(layout || '').toLowerCase();
      if (L === 'lg' || L === 'wide') return L;
      if (total <= 4) {
        if (idx === 0) return 'lg';
        if (idx === 3) return 'wide';
      }
      return '';
    };

    var html = highlights
      .map(function (p, idx) {
        var cat = categoryLabel(p.category);
        var extra = layoutClass(p.homeLayout, idx, highlights.length);
        var cls = 'project-tile' + (extra === 'lg' ? ' project-tile-lg' : extra === 'wide' ? ' project-tile-wide' : '');
        var img = esc(p.heroImage || '');
        var title = esc(p.title);
        var loc = esc(p.location);
        var link = esc(p.linkUrl || 'work.html');
        var sum = esc(p.summary || '');
        return (
          '<a href="' + link + '" class="' + cls + '" title="' + esc(sum) + '">' +
          '<img src="' + img + '" alt="' + title + '" loading="lazy" width="900" height="600" />' +
          '<div class="project-meta">' +
          '<span class="project-cat">' + cat + '</span>' +
          '<h3>' + title + '</h3>' +
          '<span class="project-loc">' + loc + '</span>' +
          '</div></a>'
        );
      })
      .join('');

    grid.innerHTML = html;

    var LOCAL_PROJECT_FALLBACK = 'uploads/ENTRY.jpg';
    $$('#home-project-grid img').forEach(function (img) {
      img.addEventListener('error', function onProjImgErr() {
        img.removeEventListener('error', onProjImgErr);
        if (img.getAttribute('data-proj-fb')) return;
        img.setAttribute('data-proj-fb', '1');
        img.src = LOCAL_PROJECT_FALLBACK;
      });
    });
  }

  function renderGallery(data) {
    var wrap = $('#site-gallery-grid');
    if (!wrap) return;
    var items = data.gallery || [];
    if (!items.length) {
      var sec = $('#gallery');
      if (sec) sec.hidden = true;
      return;
    }
    wrap.innerHTML = items
      .map(function (g) {
        return (
          '<figure class="site-gallery-card">' +
          '<button type="button" class="site-gallery-open" data-src="' + esc(g.src) + '" data-alt="' + esc(g.alt || '') + '">' +
          '<img src="' + esc(g.src) + '" alt="' + esc(g.alt || '') + '" loading="lazy" width="800" height="600" decoding="async" />' +
          '</button>' +
          (g.caption ? '<figcaption>' + esc(g.caption) + '</figcaption>' : '') +
          '</figure>'
        );
      })
      .join('');

    if (!document.getElementById('site-gallery-lightbox')) {
      var lb = document.createElement('div');
      lb.id = 'site-gallery-lightbox';
      lb.className = 'site-gallery-lightbox';
      lb.hidden = true;
      lb.innerHTML =
        '<div class="site-gallery-lightbox-scrim" data-close="1" role="presentation"></div>' +
        '<div class="site-gallery-lightbox-panel" role="dialog" aria-modal="true" aria-label="Image preview">' +
        '<button type="button" class="site-gallery-lightbox-close" aria-label="Close">&times;</button>' +
        '<img src="" alt="" />' +
        '</div>';
      document.body.appendChild(lb);
      var imgEl = lb.querySelector('img');
      lb.addEventListener('click', function (e) {
        if (e.target.getAttribute('data-close') || e.target.closest('.site-gallery-lightbox-close')) {
          lb.hidden = true;
          imgEl.removeAttribute('src');
        }
      });
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !lb.hidden) {
          lb.hidden = true;
          imgEl.removeAttribute('src');
        }
      });
    }

    var lbRoot = $('#site-gallery-lightbox');
    wrap.addEventListener('click', function (e) {
      var btn = e.target.closest('.site-gallery-open');
      if (!btn || !lbRoot) return;
      var src = btn.getAttribute('data-src');
      var alt = btn.getAttribute('data-alt') || '';
      var im = lbRoot.querySelector('img');
      if (im && src) {
        im.src = src;
        im.alt = alt;
        lbRoot.hidden = false;
      }
    });
  }

  function renderWorkArchive(data) {
    var arch = $('.work-archive');
    if (!arch || !data.projects || !data.projects.length) return;

    var seen = { residential: false, commercial: false, retail: false };
    var html = data.projects
      .map(function (p) {
        var cat = String(p.category || '').toLowerCase();
        var idAttr = '';
        if (!seen[cat] && (cat === 'residential' || cat === 'commercial' || cat === 'retail')) {
          seen[cat] = true;
          idAttr = ' id="' + cat + '"';
        }
        var link = esc(p.linkUrl || 'contact.html');
        var img = esc(p.heroImage || '');
        var title = esc(p.title);
        var loc = esc(p.location || '');
        var sum = esc(p.summary || '');
        var catLab = categoryLabel(cat);
        return (
          '<a href="' + link + '" class="work-card"' + idAttr + ' data-work-cat="' + esc(cat) + '">' +
          '<img src="' + img + '" alt="' + title + '" loading="lazy" width="600" height="450" />' +
          '<div class="work-card-body"><span>' + catLab + '</span><h3>' + title + '</h3>' +
          '<p>' + esc(loc + (sum ? ' — ' + sum : '')) + '</p></div></a>'
        );
      })
      .join('');
    arch.innerHTML = html;

    var LOCAL_PROJECT_FALLBACK = 'uploads/ENTRY.jpg';
    $$('.work-archive img').forEach(function (img) {
      img.addEventListener('error', function onProjImgErr() {
        img.removeEventListener('error', onProjImgErr);
        if (img.getAttribute('data-proj-fb')) return;
        img.setAttribute('data-proj-fb', '1');
        img.src = LOCAL_PROJECT_FALLBACK;
      });
    });

    var workFilter = document.querySelector('[data-work-filter]');
    if (workFilter) {
      var hash = window.location.hash.replace(/^#/, '');
      if (hash) {
        var matchBtn = workFilter.querySelector('.work-filter-btn[data-filter="' + hash + '"]');
        if (matchBtn) matchBtn.click();
      }
    }
  }

  function renderHeroSlides(data) {
    var slider = $('#hero-slider');
    if (!slider || !data.heroSlides || !data.heroSlides.length) return;

    var slides = data.heroSlides.filter(function (s) {
      return s && s.src && String(s.src).trim();
    });
    if (!slides.length) return;

    $$('img.hero-slide', slider).forEach(function (img) {
      img.remove();
    });

    var anchor = slider.querySelector('.hero-scrim') || slider.firstChild;
    slides.forEach(function (s, i) {
      var img = document.createElement('img');
      img.className = 'hero-slide' + (i === 0 ? ' active' : '');
      img.src = s.src;
      img.alt = s.alt || '';
      img.width = 1920;
      img.height = 1080;
      img.decoding = 'async';
      img.setAttribute('aria-hidden', i === 0 ? 'false' : 'true');
      if (i === 0) img.setAttribute('fetchpriority', 'high');
      var next = slides[(i + 1) % slides.length];
      if (next && next.src) img.setAttribute('data-hero-fallback', next.src);
      slider.insertBefore(img, anchor);
    });

    setTimeout(function () {
      document.dispatchEvent(new CustomEvent('spangle:hero-slides-rendered', { detail: { count: slides.length } }));
    }, 0);
  }

  function applyAboutImage(data) {
    var h = data.home || {};
    var img = $('.site-about-image');
    if (img && h.aboutImage) {
      img.setAttribute('src', h.aboutImage);
      if (h.aboutImageAlt) img.setAttribute('alt', h.aboutImageAlt);
    }
    var cap = $('.site-about-caption');
    if (cap && h.aboutCaption) cap.textContent = h.aboutCaption;
  }

  function homeServicesList(data) {
    if (data.pages && data.pages.services && data.pages.services.items && data.pages.services.items.length) {
      return data.pages.services.items;
    }
    if (data.servicesHome && data.servicesHome.length) {
      return data.servicesHome;
    }
    return [];
  }

  function renderHomeServices(data) {
    var grid = $('#home-service-grid');
    var list = homeServicesList(data);
    if (!grid || !list.length) return;
    grid.innerHTML = list
      .map(function (s) {
        return (
          '<article class="service-card">' +
          '<span class="service-num">' + esc(s.number || '') + '</span>' +
          '<h3>' + esc(s.title) + '</h3>' +
          '<p>' + esc(s.shortDescription || '') + '</p>' +
          '</article>'
        );
      })
      .join('');
  }

  function setPublicBaseAttr(data) {
    var root = document.documentElement;
    var b = baseUrl(data);
    if (b) root.setAttribute('data-public-base', b);
  }

  function init() {
    loadJson().then(function (data) {
      if (!data) return;
      window.__SPANGLE_SITE__ = data;
      setPublicBaseAttr(data);
      var nextBase = baseUrl(data);
      $$('.formsubmit-next-url').forEach(function (input) {
        input.value = nextBase + '/thanks.html';
      });
      var extra = applyContactAndSocial(data);
      injectJsonLd(data);
      injectWaFab(extra && extra.waHref);
      applyMaps(data);
      applyHomeCopy(data);
      renderHeroSlides(data);
      applyAboutImage(data);
      renderHomeServices(data);
      renderHomeProjects(data);
      renderGallery(data);
      renderWorkArchive(data);

      document.dispatchEvent(new CustomEvent('spangle:site-data', { detail: data }));
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
