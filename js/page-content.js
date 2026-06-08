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

  function setStudioText(sel, value) {
    var el = $(sel);
    if (el && value) el.textContent = value;
  }

  function applyStudio(page, data) {
    if (!page) return;
    data = data || window.__SPANGLE_SITE__ || {};
    applyPageHero(page);

    setStudioText('.site-studio-story-eyebrow', page.storyEyebrow);
    setStudioText('.site-studio-story-title', page.storyTitle);
    setStudioText('.site-studio-story-intro', page.storyIntro);
    var storyImg = document.getElementById('studio-story-image');
    if (storyImg) {
      var storySrc = page.storyImage || page.philosophyImage;
      if (storySrc) storyImg.setAttribute('src', mediaSrc(storySrc));
    }
    if (page.timeline && page.timeline.length) {
      page.timeline.forEach(function (item, i) {
        var n = i + 1;
        setStudioText('.site-studio-timeline-' + n + '-year', item.year);
        setStudioText('.site-studio-timeline-' + n + '-title', item.title);
        var textEl = $('.site-studio-timeline-' + n + '-text');
        if (textEl && item.text) textEl.textContent = item.text;
      });
    }

    var pe = $('.site-studio-philosophy-eyebrow');
    var pt = $('.site-studio-philosophy-title');
    var pi = $('.site-studio-philosophy-image');
    if (pe && page.philosophyEyebrow) pe.textContent = page.philosophyEyebrow;
    if (pt && page.philosophyTitle) {
      pt.textContent = page.philosophyTitle;
      resetMotionTitle(pt);
    }
    setStudioText('.site-studio-philosophy-lead-1', page.philosophyLead1);
    setStudioText('.site-studio-philosophy-lead-2', page.philosophyLead2);
    if (pi && page.philosophyImage) pi.setAttribute('src', mediaSrc(page.philosophyImage));

    if (page.pillars && page.pillars.length) {
      page.pillars.forEach(function (pillar, i) {
        var n = i + 1;
        setStudioText('.site-studio-pillar-' + n + '-title', pillar.title);
        setStudioText('.site-studio-pillar-' + n + '-text', pillar.text);
        var iconEl = $('.site-studio-pillar-' + n + '-icon');
        if (iconEl && pillar.icon) {
          iconEl.className = 'site-studio-pillar-' + n + '-icon ' + pillar.icon;
        }
      });
    }

    setStudioText('.site-studio-why-eyebrow', page.whyEyebrow);
    setStudioText('.site-studio-why-title', page.whyTitle);
    setStudioText('.site-studio-why-intro', page.whyIntro);

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

    setStudioText('.site-studio-process-eyebrow', page.processEyebrow);
    setStudioText('.site-studio-process-title', page.processTitle);
    setStudioText('.site-studio-process-intro', page.processIntro);

    setStudioText('.site-studio-culture-eyebrow', page.cultureEyebrow);
    setStudioText('.site-studio-culture-title', page.cultureTitle);
    setStudioText('.site-studio-culture-intro', page.cultureIntro);

    setStudioText('.site-studio-impact-eyebrow', page.impactEyebrow);
    setStudioText('.site-studio-impact-title', page.impactTitle);

    var stE = $('.site-studio-testimonials-eyebrow');
    var stT = $('.site-studio-testimonials-title');
    if (stE && page.testimonialsEyebrow) stE.textContent = page.testimonialsEyebrow;
    if (stT && page.testimonialsTitle) stT.textContent = page.testimonialsTitle;

    setStudioText('.site-studio-compare-eyebrow', page.compareEyebrow);
    setStudioText('.site-studio-compare-title', page.compareTitle);
    setStudioText('.site-studio-compare-us-title', page.compareUsTitle);
    setStudioText('.site-studio-compare-them-title', page.compareThemTitle);
    var compareUsList = $('.site-studio-compare-us-list');
    var compareThemList = $('.site-studio-compare-them-list');
    if (compareUsList && page.compareUsItems && page.compareUsItems.length) {
      compareUsList.innerHTML = page.compareUsItems
        .map(function (text) {
          return '<li><i class="fa-solid fa-check" aria-hidden="true"></i> ' + esc(text) + '</li>';
        })
        .join('');
    }
    if (compareThemList && page.compareThemItems && page.compareThemItems.length) {
      compareThemList.innerHTML = page.compareThemItems
        .map(function (text) {
          return '<li><i class="fa-solid fa-minus" aria-hidden="true"></i> ' + esc(text) + '</li>';
        })
        .join('');
    }

    setStudioText('.site-studio-cta-eyebrow', page.ctaEyebrow);
    setStudioText('.site-studio-cta-title', page.ctaTitle);
    setStudioText('.site-studio-cta-sub', page.ctaSub);
    var ctaBtn2 = $('.site-studio-cta-btn2');
    if (ctaBtn2 && page.ctaBtn2Text) ctaBtn2.textContent = page.ctaBtn2Text;
    if (ctaBtn2 && page.ctaBtn2Url) ctaBtn2.setAttribute('href', page.ctaBtn2Url);

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
    try {
      var email = new URLSearchParams(window.location.search).get('email');
      if (email) {
        var emailInput = document.querySelector('.page-contact .enquiry-form [name="email"]');
        if (emailInput) emailInput.value = email;
      }
    } catch (err) {
      /* ignore */
    }
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

  function onData(e) {
    var data = e.detail || window.__SPANGLE_SITE__;
    if (!data || !data.pages) return;
    if (document.body.classList.contains('page-studio')) {
      applyStudio(data.pages.studio, data);
      document.dispatchEvent(new CustomEvent('spangle:content-updated'));
    }
    if (document.body.classList.contains('page-work') && !document.getElementById('wrk-hero')) {
      applyPageHero(data.pages.work);
    }
    if (document.body.classList.contains('page-contact')) {
      applyContactExtras(data.pages.contact);
    }
    if (document.body.classList.contains('page-process') && !document.getElementById('proc-timeline')) {
      applyProcessPage(data.pages.process, data.processSteps);
    }
  }

  document.addEventListener('spangle:site-data', onData);
  if (window.__SPANGLE_SITE__) onData({ detail: window.__SPANGLE_SITE__ });
}());
