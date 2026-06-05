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

  function resetMotionTitle(el) {
    if (!el) return;
    el.classList.remove('motion-title');
    el.removeAttribute('data-motion-split');
    el.classList.add('is-revealed');
  }

  function applyPageHero(page) {
    if (!page) return;
    var k = $('.site-page-kicker');
    var t = $('.site-page-hero-title');
    var l = $('.site-page-hero-lead');
    if (k && page.kicker) k.textContent = page.kicker;
    if (t && page.title) {
      t.textContent = page.title;
      resetMotionTitle(t);
    }
    if (l && page.lead) l.textContent = page.lead;
    var hero = $('.studio-hero__media') || $('.site-page-hero');
    if (hero && page.heroImage) hero.style.backgroundImage = "url('" + mediaSrc(page.heroImage).replace(/'/g, '%27') + "')";
  }

  function bindStudioValueCards(vw) {
    if (!vw) return;
    var section = vw.closest('.fade-slide');
    vw.querySelectorAll('.value-card').forEach(function (card, i) {
      card.classList.remove('fade-slide');
      card.classList.add('motion-item');
      card.style.setProperty('--motion-i', String(i));
      if (section && section.classList.contains('active')) {
        card.classList.add('motion-in');
      }
    });
    if (section && section.getBoundingClientRect().top < window.innerHeight * 0.92) {
      section.classList.add('active');
      vw.querySelectorAll('.value-card').forEach(function (card) {
        card.classList.add('motion-in');
      });
    }
  }

  function applyStudio(page, data) {
    if (!page) return;
    data = data || window.__SPANGLE_SITE__ || {};
    applyPageHero(page);
    var pe = $('.site-studio-philosophy-eyebrow');
    var pt = $('.site-studio-philosophy-title');
    var p1 = $('.site-studio-philosophy-lead-1');
    var p2 = $('.site-studio-philosophy-lead-2');
    var pi = $('.site-studio-philosophy-image');
    if (pe && page.philosophyEyebrow) pe.textContent = page.philosophyEyebrow;
    if (pt && page.philosophyTitle) {
      pt.textContent = page.philosophyTitle;
      resetMotionTitle(pt);
    }
    if (p1 && page.philosophyLead1) p1.textContent = page.philosophyLead1;
    if (p2 && page.philosophyLead2) p2.textContent = page.philosophyLead2;
    if (pi && page.philosophyImage) pi.setAttribute('src', mediaSrc(page.philosophyImage));
    var storyImg = document.getElementById('studio-story-image');
    if (storyImg && page.philosophyImage) storyImg.setAttribute('src', mediaSrc(page.philosophyImage));
    var ve = $('.site-studio-values-eyebrow');
    var vt = $('.site-studio-values-title');
    var vw = $('.site-studio-values-wrap');
    if (ve && page.valuesEyebrow) ve.textContent = page.valuesEyebrow;
    if (vt && page.valuesTitle) {
      vt.textContent = page.valuesTitle;
      resetMotionTitle(vt);
    }
    if (vw) {
      if (data.awards && data.awards.length) {
        vw.innerHTML = data.awards
          .map(function (a) {
            return (
              '<div class="value-card"><h3>' +
              esc(a.title) +
              '</h3><p>' +
              esc(a.subtitle || '') +
              '</p></div>'
            );
          })
          .join('');
      } else if (page.valuesHtml) {
        vw.innerHTML = page.valuesHtml;
      }
      bindStudioValueCards(vw);
    }
    document.querySelectorAll('.page-studio .fade-slide').forEach(function (section) {
      if (section.getBoundingClientRect().top < window.innerHeight * 0.92) {
        section.classList.add('active');
        section.querySelectorAll('.motion-item').forEach(function (item) {
          item.classList.add('motion-in');
        });
      }
    });
    var pq = $('.site-studio-pullquote');
    if (pq && page.pullquote) pq.textContent = page.pullquote;
    var founderQuote = document.getElementById('studio-founder-quote');
    if (founderQuote && page.pullquote) founderQuote.textContent = page.pullquote.replace(/^["“]|["”]$/g, '');
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
    if (document.getElementById('svc-detail-section')) return;
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
    if (document.getElementById('proc-timeline')) return;
    applyPageHero(page);
    var se = $('.site-process-split-eyebrow');
    var st = $('.site-process-split-title');
    var si = $('.site-process-split-image');
    var te = $('.site-process-timeline-eyebrow');
    var tt = $('.site-process-timeline-title');
    if (se && page.splitEyebrow) se.textContent = page.splitEyebrow;
    if (st && page.splitTitle) st.textContent = page.splitTitle;
    if (te && page.timelineEyebrow) te.textContent = page.timelineEyebrow;
    if (tt && page.timelineTitle) tt.textContent = page.timelineTitle;
    if (si && page.splitImage) si.setAttribute('src', mediaSrc(page.splitImage));
    var splitLeads = document.querySelectorAll('.site-process-split-lead');
    if (splitLeads.length) {
      if (page.splitLead1) splitLeads[0].textContent = page.splitLead1;
      if (splitLeads[1] && page.splitLead2) splitLeads[1].textContent = page.splitLead2;
      else if (splitLeads[0] && page.splitLeadHtml && !page.splitLead1) splitLeads[0].innerHTML = page.splitLeadHtml;
    }
    var list = $('#site-process-page-list');
    if (list && processSteps && processSteps.length) {
      var steps = processSteps.filter(function (s) {
        var c = (s.context || 'both').toLowerCase();
        return c === 'both' || c === 'page';
      });
      list.innerHTML = steps
        .map(function (s, idx, arr) {
          var milestone = idx < arr.length - 1;
          var liCls = milestone ? ' class="is-milestone"' : '';
          return (
            '<li' +
            liCls +
            '><span class="step-tag">' +
            esc(s.label) +
            '</span><h3>' +
            esc(s.title) +
            '</h3><p>' +
            esc(s.description) +
            '</p></li>'
          );
        })
        .join('');
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
    var fromQuery = url.match(/[?&]slug=([a-z0-9-]+)/i);
    if (fromQuery) slug = fromQuery[1];
    if (!slug && url) {
      slug = url.replace(/\?.*$/, '').replace(/\.html$/i, '').replace(/^.*\//, '');
    }
    slug = slug.replace(/[^a-z0-9-]/gi, '').toLowerCase();
    if (!slug || slug === 'journal') return 'journal.html';
    return 'journal-post.php?slug=' + encodeURIComponent(slug);
  }

  function journalSlugFromHref(href) {
    var h = String(href || '').trim();
    if (!h) return '';
    var q = h.match(/[?&]slug=([a-z0-9-]+)/i);
    if (q) return q[1];
    var m = h.match(/(?:^|\/)([a-z0-9-]+)\.html(?:\?|$)/i);
    if (m && m[1] && m[1] !== 'journal') return m[1];
    return '';
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

  function applyServicesCta(copy) {
    if (!copy) return;
    var eyebrow = $('.site-services-cta-eyebrow');
    var title = $('.site-services-cta-title');
    var sub = $('.site-services-cta-sub');
    var lead = $('.site-services-cta-lead');
    var btn = $('.site-services-cta-btn');
    var btn2 = $('.site-services-cta-btn-secondary');
    if (eyebrow && copy.services_cta_eyebrow) eyebrow.textContent = copy.services_cta_eyebrow;
    if (title && copy.services_cta_title) title.textContent = copy.services_cta_title;
    if (sub && copy.services_cta_sub) sub.textContent = copy.services_cta_sub;
    if (lead && copy.services_cta_lead) lead.textContent = copy.services_cta_lead;
    if (btn && copy.services_cta_btn_text) btn.textContent = copy.services_cta_btn_text;
    if (btn && copy.services_cta_btn_url) btn.setAttribute('href', copy.services_cta_btn_url);
    if (btn2 && copy.services_cta_btn2_text) {
      btn2.textContent = copy.services_cta_btn2_text;
      btn2.hidden = false;
    }
    if (btn2 && copy.services_cta_btn2_url) btn2.setAttribute('href', copy.services_cta_btn2_url);
  }

  function onData(e) {
    var data = e.detail || window.__SPANGLE_SITE__;
    if (!data || !data.pages) return;
    if (document.body.classList.contains('page-studio')) {
      applyStudio(data.pages.studio, data);
      document.dispatchEvent(new CustomEvent('spangle:content-updated'));
    }
    if (document.body.classList.contains('page-services')) {
      if (!document.getElementById('svc-detail-section')) {
        applyServices(data.pages.services);
      }
      applyServicesCta(data.copy);
    }
    if (document.body.classList.contains('page-work') && !document.getElementById('wrk-hero')) {
      applyPageHero(data.pages.work);
    }
    if (document.body.classList.contains('page-contact') && !document.getElementById('cnt-enquiry-form')) {
      applyContactExtras(data.pages.contact);
    }
    if (document.body.classList.contains('page-process') && !document.getElementById('proc-timeline')) {
      applyProcessPage(data.pages.process, data.processSteps);
    }
    if (document.body.classList.contains('page-journal') && !document.getElementById('journal-grid')) {
      applyPageHero(data.pages.journal);
      renderJournalList(data.journalPosts);
    }
  }

  document.addEventListener('spangle:site-data', onData);
  if (window.__SPANGLE_SITE__) onData({ detail: window.__SPANGLE_SITE__ });
}());
