(function () {
  'use strict';

  if (!document.body.classList.contains('page-journal')) return;
  if (!document.getElementById('journal-grid')) return;

  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  var CATEGORIES = [
    'Architecture',
    'Interiors',
    'Construction',
    'Materials',
    'Sustainability',
    'Design Thinking',
    'Lifestyle',
  ];

  var SLUG_CATEGORY = {
    sustainable: 'Sustainability',
    'journal-sustainable': 'Sustainability',
    'quiet-luxury': 'Interiors',
    'journal-quiet-luxury': 'Interiors',
    workplaces: 'Lifestyle',
    'journal-workplaces': 'Lifestyle',
    materiality: 'Materials',
    'journal-materiality': 'Materials',
  };

  var TREND_TOPICS = [
    { icon: 'fa-solid fa-city', title: 'Future of Architecture', text: 'Climate-responsive envelopes, adaptive reuse, and typologies shaped for Indian cities.' },
    { icon: 'fa-solid fa-gem', title: 'Luxury Interiors', text: 'Quiet luxury, bespoke joinery, and material palettes that age with grace.' },
    { icon: 'fa-solid fa-leaf', title: 'Sustainable Construction', text: 'Passive cooling, honest materials, and envelopes designed for decades.' },
    { icon: 'fa-solid fa-house-signal', title: 'Smart Homes', text: 'Lighting, climate, and security integrated without compromising architecture.' },
    { icon: 'fa-solid fa-landmark', title: 'Indian Design Trends', text: 'Courtyards, local stone, and craft traditions reinterpreted for modern living.' },
  ];

  var INSIGHT_TOPICS = [
    { icon: 'fa-solid fa-compass-drafting', title: 'Planning Mistakes', text: 'Circulation, light, and zoning errors we correct early in every brief.' },
    { icon: 'fa-solid fa-layer-group', title: 'Material Selection', text: 'How limestone, teak, and plaster respond to harsh sun and monsoon.' },
    { icon: 'fa-solid fa-calculator', title: 'Budget Planning', text: 'Phased estimates, contingency bands, and where to invest for lasting value.' },
    { icon: 'fa-solid fa-helmet-safety', title: 'Construction Tips', text: 'Site coordination, quality checks, and contractor alignment from the studio.' },
    { icon: 'fa-solid fa-couch', title: 'Interior Strategies', text: 'Editing a room to feel expensive without shouting — restraint, texture, light.' },
  ];

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
        src.indexOf('journal-premium.js') !== -1
      ) {
        try {
          return new URL(src, window.location.href).href.replace(
            /\/(js\/content-bridge\.js|js\/page-content\.js|js\/journal-premium\.js|api\/site-data\.js\.php)(\?.*)?$/i,
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

  function normalizeSlug(slug) {
    slug = String(slug || '').toLowerCase().trim();
    if (slug.indexOf('journal-') === 0) slug = slug.slice(8);
    return slug;
  }

  function postCategory(post) {
    var cat = String((post && post.category) || '').trim();
    if (cat) return cat;
    var slug = String((post && post.slug) || '').toLowerCase();
    if (SLUG_CATEGORY[slug]) return SLUG_CATEGORY[slug];
    var short = normalizeSlug(slug);
    return SLUG_CATEGORY[short] || 'Architecture';
  }

  function readMinutes(post) {
    if (post && post.readMinutes) return post.readMinutes;
    var text = String((post && (post.excerpt || post.bodyHtml)) || '');
    var words = text.split(/\s+/).filter(Boolean).length;
    return Math.max(4, Math.round(words / 200) || 5);
  }

  function metaLine(post, author) {
    var parts = [postCategory(post)];
    parts.push(readMinutes(post) + ' min read');
    if (author) parts.push(author);
    return parts.join(' · ');
  }

  function applyHero(page, posts, stats, copy) {
    page = page || {};
    copy = copy || {};
    var kicker = $('.jnl-hero__kicker.site-page-kicker');
    var title = $('.jnl-hero__title.site-page-hero-title');
    var lead = $('.jnl-hero__lead.site-page-hero-lead');
    var media = $('.jnl-hero__media');
    if (kicker && page.kicker) kicker.textContent = page.kicker;
    if (title && page.title) title.textContent = page.title;
    if (lead && page.lead) lead.textContent = page.lead;
    var heroImg = page.heroImage || (posts[0] && posts[0].image) || '';
    if (media && heroImg) {
      media.style.backgroundImage = "url('" + mediaSrc(heroImg).replace(/'/g, '%27') + "')";
    }
    var statsEl = $('#jnl-hero-stats');
    if (!statsEl) return;
    var articleCount = posts.length;
    var years = '15+';
    var projects = '120+';
    var readers = copy.journal_stat_readers || '12K+';
    if (stats && stats.length) {
      stats.forEach(function (s) {
        var lbl = String(s.label || '').toLowerCase();
        if (lbl.indexOf('year') !== -1) years = s.value;
        if (lbl.indexOf('project') !== -1) projects = s.value;
      });
    }
    statsEl.innerHTML =
      '<div class="jnl-hero-stat jnl-reveal"><strong>' +
      esc(String(articleCount)) +
      '</strong><span>Published insights</span></div>' +
      '<div class="jnl-hero-stat jnl-reveal"><strong>' +
      esc(years) +
      '</strong><span>Years of experience</span></div>' +
      '<div class="jnl-hero-stat jnl-reveal"><strong>' +
      esc(projects) +
      '</strong><span>Projects completed</span></div>' +
      '<div class="jnl-hero-stat jnl-reveal"><strong>' +
      esc(readers) +
      '</strong><span>Monthly readers</span></div>';
  }

  function renderFeatured(post, team) {
    var el = $('#journal-featured');
    if (!el || !post) return;
    var author = team[0] ? team[0].name : 'Editorial team';
    var url = esc(journalArticleHref(post));
    var img = esc(mediaSrc(post.image));
    el.innerHTML =
      '<div class="jnl-feature__visual">' +
      '<span class="jnl-feature__badge">Cover story</span>' +
      (img ? '<img src="' + img + '" alt="' + esc(post.title) + '" loading="lazy" width="1200" height="800" decoding="async" />' : '') +
      '</div>' +
      '<div class="jnl-feature__copy">' +
      '<p class="jnl-feature__meta">' +
      esc(metaLine(post, author)) +
      '</p>' +
      '<h2 class="jnl-feature__title"><a href="' + url + '">' + esc(post.title) + '</a></h2>' +
      '<p class="jnl-feature__excerpt">' + esc(post.excerpt || '') + '</p>' +
      '<a href="' + url + '" class="btn btn-primary">Read cover story</a>' +
      '</div>';
    bindLazyImages(el);
  }

  function renderCardGrid(posts, containerId, team) {
    var el = $(containerId);
    if (!el || !posts.length) return;
    var author = team[0] ? team[0].name : 'Studio';
    el.innerHTML = posts
      .map(function (post) {
        var url = esc(journalArticleHref(post));
        var img = esc(mediaSrc(post.image));
        return (
          '<article class="jnl-card" data-category="' +
          esc(postCategory(post)) +
          '">' +
          '<a href="' +
          url +
          '" class="jnl-card__img">' +
          (img ? '<img src="' + img + '" alt="' + esc(post.title) + '" loading="lazy" width="800" height="500" decoding="async" />' : '') +
          '</a>' +
          '<div class="jnl-card__body">' +
          '<p class="jnl-card__cat">' +
          esc(postCategory(post)) +
          '</p>' +
          '<h3 class="jnl-card__title"><a href="' +
          url +
          '">' +
          esc(post.title) +
          '</a></h3>' +
          '<p class="jnl-card__excerpt">' +
          esc(post.excerpt || '') +
          '</p>' +
          '<div class="jnl-card__foot"><span>' +
          esc(author) +
          '</span><a class="jnl-card__read" href="' +
          url +
          '">Read →</a></div>' +
          '</div></article>'
        );
      })
      .join('');
    bindLazyImages(el);
  }

  function renderMagazineGrid(posts, team) {
    var el = $('#journal-grid');
    if (!el || !posts.length) return;
    var author = team[0] ? team[0].name : 'Studio';
    el.innerHTML = posts
      .map(function (post) {
        var url = esc(journalArticleHref(post));
        var img = esc(mediaSrc(post.image));
        var cat = esc(postCategory(post));
        return (
          '<article class="jnl-mag-card" data-category="' +
          cat +
          '" data-title="' +
          esc(post.title) +
          '">' +
          '<a href="' +
          url +
          '" class="jnl-mag-card__img">' +
          (img ? '<img src="' + img + '" alt="' + esc(post.title) + '" loading="lazy" width="800" height="500" decoding="async" />' : '') +
          '</a>' +
          '<div class="jnl-mag-card__body">' +
          '<p class="jnl-card__cat">' +
          cat +
          '</p>' +
          '<h3 class="jnl-card__title"><a href="' +
          url +
          '">' +
          esc(post.title) +
          '</a></h3>' +
          '<p class="jnl-card__excerpt">' +
          esc(post.excerpt || '') +
          '</p>' +
          '<div class="jnl-card__foot"><span>' +
          esc(author) +
          ' · ' +
          readMinutes(post) +
          ' min</span><a class="jnl-card__read" href="' +
          url +
          '">Read</a></div>' +
          '</div></article>'
        );
      })
      .join('');
    bindLazyImages(el);
  }

  function renderCategories(posts) {
    var el = $('#journal-categories');
    var section = el ? el.closest('section') : null;
    if (!el) return;
    var counts = {};
    posts.forEach(function (p) {
      var c = postCategory(p);
      counts[c] = (counts[c] || 0) + 1;
    });
    var activeCats = Object.keys(counts).filter(function (c) {
      return counts[c] > 0;
    });
    if (activeCats.length < 2) {
      if (section) section.hidden = true;
      return;
    }
    if (section) section.hidden = false;
    activeCats.sort(function (a, b) {
      return (counts[b] || 0) - (counts[a] || 0);
    });
    el.innerHTML =
      '<button type="button" class="jnl-cat-btn is-active" data-filter="all">All articles <span>(' +
      posts.length +
      ')</span></button>' +
      activeCats
        .map(function (c) {
          return (
            '<button type="button" class="jnl-cat-btn" data-filter="' +
            esc(c) +
            '">' +
            esc(c) +
            ' <span>(' +
            counts[c] +
            ')</span></button>'
          );
        })
        .join('');
    el.querySelectorAll('.jnl-cat-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        el.querySelectorAll('.jnl-cat-btn').forEach(function (b) {
          b.classList.remove('is-active');
        });
        btn.classList.add('is-active');
        filterArticles(btn.getAttribute('data-filter') || 'all', $('#journal-search') ? $('#journal-search').value : '');
      });
    });
  }

  function filterArticles(category, query) {
    query = String(query || '').toLowerCase().trim();
    var visible = 0;
    $$('#journal-grid .jnl-mag-card').forEach(function (card) {
      var cat = card.getAttribute('data-category') || '';
      var title = (card.getAttribute('data-title') || '').toLowerCase();
      var catOk = category === 'all' || cat === category;
      var qOk = !query || title.indexOf(query) !== -1;
      var show = catOk && qOk;
      card.classList.toggle('is-hidden', !show);
      if (show) visible++;
    });
    var empty = $('#journal-grid-empty');
    if (empty) empty.hidden = visible > 0;
  }

  function renderTopics(containerId, topics) {
    var el = $(containerId);
    if (!el) return;
    el.innerHTML = topics
      .map(function (t) {
        return (
          '<div class="jnl-topic jnl-reveal">' +
          '<i class="' +
          esc(t.icon) +
          '" aria-hidden="true"></i>' +
          '<h3>' +
          esc(t.title) +
          '</h3>' +
          '<p>' +
          esc(t.text) +
          '</p></div>'
        );
      })
      .join('');
  }

  function renderProjectStory(post, project) {
    var el = $('#journal-project-story');
    if (!el) return;
    post = post || {};
    project = project || {};
    var url = esc(journalArticleHref(post));
    var img = esc(mediaSrc(project.heroImage || post.image));
    var title = project.title || post.title || 'Project story';
    var summary = project.summary || post.excerpt || '';
    el.innerHTML =
      '<div class="jnl-project-story__img">' +
      (img ? '<img src="' + img + '" alt="' + esc(title) + '" loading="lazy" width="800" height="600" decoding="async" />' : '') +
      '</div>' +
      '<div>' +
      '<p class="section-eyebrow">From the field</p>' +
      '<h2 class="section-title">' +
      esc(title) +
      '</h2>' +
      '<p class="section-lead">' +
      esc(summary) +
      '</p>' +
      '<ul class="jnl-story-points">' +
      '<li><strong>Challenge</strong> — Brief, site constraints, and programme requirements aligned with local context.</li>' +
      '<li><strong>Design approach</strong> — Spatial strategy, material palette, and coordination with services.</li>' +
      '<li><strong>Execution</strong> — On-site quality reviews, vendor alignment, and milestone sign-offs.</li>' +
      '<li><strong>Outcome</strong> — A space that performs for daily life and reads as intentional architecture.</li>' +
      '</ul>' +
      '<a href="' +
      url +
      '" class="btn btn-primary">Read the full story</a>' +
      '</div>';
    bindLazyImages(el);
  }

  function renderAuthors(team) {
    var el = $('#journal-authors');
    if (!el || !team.length) return;
    el.innerHTML = team
      .map(function (m) {
        var img = m.image
          ? '<img src="' + esc(mediaSrc(m.image)) + '" alt="' + esc(m.name) + '" loading="lazy" width="120" height="120" decoding="async" />'
          : '<div class="jnl-author__initials" aria-hidden="true">' + esc(m.initials || m.name.charAt(0)) + '</div>';
        return (
          '<div class="jnl-author jnl-reveal">' +
          img +
          '<h3>' +
          esc(m.name) +
          '</h3>' +
          '<p class="role">' +
          esc(m.role || '') +
          '</p>' +
          '<p>' +
          esc(m.bio || '') +
          '</p></div>'
        );
      })
      .join('');
    bindLazyImages(el);
  }

  function renderPopular(posts) {
    var el = $('#journal-popular');
    if (!el || !posts.length) return;
    var ranked = posts.slice().reverse();
    el.innerHTML = ranked
      .map(function (post) {
        var url = esc(journalArticleHref(post));
        return (
          '<div class="jnl-popular-item jnl-reveal">' +
          '<div><h3><a class="title-link" href="' +
          url +
          '">' +
          esc(post.title) +
          '</a></h3>' +
          '<p class="jnl-card__cat">' +
          esc(metaLine(post)) +
          '</p></div>' +
          '<a href="' +
          url +
          '" class="jnl-card__read">Read</a></div>'
        );
      })
      .join('');
  }

  function renderFaq(page) {
    var el = $('#journal-faq');
    if (!el) return;
    var items = (page && page.faq && page.faq.items) || [];
    if (!items.length) {
      el.closest('section').hidden = true;
      return;
    }
    el.innerHTML = items
      .map(function (item, i) {
        return (
          '<div class="jnl-faq-item jnl-reveal">' +
          '<button type="button" class="jnl-faq-q" aria-expanded="false" aria-controls="jnl-faq-a-' +
          i +
          '">' +
          esc(item.q) +
          '<span aria-hidden="true">+</span></button>' +
          '<div class="jnl-faq-a" id="jnl-faq-a-' +
          i +
          '">' +
          esc(item.a) +
          '</div></div>'
        );
      })
      .join('');
    el.querySelectorAll('.jnl-faq-q').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var item = btn.closest('.jnl-faq-item');
        var open = item.classList.contains('is-open');
        el.querySelectorAll('.jnl-faq-item').forEach(function (node) {
          node.classList.remove('is-open');
          node.querySelector('.jnl-faq-q').setAttribute('aria-expanded', 'false');
        });
        if (!open) {
          item.classList.add('is-open');
          btn.setAttribute('aria-expanded', 'true');
        }
      });
    });
  }

  function renderProof(posts, copy) {
    var el = $('#journal-proof');
    if (!el) return;
    var readers = copy.journal_stat_readers || '12K+';
    el.innerHTML =
      '<div class="jnl-proof-stat jnl-reveal"><strong data-count="' +
      esc(String(posts.length)) +
      '">' +
      esc(String(posts.length)) +
      '</strong><span>Published insights</span></div>' +
      '<div class="jnl-proof-stat jnl-reveal"><strong>' +
      esc(readers) +
      '</strong><span>Monthly readers</span></div>' +
      '<div class="jnl-proof-stat jnl-reveal"><strong data-count="48">48</strong><span>Project case notes</span></div>' +
      '<div class="jnl-proof-stat jnl-reveal"><strong>4.9</strong><span>Reader satisfaction</span></div>';
  }

  function applyNewsletter(copy, page) {
    copy = copy || {};
    page = page || {};
    var title = $('.jnl-newsletter__title');
    var lead = $('.jnl-newsletter__lead');
    if (title && (copy.journal_newsletter_title || page.newsletterTitle)) {
      title.textContent = copy.journal_newsletter_title || page.newsletterTitle;
    }
    if (lead && (copy.journal_newsletter_lead || page.newsletterLead)) {
      lead.textContent = copy.journal_newsletter_lead || page.newsletterLead;
    }
    var form = $('#journal-newsletter-form');
    if (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var email = (form.querySelector('input[type="email"]') || {}).value || '';
        window.location.href = 'contact.html' + (email ? '?email=' + encodeURIComponent(email) : '');
      });
    }
  }

  function applyCta(copy, page) {
    copy = copy || {};
    page = page || {};
    var eyebrow = $('.site-journal-cta-eyebrow');
    var title = $('.jnl-cta__title.site-journal-cta-title');
    var sub = $('.jnl-cta__sub');
    var lead = $('.jnl-cta__lead.site-journal-cta-text');
    var btn = $('.site-journal-cta-btn');
    var btn2 = $('.site-journal-cta-btn-secondary');
    if (eyebrow && copy.journal_cta_eyebrow) eyebrow.textContent = copy.journal_cta_eyebrow;
    if (title && copy.journal_cta_title) title.textContent = copy.journal_cta_title;
    if (sub && copy.journal_cta_sub) sub.textContent = copy.journal_cta_sub;
    if (lead && copy.journal_cta_text) lead.textContent = copy.journal_cta_text;
    if (btn && copy.journal_cta_btn_text) btn.textContent = copy.journal_cta_btn_text;
    if (btn && copy.journal_cta_btn_url) btn.setAttribute('href', copy.journal_cta_btn_url);
    if (btn2 && (copy.journal_cta_btn2_text || (page.ctaSecondary && page.ctaSecondary.text))) {
      btn2.textContent = copy.journal_cta_btn2_text || page.ctaSecondary.text;
      btn2.setAttribute('href', copy.journal_cta_btn2_url || (page.ctaSecondary && page.ctaSecondary.url) || 'contact.html');
      btn2.hidden = false;
    }
  }

  function injectSchema(data, posts, team) {
    var site = (data && data.siteName) || 'Archevo Design';
    var base = appBase();
    var items = posts.map(function (p, i) {
      return {
        '@type': 'ListItem',
        position: i + 1,
        url: base + '/' + journalArticleHref(p),
        name: p.title,
      };
    });
    var authors = team.map(function (m) {
      return {
        '@type': 'Person',
        name: m.name,
        jobTitle: m.role || '',
        description: m.bio || '',
      };
    });
    var faq = (data.pages && data.pages.journal && data.pages.journal.faq) || {};
    var faqItems = (faq.items || []).map(function (item) {
      return {
        '@type': 'Question',
        name: item.q,
        acceptedAnswer: { '@type': 'Answer', text: item.a },
      };
    });
    var graphs = [
      {
        '@context': 'https://schema.org',
        '@type': 'Blog',
        name: site + ' Journal',
        description: (data.pages && data.pages.journal && data.pages.journal.lead) || '',
        url: base + '/journal.html',
        publisher: { '@type': 'Organization', name: site },
        blogPost: posts.map(function (p) {
          return {
            '@type': 'BlogPosting',
            headline: p.title,
            description: p.excerpt || '',
            image: mediaSrc(p.image),
            url: base + '/' + journalArticleHref(p),
            author: { '@type': 'Organization', name: site },
          };
        }),
      },
      {
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        itemListElement: [
          { '@type': 'ListItem', position: 1, name: 'Home', item: base + '/index.html' },
          { '@type': 'ListItem', position: 2, name: 'Journal', item: base + '/journal.html' },
        ],
      },
      {
        '@context': 'https://schema.org',
        '@type': 'ItemList',
        name: 'Latest articles',
        itemListElement: items,
      },
    ];
    if (authors.length) {
      graphs.push({
        '@context': 'https://schema.org',
        '@type': 'ItemList',
        name: 'Editorial contributors',
        itemListElement: authors,
      });
    }
    if (faqItems.length) {
      graphs.push({
        '@context': 'https://schema.org',
        '@type': 'FAQPage',
        mainEntity: faqItems,
      });
    }
    var script = document.createElement('script');
    script.type = 'application/ld+json';
    script.id = 'journal-schema';
    script.textContent = JSON.stringify(graphs.length === 1 ? graphs[0] : { '@context': 'https://schema.org', '@graph': graphs });
    var old = document.getElementById('journal-schema');
    if (old) old.remove();
    document.head.appendChild(script);
  }

  function updateOg(data, page) {
    page = page || {};
    var descMeta = document.querySelector('meta[name="description"]');
    if (descMeta && page.lead) descMeta.setAttribute('content', page.lead);
    var ogTitle = document.querySelector('meta[property="og:title"]');
    var ogDesc = document.querySelector('meta[property="og:description"]');
    var ogImg = document.querySelector('meta[property="og:image"]');
    if (ogTitle && page.title) ogTitle.setAttribute('content', page.title + ' | ' + ((data && data.siteName) || 'Archevo Design'));
    if (ogDesc && page.lead) ogDesc.setAttribute('content', page.lead);
    if (ogImg && page.heroImage) ogImg.setAttribute('content', mediaSrc(page.heroImage));
  }

  function animateCounters() {
    $$('[data-count]').forEach(function (el) {
      var raw = el.getAttribute('data-count') || el.textContent || '';
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
    $$('.jnl-reveal').forEach(function (el) {
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
      $$('.jnl-reveal').forEach(function (el) {
        el.classList.add('is-in');
      });
      return;
    }
    if (window.ScrollTrigger) gsap.registerPlugin(ScrollTrigger);
    gsap.utils.toArray('.jnl-reveal:not(.is-in)').forEach(function (el) {
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
    var heroMedia = $('.jnl-hero__media');
    if (heroMedia && window.ScrollTrigger) {
      gsap.to(heroMedia, {
        scale: 1.12,
        ease: 'none',
        scrollTrigger: { trigger: '.jnl-hero', start: 'top top', end: 'bottom top', scrub: true },
      });
    }
  }

  function hydrate(data) {
    data = data || window.__SPANGLE_SITE__ || {};
    var posts = data.journalPosts || [];
    var page = data.pages && data.pages.journal;
    var team = data.team || [];
    var stats = (data.home && data.home.stats) || data.stats || [];
    var projects = data.projects || [];
    var copy = data.copy || {};

    applyHero(page, posts, stats, copy);
    if (posts.length) {
      renderFeatured(posts[0], team);
      renderCardGrid(posts.slice(1, 4), '#journal-picks', team);
      renderMagazineGrid(posts, team);
      renderPopular(posts);
      renderProjectStory(posts[0], projects[0]);
    }
    renderCategories(posts);
    renderTopics('#journal-trends', TREND_TOPICS);
    renderTopics('#journal-insights', INSIGHT_TOPICS);
    renderAuthors(team);
    renderFaq(page);
    renderProof(posts, copy);
    applyNewsletter(copy, page);
    applyCta(copy, page);
    injectSchema(data, posts, team);
    updateOg(data, page);

    var search = $('#journal-search');
    if (search) {
      search.addEventListener('input', function () {
        var active = $('.jnl-cat-btn.is-active');
        filterArticles(active ? active.getAttribute('data-filter') || 'all' : 'all', search.value);
      });
    }

    initReveal();
    animateCounters();
    bindLazyImages(document);
    document.dispatchEvent(new CustomEvent('spangle:content-updated'));
  }

  function onData(e) {
    hydrate(e.detail || e);
  }

  document.addEventListener('spangle:site-data', onData);
  if (window.__SPANGLE_SITE__) hydrate(window.__SPANGLE_SITE__);

  loadScript('https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js')
    .then(function () {
      return loadScript('https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js');
    })
    .then(initGsap)
    .catch(function () {
      initReveal();
    });
}());
