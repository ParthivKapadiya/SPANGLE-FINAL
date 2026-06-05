/**
 * SPANGLE — UI/UX behaviours
 */
(function () {
  'use strict';

  function $(sel, root) {
    return (root || document).querySelector(sel);
  }

  function $$(sel, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(sel));
  }

  var header = $('#site-header');
  var headerH = 84;

  function readHeaderHeight() {
    if (!header) return;
    headerH = header.offsetHeight || 84;
    document.documentElement.style.setProperty('--header-h-live', headerH + 'px');
  }

  readHeaderHeight();
  window.addEventListener('resize', readHeaderHeight, { passive: true });

  /* Header solid/compact state is handled by site.js (is-top / is-solid) */

  /* —— Smooth anchor links (fixed header offset) —— */
  function scrollToTarget(el) {
    if (!el) return;
    var top = el.getBoundingClientRect().top + window.scrollY - headerH - 12;
    window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
  }

  document.addEventListener('click', function (e) {
    var link = e.target.closest('a[href^="#"]');
    if (!link) return;
    var hash = link.getAttribute('href');
    if (!hash || hash === '#') return;
    var target = document.querySelector(hash);
    if (!target) return;
    e.preventDefault();
    scrollToTarget(target);
    if (history.pushState) {
      history.pushState(null, '', hash);
    }
  });

  /* —— Back to top —— */
  var backBtn = document.createElement('button');
  backBtn.type = 'button';
  backBtn.className = 'back-to-top';
  backBtn.setAttribute('aria-label', 'Back to top');
  backBtn.innerHTML =
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">' +
    '<path d="M12 19V5M5 12l7-7 7 7"/></svg>';
  document.body.appendChild(backBtn);

  function onScrollBackTop() {
    var show = (window.scrollY || 0) > window.innerHeight * 0.65;
    backBtn.classList.toggle('is-visible', show);
  }

  window.addEventListener('scroll', onScrollBackTop, { passive: true });
  onScrollBackTop();

  backBtn.addEventListener('click', function () {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  /* —— Mobile sticky enquire bar (not on home — hero has primary CTA) —— */
  if (!document.body.classList.contains('page-contact') && !document.body.classList.contains('home')) {
    var bar = document.createElement('div');
    bar.className = 'mobile-enquire-bar';
    bar.setAttribute('role', 'navigation');
    bar.setAttribute('aria-label', 'Quick actions');
    bar.innerHTML =
      '<a href="tel:+916359351513"><i class="fas fa-phone" aria-hidden="true"></i> Call</a>' +
      '<a href="contact.html" class="mobile-enquire-primary">Enquire</a>';
    document.body.appendChild(bar);

  }

  /* —— Image load fade-in —— */
  function markLoaded(img) {
    img.classList.add('is-loaded');
  }

  $$('.brand-logo-full, .footer-logo, .hero-slide, img[loading="eager"]').forEach(markLoaded);

  $$('img[loading="lazy"]').forEach(function (img) {
    if (img.complete && img.naturalWidth > 0) {
      markLoaded(img);
    } else {
      img.addEventListener('load', function () {
        markLoaded(img);
      });
      img.addEventListener('error', function () {
        markLoaded(img);
      });
    }
  });

  document.addEventListener('spangle:work-archive-rendered', function () {
    $$('#work-archive img, .work-archive .work-card img').forEach(function (img) {
      markLoaded(img);
      img.loading = 'eager';
    });
  });

  document.addEventListener('spangle:content-updated', function () {
    $$('img[loading="lazy"]:not(.is-loaded)').forEach(function (img) {
      if (img.complete && img.naturalWidth > 0) {
        markLoaded(img);
        return;
      }
      img.addEventListener('load', function () {
        markLoaded(img);
      });
      img.addEventListener('error', function () {
        markLoaded(img);
      });
    });
  });

  /* —— Form UX: loading + validation —— */
  $$('.enquiry-form').forEach(function (form) {
    var submitBtn = form.querySelector('[type="submit"]');
    if (submitBtn && !submitBtn.dataset.uxLabel) {
      submitBtn.dataset.uxLabel = submitBtn.textContent.trim();
    }

    form.addEventListener('submit', function () {
      if (!form.checkValidity()) return;
      if (!submitBtn) return;
      submitBtn.disabled = true;
      submitBtn.classList.add('is-loading');
      submitBtn.textContent = 'Sending…';
    });

    $$('input, textarea', form).forEach(function (field) {
      field.addEventListener('blur', function () {
        if (field.value.trim()) field.reportValidity();
      });
    });
  });

  /* —— Enquiry banner styling from URL —— */
  var banner = $('#enquiry-status-banner');
  if (banner && !banner.hidden) {
    var code = '';
    try {
      code = new URLSearchParams(window.location.search).get('enquiry') || '';
    } catch (e) {
      code = '';
    }
    banner.classList.add(code === 'spam' || code ? 'is-error' : 'is-success');
    banner.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  /* —— Work filter handled by js/work-page.js after CMS render —— */

  /* —— Close mobile nav on resize to desktop —— */
  window.addEventListener('resize', function () {
    if (window.innerWidth > 900 && header && header.classList.contains('nav-open')) {
      var closeBtn = $('#nav-close');
      if (closeBtn) closeBtn.click();
    }
  });

  /* —— External links: subtle hint in title —— */
  $$('a[target="_blank"]').forEach(function (a) {
    if (!a.getAttribute('title') && !a.getAttribute('aria-label')) {
      a.setAttribute('title', 'Opens in a new tab');
    }
  });
})();
