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

  function mediaSrc(path, data) {
    if (!path) return '';
    var p = String(path).trim();
    if (/^https?:\/\//i.test(p)) return p;
    var b = baseUrl(data || window.__SPANGLE_SITE__ || {}).replace(/\/$/, '');
    var parts = p.split('/').map(function (seg) {
      try {
        return encodeURIComponent(decodeURIComponent(seg));
      } catch (e) {
        return encodeURIComponent(seg);
      }
    });
    return b + '/' + parts.join('/');
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
      try {
        localStorage.removeItem(STORAGE_KEY);
      } catch (e) {
        /* ignore */
      }
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
        try {
          localStorage.removeItem(STORAGE_KEY);
        } catch (e) {
          /* ignore */
        }
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
    // Stale XAMPP base in DB/site.json must not break images on live hosting.
    if (b && /localhost|127\.0\.0\.1/i.test(b)) {
      b = '';
    }
    if (!b && typeof window !== 'undefined' && window.location && /^https?:/i.test(window.location.protocol)) {
      b = (
        window.location.origin +
        window.location.pathname.replace(/\/[^/]*$/, '')
      ).replace(/\/$/, '');
    }
    return b || DEFAULT_BASE;
  }

  /** Always link to static journal article pages (avoids broken journal-post.php / cached 301s). */
  function journalArticleHref(post) {
    var slug = '';
    var url = '';
    if (post && typeof post === 'object') {
      slug = String(post.slug || '').trim();
      url = String(post.url || '').trim();
    } else {
      slug = String(post || '').trim();
    }
    if (url.indexOf('journal-post') !== -1 || url.indexOf('Applications') !== -1 || url.indexOf('xamppfiles') !== -1) {
      var m = url.match(/[?&]slug=([a-z0-9-]+)/i);
      if (m) slug = m[1];
      url = '';
    }
    if (/^https?:\/\//i.test(url) || url.indexOf('/') === 0) {
      url = '';
    }
    if (!slug && url) {
      slug = url.replace(/\?.*$/, '').replace(/\.html$/i, '');
    }
    if (!slug) return 'journal.html';
    var file = /\.html/i.test(slug) ? slug : slug + '.html';
    return file.indexOf('?v=3') !== -1 ? file : file + '?v=3';
  }

  function journalSlugFromHref(href) {
    var h = String(href || '').trim();
    if (!h) return '';
    if (h.indexOf('journal-post') !== -1 || h.indexOf('Applications') !== -1 || h.indexOf('xamppfiles') !== -1) {
      var q = h.match(/[?&]slug=([a-z0-9-]+)/i);
      return q ? q[1] : '';
    }
    var m = h.match(/(journal-[a-z0-9-]+)(?:\.html)?/i);
    return m ? m[1] : '';
  }

  function fixJournalCardLinks() {
    $$('.blog-card-link, .journal-row a[href]').forEach(function (a) {
      var slug = journalSlugFromHref(a.getAttribute('href') || '');
      if (slug) a.setAttribute('href', journalArticleHref(slug));
    });
  }

  function initJournalLinkGuard() {
    if (window.__spangleJournalLinkGuard) return;
    window.__spangleJournalLinkGuard = true;
    document.addEventListener('click', function (e) {
      var a = e.target.closest('.blog-card-link, .journal-row-img, .journal-row h2 a, .journal-row .text-link');
      if (!a) return;
      var h = a.getAttribute('href') || '';
      var slug = journalSlugFromHref(h);
      if (!slug) return;
      var target = journalArticleHref(slug);
      if (
        h.indexOf('journal-post') !== -1 ||
        h.indexOf('Applications') !== -1 ||
        h.indexOf('xamppfiles') !== -1 ||
        h.indexOf('v=3') === -1
      ) {
        e.preventDefault();
        window.location.assign(target);
      }
    }, true);
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

    var website = c.websiteUrl || '';
    $$('.site-website-link').forEach(function (a) {
      if (!website) return;
      var url = website.indexOf('http') === 0 ? website : 'https://' + website;
      a.setAttribute('href', url);
      a.setAttribute('rel', 'noopener noreferrer');
      a.textContent = website.replace(/^https?:\/\//i, '');
    });

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
        var img = esc(mediaSrc(p.heroImage || '', data));
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
    var items = data.homeGallery || data.gallery || [];
    if (!items.length) {
      var sec = $('#gallery');
      if (sec) sec.hidden = true;
      return;
    }
    wrap.innerHTML = items
      .map(function (g) {
        return (
          '<figure class="site-gallery-card">' +
          '<button type="button" class="site-gallery-open" data-src="' + esc(mediaSrc(g.src, data)) + '" data-alt="' + esc(g.alt || '') + '">' +
          '<img src="' + esc(mediaSrc(g.src, data)) + '" alt="' + esc(g.alt || '') + '" loading="lazy" width="800" height="600" decoding="async" />' +
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
        var img = esc(mediaSrc(p.heroImage || '', data));
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

    document.dispatchEvent(new CustomEvent('spangle:work-archive-rendered'));

    var LOCAL_PROJECT_FALLBACK = 'uploads/ENTRY.jpg';
    $$('.work-archive img').forEach(function (img) {
      img.addEventListener('error', function onProjImgErr() {
        img.removeEventListener('error', onProjImgErr);
        if (img.getAttribute('data-proj-fb')) return;
        if (/^https?:\/\//i.test(img.src || '')) return;
        img.setAttribute('data-proj-fb', '1');
        img.src = LOCAL_PROJECT_FALLBACK;
      });
    });
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
      img.src = mediaSrc(s.src, data);
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
    var h = (data || {}).home || {};
    var img = $('.site-about-image');
    if (img && h.aboutImage) {
      img.setAttribute('src', mediaSrc(h.aboutImage, data));
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

  function applyBranding(data) {
    var b = data.branding || {};
    var faviconUrl = b.favicon ? mediaSrc(b.favicon, data) : '';
    if (b.logo) {
      var logoUrl = mediaSrc(b.logo, data);
      $$('.footer-logo').forEach(function (img) {
        img.setAttribute('src', logoUrl);
        img.setAttribute('alt', b.brandName || 'Archevo Design');
      });
      $$('link[rel="icon"], link[rel="apple-touch-icon"]').forEach(function (link) {
        link.setAttribute('href', faviconUrl || logoUrl);
      });
    } else if (faviconUrl) {
      $$('link[rel="icon"], link[rel="apple-touch-icon"]').forEach(function (link) {
        link.setAttribute('href', faviconUrl);
      });
    }
    var lightUrl = b.logoLight ? mediaSrc(b.logoLight, data) : 'archevo-logo-light.png';
    var darkUrl = b.logoDark ? mediaSrc(b.logoDark, data) : 'archevo-logo-dark.png';
    $$('.brand-logo-full--light').forEach(function (img) {
      img.setAttribute('src', lightUrl);
    });
    $$('.brand-logo-full--dark').forEach(function (img) {
      img.setAttribute('src', darkUrl);
    });
    if (b.brandName) {
      $$('.brand-name, .site-brand-name').forEach(function (el) { el.textContent = b.brandName; });
      $$('.site-header .brand[aria-label]').forEach(function (el) {
        el.setAttribute('aria-label', b.brandName + ' home');
      });
    }
    if (b.brandLine) $$('.brand-line, .site-brand-line').forEach(function (el) { el.textContent = b.brandLine; });
    if (b.footerBlurbHtml) $$('.site-footer-blurb').forEach(function (el) { el.innerHTML = b.footerBlurbHtml; });
    if (b.footerCopyright) $$('.site-footer-copy').forEach(function (el) { el.textContent = b.footerCopyright; });
  }

  function applyNavigation(data) {
    var nav = data.navigation || {};
    Object.keys(nav).forEach(function (id) {
      var item = nav[id];
      if (!item) return;
      var sel = '.site-header [data-nav-link="' + id + '"], .nav-drawer-links [data-nav-link="' + id + '"]';
      $$(sel).forEach(function (a) {
        if (item.label) a.textContent = item.label;
        if (item.href) a.setAttribute('href', item.href);
      });
    });
  }

  function applyHomeSections(data) {
    var h = data.home || {};
    var svc = (data.pages && data.pages.services) || {};
    if (svc.kicker) {
      h.capabilitiesEyebrow = svc.kicker;
    }
    if (svc.title) {
      h.capabilitiesTitle = svc.title;
    }
    if (svc.lead) {
      h.capabilitiesIntro = svc.lead;
    }
    var map = [
      ['.site-capabilities-eyebrow', h.capabilitiesEyebrow],
      ['.site-capabilities-title', h.capabilitiesTitle],
      ['.site-capabilities-intro', h.capabilitiesIntro],
      ['.site-home-process-eyebrow', h.processEyebrow],
      ['.site-home-process-title', h.processTitle],
      ['.site-home-process-intro', h.processIntro],
      ['.site-testimonials-eyebrow', h.testimonialsEyebrow],
      ['.site-testimonials-title', h.testimonialsTitle],
      ['.site-awards-eyebrow', h.awardsEyebrow],
      ['.site-awards-title', h.awardsTitle],
      ['.site-team-eyebrow', h.teamEyebrow],
      ['.site-team-title', h.teamTitle],
      ['.site-journal-teaser-eyebrow', h.journalEyebrow],
      ['.site-journal-teaser-title', h.journalTitle],
      ['.site-cta-eyebrow', h.ctaEyebrow],
      ['.site-cta-title', h.ctaTitle],
      ['.site-cta-lead', h.ctaLead]
    ];
    map.forEach(function (pair) {
      var el = $(pair[0]);
      if (el && pair[1]) el.textContent = pair[1];
    });
    var ctaBtn = $('.site-cta-btn');
    if (ctaBtn && h.ctaBtnText) {
      ctaBtn.textContent = h.ctaBtnText;
      if (h.ctaBtnUrl) ctaBtn.setAttribute('href', h.ctaBtnUrl);
    }
  }

  function renderProcessList(containerSel, steps, context) {
    var list = $(containerSel);
    if (!list || !steps || !steps.length) return;
    var filtered = steps.filter(function (s) {
      var c = (s.context || 'both').toLowerCase();
      return c === 'both' || c === context;
    });
    if (!filtered.length) return;
    list.innerHTML = filtered.map(function (s) {
      return '<li><span class="process-step">' + esc(s.label) + '</span><h3>' + esc(s.title) + '</h3><p>' + esc(s.description) + '</p></li>';
    }).join('');
  }

  function renderTestimonials(data) {
    var track = $('#site-testimonials-track');
    if (!track || !data.testimonials || !data.testimonials.length) return;
    track.innerHTML = data.testimonials.map(function (t) {
      return '<figure class="quote-card"><blockquote>' + esc(t.quote) + '</blockquote><figcaption><span class="quote-name">' + esc(t.authorName) + '</span><span class="quote-role">' + esc(t.authorRole) + '</span></figcaption></figure>';
    }).join('');
  }

  function renderTeam(data) {
    var grid = $('#site-team-grid');
    if (!grid || !data.team || !data.team.length) return;
    grid.innerHTML = data.team.map(function (m) {
      var avatar = m.image
        ? '<img src="' + esc(mediaSrc(m.image, data)) + '" alt="" class="team-photo" loading="lazy" />'
        : '<div class="team-avatar" role="img" aria-label="' + esc(m.name) + ' initials">' + esc(m.initials || m.name.charAt(0)) + '</div>';
      return '<article class="team-card">' + avatar.replace('</div>', '</div>') + '<h3>' + esc(m.name) + '</h3><p class="team-role">' + esc(m.role) + '</p><p>' + esc(m.bio) + '</p></article>';
    }).join('');
  }

  function renderAwards(data) {
    var row = $('#site-awards-row');
    if (!row || !data.awards || !data.awards.length) return;
    row.innerHTML = data.awards.map(function (a) {
      return '<div class="award-item"><i class="' + esc(a.icon || 'fas fa-trophy') + '" aria-hidden="true"></i><div><h4>' + esc(a.title) + '</h4><p>' + esc(a.subtitle) + '</p></div></div>';
    }).join('');
  }

  function renderJournalTeaser(data) {
    var grid = $('#site-journal-teaser-grid');
    if (!grid || !data.journalPosts || !data.journalPosts.length) return;
    grid.innerHTML = data.journalPosts.slice(0, 4).map(function (j) {
      return '<article class="blog-card"><a href="' + esc(journalArticleHref(j)) + '" class="blog-card-link"><div class="blog-img"><img src="' + esc(mediaSrc(j.image, data)) + '" alt="" loading="lazy" width="800" height="450" decoding="async" /></div><div class="blog-body"><h3>' + esc(j.title) + '</h3><p>' + esc(j.excerpt) + '</p><span class="blog-more">Read</span></div></a></article>';
    }).join('');
    fixJournalCardLinks();
  }

  function detectSeoPageKey() {
    var path = (window.location.pathname || '').toLowerCase();
    if (path.indexOf('index.html') !== -1 || path.endsWith('/spangle_final') || path.endsWith('/spangle_final/')) {
      return 'home';
    }
    if (path.indexOf('studio') !== -1) return 'studio';
    if (path.indexOf('services') !== -1) return 'services';
    if (path.indexOf('work') !== -1) return 'work';
    if (path.indexOf('process') !== -1) return 'process';
    if (path.indexOf('journal') !== -1 && path.indexOf('journal-post') === -1) return 'journal';
    if (path.indexOf('contact') !== -1) return 'contact';
    if (path.indexOf('privacy') !== -1) return 'privacy';
    if (path.indexOf('terms') !== -1) return 'terms';
    if (path.indexOf('thanks') !== -1) return 'thanks';
    return '';
  }

  function applySeoPages(data) {
    var key = detectSeoPageKey();
    if (!key || !data.seoPages || !data.seoPages[key]) return;
    var seo = data.seoPages[key];
    if (seo.title) document.title = seo.title;
    var meta = $('meta[name="description"]');
    if (meta && seo.description) meta.setAttribute('content', seo.description);
  }

  function applyLegal(data) {
    var legal = data.legal || {};
    var body = $('.site-legal-body');
    if (!body) return;
    if (window.location.pathname.indexOf('privacy') !== -1 && legal.privacyHtml) {
      body.innerHTML = legal.privacyHtml;
    }
    if (window.location.pathname.indexOf('terms') !== -1 && legal.termsHtml) {
      body.innerHTML = legal.termsHtml;
    }
  }

  function setLinkHtml(el, text, url) {
    if (!el) return;
    if (url) el.setAttribute('href', url);
    if (text) {
      el.innerHTML = esc(text) + ' <span aria-hidden="true">→</span>';
    }
  }

  function applySiteCopy(data) {
    var c = data.copy || {};
    function setText(sel, key) {
      var el = $(sel);
      if (el && c[key]) el.textContent = c[key];
    }
    function setHtml(sel, key) {
      var el = $(sel);
      if (el && c[key]) el.innerHTML = c[key];
    }
    function setBtn(sel, textKey, urlKey) {
      var el = $(sel);
      if (!el) return;
      if (c[textKey]) el.textContent = c[textKey];
      if (c[urlKey]) el.setAttribute('href', c[urlKey]);
    }

    setBtn('.site-hero-btn-primary', 'home_hero_btn_primary_text', 'home_hero_btn_primary_url');
    setBtn('.site-hero-btn-secondary', 'home_hero_btn_secondary_text', 'home_hero_btn_secondary_url');
    setText('.site-hero-scroll-text', 'home_hero_scroll_text');
    setLinkHtml($('.site-home-link-about'), c.home_link_about_text, c.home_link_about_url);
    setLinkHtml($('.site-home-link-services'), c.home_link_services_text, c.home_link_services_url);
    setLinkHtml($('.site-home-link-work'), c.home_link_work_text, c.home_link_work_url);
    setLinkHtml($('.site-home-link-process'), c.home_link_process_text, c.home_link_process_url);
    setLinkHtml($('.site-home-link-journal'), c.home_link_journal_text, c.home_link_journal_url);

    setText('.site-studio-cta-text', 'studio_cta_text');
    setBtn('.site-studio-cta-btn', 'studio_cta_btn_text', 'studio_cta_btn_url');
    setText('.site-services-cta-eyebrow', 'services_cta_eyebrow');
    setText('.site-services-cta-title', 'services_cta_title');
    setText('.site-services-cta-lead', 'services_cta_lead');
    setBtn('.site-services-cta-btn', 'services_cta_btn_text', 'services_cta_btn_url');
    setText('.site-work-cta-text', 'work_cta_text');
    setBtn('.site-work-cta-btn', 'work_cta_btn_text', 'work_cta_btn_url');
    setText('.site-process-cta-text', 'process_cta_text');
    setBtn('.site-process-cta-btn', 'process_cta_btn_text', 'process_cta_btn_url');
    setText('.site-journal-cta-text', 'journal_cta_text');
    setBtn('.site-journal-cta-btn', 'journal_cta_btn_text', 'journal_cta_btn_url');

    $$('.site-work-filter').forEach(function (btn) {
      var f = btn.getAttribute('data-filter');
      if (f === 'all' && c.work_filter_all) btn.textContent = c.work_filter_all;
      if (f === 'residential' && c.work_filter_residential) btn.textContent = c.work_filter_residential;
      if (f === 'commercial' && c.work_filter_commercial) btn.textContent = c.work_filter_commercial;
      if (f === 'retail' && c.work_filter_retail) btn.textContent = c.work_filter_retail;
    });

    setText('.site-thanks-eyebrow', 'thanks_eyebrow');
    setText('.site-thanks-title', 'thanks_title');
    setHtml('#thanks-default-note', 'thanks_note_html');
    setHtml('#thanks-php-note', 'thanks_note_php_html');
    setText('.site-thanks-btn-home', 'thanks_btn_home_text');
    setText('.site-thanks-btn-work', 'thanks_btn_work_text');

    $$('.site-form-label-name').forEach(function (el) { if (c.form_label_name) el.textContent = c.form_label_name; });
    $$('.site-form-label-email').forEach(function (el) { if (c.form_label_email) el.textContent = c.form_label_email; });
    $$('.site-form-label-phone').forEach(function (el) { if (c.form_label_phone) el.textContent = c.form_label_phone; });
    $$('.site-form-label-project').forEach(function (el) { if (c.form_label_project_type) el.textContent = c.form_label_project_type; });
    $$('.site-form-label-message').forEach(function (el) { if (c.form_label_message) el.textContent = c.form_label_message; });
    $$('.site-form-input-name').forEach(function (el) { if (c.form_placeholder_name) el.setAttribute('placeholder', c.form_placeholder_name); });
    $$('.site-form-input-email').forEach(function (el) { if (c.form_placeholder_email) el.setAttribute('placeholder', c.form_placeholder_email); });
    $$('.site-form-input-phone').forEach(function (el) { if (c.form_placeholder_phone) el.setAttribute('placeholder', c.form_placeholder_phone); });
    $$('.site-form-input-project').forEach(function (el) { if (c.form_placeholder_project_type) el.setAttribute('placeholder', c.form_placeholder_project_type); });
    $$('.site-form-input-message').forEach(function (el) { if (c.form_placeholder_message) el.setAttribute('placeholder', c.form_placeholder_message); });
    $$('.site-form-note').forEach(function (el) { if (c.form_note_contact) el.textContent = c.form_note_contact; });
    $$('.site-form-submit').forEach(function (el) { if (c.form_submit_text) el.textContent = c.form_submit_text; });
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
      applyBranding(data);
      applyNavigation(data);
      applySeoPages(data);
      applyLegal(data);
      applySiteCopy(data);
      applyHomeCopy(data);
      applyHomeSections(data);
      renderHeroSlides(data);
      applyAboutImage(data);
      renderHomeServices(data);
      renderHomeProjects(data);
      renderGallery(data);
      renderProcessList('#site-home-process-list', data.processSteps, 'home');
      renderTestimonials(data);
      renderAwards(data);
      renderTeam(data);
      renderJournalTeaser(data);
      fixJournalCardLinks();
      initJournalLinkGuard();
      renderWorkArchive(data);

      document.dispatchEvent(new CustomEvent('spangle:site-data', { detail: data }));
      document.dispatchEvent(new CustomEvent('spangle:content-updated'));
    });
  }

  initJournalLinkGuard();

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
