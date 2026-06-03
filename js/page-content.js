(function () {
  'use strict';

  function $(sel, root) {
    return (root || document).querySelector(sel);
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
      if (src.indexOf('content-bridge') !== -1 || src.indexOf('site-data.js') !== -1 || src.indexOf('page-content.js') !== -1) {
        try {
          return new URL(src, window.location.href).href.replace(
            /\/(js\/content-bridge\.js|js\/page-content\.js|api\/site-data\.js\.php)(\?.*)?$/i,
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
  /* Same-origin relative paths work on localhost and production */
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

  function applyPageHero(page) {
    if (!page) return;
    var k = $('.site-page-kicker');
    var t = $('.site-page-hero-title');
    var l = $('.site-page-hero-lead');
    if (k && page.kicker) k.textContent = page.kicker;
    if (t && page.title) t.textContent = page.title;
    if (l && page.lead) l.textContent = page.lead;
    var hero = $('.site-page-hero');
    if (hero && page.heroImage) hero.style.backgroundImage = "url('" + mediaSrc(page.heroImage).replace(/'/g, '%27') + "')";
  }

  function applyStudio(page) {
    if (!page) return;
    applyPageHero(page);
    var pe = $('.site-studio-philosophy-eyebrow');
    var pt = $('.site-studio-philosophy-title');
    var p1 = $('.site-studio-philosophy-lead-1');
    var p2 = $('.site-studio-philosophy-lead-2');
    var pi = $('.site-studio-philosophy-image');
    if (pe && page.philosophyEyebrow) pe.textContent = page.philosophyEyebrow;
    if (pt && page.philosophyTitle) pt.textContent = page.philosophyTitle;
    if (p1 && page.philosophyLead1) p1.textContent = page.philosophyLead1;
    if (p2 && page.philosophyLead2) p2.textContent = page.philosophyLead2;
    if (pi && page.philosophyImage) pi.setAttribute('src', mediaSrc(page.philosophyImage));
    var ve = $('.site-studio-values-eyebrow');
    var vt = $('.site-studio-values-title');
    var vw = $('.site-studio-values-wrap');
    if (ve && page.valuesEyebrow) ve.textContent = page.valuesEyebrow;
    if (vt && page.valuesTitle) vt.textContent = page.valuesTitle;
    if (vw && page.valuesHtml) vw.innerHTML = page.valuesHtml;
    var pq = $('.site-studio-pullquote');
    if (pq && page.pullquote) pq.textContent = page.pullquote;
    var strip = $('.site-studio-strip');
    if (strip && page.stripImages && page.stripImages.length) {
      var stripImgs = strip.querySelectorAll('img');
      page.stripImages.filter(Boolean).forEach(function (src, i) {
        if (stripImgs[i]) {
          stripImgs[i].setAttribute('src', mediaSrc(src));
        }
      });
    }
  }

  function applyServices(page) {
    if (!page) return;
    applyPageHero(page);
    var grid = $('#site-services-detail-grid');
    if (!grid || !page.items || !page.items.length) return;
    grid.innerHTML = page.items
      .map(function (s) {
        return (
          '<article class="service-detail-block fade-slide">' +
          '<div class="service-detail-copy">' +
          '<p class="section-eyebrow">' + esc(s.eyebrow || '') + '</p>' +
          '<h2 class="section-title">' + esc(s.detailTitle || s.title) + '</h2>' +
          '<p class="section-lead">' + esc(s.detailLead1 || '') + '</p>' +
          (s.detailLead2 ? '<p class="section-lead">' + esc(s.detailLead2) + '</p>' : '') +
          '</div>' +
          '<div class="service-detail-img">' +
          (s.image ? '<img src="' + esc(mediaSrc(s.image)) + '" alt="" loading="lazy" width="800" height="600" decoding="async" />' : '') +
          '</div></article>'
        );
      })
      .join('');
  }

  function applyContactExtras(page) {
    if (!page) return;
    applyPageHero({
      kicker: page.heroKicker,
      title: page.heroTitle,
      lead: page.heroLead,
      heroImage: page.heroImage
    });
    var hours = $('.site-contact-hours');
    if (hours && page.hoursHtml) hours.innerHTML = page.hoursHtml;
  }

  function applyProcessPage(page, processSteps) {
    if (!page) return;
    applyPageHero(page);
    var se = $('.site-process-split-eyebrow');
    var st = $('.site-process-split-title');
    var sw = $('.site-process-split-lead');
    var si = $('.site-process-split-image');
    if (se && page.splitEyebrow) se.textContent = page.splitEyebrow;
    if (st && page.splitTitle) st.textContent = page.splitTitle;
    if (sw && page.splitLeadHtml) sw.innerHTML = page.splitLeadHtml;
    if (si && page.splitImage) si.setAttribute('src', page.splitImage);
    var list = $('#site-process-page-list');
    if (list && processSteps && processSteps.length) {
      var steps = processSteps.filter(function (s) {
        var c = (s.context || 'both').toLowerCase();
        return c === 'both' || c === 'page';
      });
      list.innerHTML = steps.map(function (s) {
        return '<li><span class="process-step">' + esc(s.label) + '</span><h3>' + esc(s.title) + '</h3><p>' + esc(s.description) + '</p></li>';
      }).join('');
    }
  }

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
    if (/^https?:\/\//i.test(url) || (url.indexOf('/') === 0 && url.indexOf('uploads/') !== 0)) {
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

  function fixJournalListLinks(root) {
    var scope = root || document;
    scope.querySelectorAll('.journal-row a[href], .blog-card-link').forEach(function (a) {
      var slug = journalSlugFromHref(a.getAttribute('href') || '');
      if (slug) a.setAttribute('href', journalArticleHref(slug));
    });
  }

  function journalMetaLine(j) {
    var parts = [];
    if (j.category) parts.push(esc(j.category));
    if (j.readMinutes) parts.push(esc(String(j.readMinutes)) + ' min read');
    if (!parts.length) return '';
    return '<p class="journal-meta">' + parts.join(' · ') + '</p>';
  }

  function renderJournalList(posts) {
    var list = $('#site-journal-list');
    if (!list || !posts || !posts.length) return;
    list.innerHTML = posts.map(function (j) {
      var url = esc(journalArticleHref(j));
      var img = esc(mediaSrc(j.image));
      var title = esc(j.title);
      var excerpt = esc(j.excerpt || '');
      return (
        '<article class="journal-row">' +
        '<a href="' + url + '" class="journal-row-img">' +
        '<img src="' + img + '" alt="' + title + '" loading="lazy" width="800" height="500" decoding="async" />' +
        '</a>' +
        '<div>' +
        journalMetaLine(j) +
        '<h2><a href="' + url + '">' + title + '</a></h2>' +
        '<p>' + excerpt + '</p>' +
        '<a href="' + url + '" class="text-link">Read article <span aria-hidden="true">→</span></a>' +
        '</div></article>'
      );
    }).join('');
    document.dispatchEvent(new CustomEvent('spangle:content-updated'));
    if (window.SpangleMotion) {
      window.SpangleMotion.refresh();
    }
    list.querySelectorAll('.journal-row-img').forEach(function (imgWrap) {
      imgWrap.classList.add('motion-media-revealed');
    });
    fixJournalListLinks(list);
  }

  function onData(e) {
    var data = e.detail || window.__SPANGLE_SITE__;
    if (!data || !data.pages) return;
    if (document.body.classList.contains('page-studio')) applyStudio(data.pages.studio);
    if (document.body.classList.contains('page-services')) applyServices(data.pages.services);
    if (document.body.classList.contains('page-work')) applyPageHero(data.pages.work);
    if (document.body.classList.contains('page-contact')) applyContactExtras(data.pages.contact);
    if (document.body.classList.contains('page-process')) applyProcessPage(data.pages.process, data.processSteps);
    if (document.body.classList.contains('page-journal')) {
      applyPageHero(data.pages.journal);
      renderJournalList(data.journalPosts);
    }
  }

  document.addEventListener('spangle:site-data', onData);
  if (window.__SPANGLE_SITE__) onData({ detail: window.__SPANGLE_SITE__ });
}());
