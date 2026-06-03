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

  function applyStudio(page) {
    if (!page) return;
    var k = $('.site-page-kicker');
    var t = $('.site-page-hero-title');
    var l = $('.site-page-hero-lead');
    if (k && page.kicker) k.textContent = page.kicker;
    if (t && page.title) t.textContent = page.title;
    if (l && page.lead) l.textContent = page.lead;
    var hero = $('.site-page-hero');
    if (hero && page.heroImage) hero.style.backgroundImage = "url('" + page.heroImage.replace(/'/g, '%27') + "')";
    var pe = $('.site-studio-philosophy-eyebrow');
    var pt = $('.site-studio-philosophy-title');
    var p1 = $('.site-studio-philosophy-lead-1');
    var p2 = $('.site-studio-philosophy-lead-2');
    var pi = $('.site-studio-philosophy-image');
    if (pe && page.philosophyEyebrow) pe.textContent = page.philosophyEyebrow;
    if (pt && page.philosophyTitle) pt.textContent = page.philosophyTitle;
    if (p1 && page.philosophyLead1) p1.textContent = page.philosophyLead1;
    if (p2 && page.philosophyLead2) p2.textContent = page.philosophyLead2;
    if (pi && page.philosophyImage) pi.setAttribute('src', page.philosophyImage);
  }

  function applyServices(page) {
    if (!page) return;
    var k = $('.site-page-kicker');
    var t = $('.site-page-hero-title');
    var l = $('.site-page-hero-lead');
    if (k && page.kicker) k.textContent = page.kicker;
    if (t && page.title) t.textContent = page.title;
    if (l && page.lead) l.textContent = page.lead;
    var hero = $('.site-page-hero');
    if (hero && page.heroImage) hero.style.backgroundImage = "url('" + page.heroImage.replace(/'/g, '%27') + "')";
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
          (s.image ? '<img src="' + esc(s.image) + '" alt="" loading="lazy" width="800" height="600" decoding="async" />' : '') +
          '</div></article>'
        );
      })
      .join('');
  }

  function onData(e) {
    var data = e.detail || window.__SPANGLE_SITE__;
    if (!data || !data.pages) return;
    if (document.body.classList.contains('page-studio')) applyStudio(data.pages.studio);
    if (document.body.classList.contains('page-services')) applyServices(data.pages.services);
  }

  document.addEventListener('spangle:site-data', onData);
  if (window.__SPANGLE_SITE__) onData({ detail: window.__SPANGLE_SITE__ });
}());
