(function () {
  'use strict';

  function $(sel, root) {
    return (root || document).querySelector(sel);
  }
  function $$(sel, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(sel));
  }

  var header = $('#site-header');
  var navToggle = $('#nav-toggle');
  var navDrawer = $('#nav-drawer');
  var navClose = $('#nav-close');
  var heroSlider = $('#hero-slider');
  var slides = heroSlider ? $$('.hero-slide', heroSlider) : [];
  var prevBtn = $('#hero-prev');
  var nextBtn = $('#hero-next');
  var dotsWrap = $('#hero-dots');
  var sliderLive = $('#slider-live');
  var rootEl = document.documentElement;

  function isLocalHost() {
    var h = (window.location && window.location.hostname) || '';
    return h === 'localhost' || h === '127.0.0.1';
  }

  function resolveSitePublicBase() {
    if (isLocalHost()) {
      var fromAttr = (rootEl.getAttribute('data-public-base') || '').trim().replace(/\/$/, '');
      if (fromAttr && !/spangle\.studio/i.test(fromAttr)) {
        return fromAttr;
      }
      var scripts = document.getElementsByTagName('script');
      var i;
      var src;
      for (i = scripts.length - 1; i >= 0; i--) {
        src = scripts[i].getAttribute('src') || '';
        if (src.indexOf('content-bridge') !== -1 || src.indexOf('site-data.js') !== -1) {
          try {
            return new URL(src, window.location.href).href.replace(
              /\/(js\/content-bridge\.js|api\/site-data\.js\.php)(\?.*)?$/i,
              ''
            );
          } catch (e) {
            break;
          }
        }
      }
      return (
        window.location.origin +
        window.location.pathname.replace(/\/[^/]*$/, '')
      ).replace(/\/$/, '');
    }

    var htmlBase = (rootEl.getAttribute('data-public-base') || '').trim().replace(/\/$/, '');
    if (htmlBase) {
      return htmlBase;
    }
    if (typeof window !== 'undefined' && window.location) {
      var locOrigin = window.location.origin;
      if (
        locOrigin
        && typeof locOrigin === 'string'
        && locOrigin !== 'null'
        && !/^file:/i.test(locOrigin)
        && /^https?:\/\//i.test(locOrigin)
      ) {
        return locOrigin.replace(/\/$/, '');
      }
    }

    return 'https://www.archevoinfra.com';
  }

  var SITE_PUBLIC_BASE = resolveSitePublicBase();

  var heroIndex = Math.max(0, slides.findIndex(function (s) {
    return s.classList.contains('active');
  }));
  var heroTimer = null;
  var heroUserPaused = false;
  var heroInterval = 8000;

  var testSlider = $('.testimonials-marquee');
  var testTrack = testSlider ? $('.testimonials-track', testSlider) : null;
  var testCards = testTrack ? $$('.quote-card', testTrack) : [];
  var testTimer = null;
  var testUserPaused = false;
  var testIndex = 0;

  function isSubPage() {
    return document.body.classList.contains('page-sub');
  }

  function setHeaderState() {
    if (!header) return;
    if (isSubPage()) {
      header.classList.remove('is-top');
      header.classList.add('is-solid');
      return;
    }
    /* Switch to solid bar soon after scroll (not after the full hero) */
    var threshold = 72;
    if (window.scrollY < threshold) {
      header.classList.add('is-top');
      header.classList.remove('is-solid');
    } else {
      header.classList.remove('is-top');
      header.classList.add('is-solid');
    }
  }

  window.addEventListener('scroll', setHeaderState, { passive: true });
  window.addEventListener('resize', setHeaderState, { passive: true });
  setHeaderState();

  function openNav() {
    if (!header || !navToggle || !navDrawer) return;
    header.classList.add('nav-open');
    navToggle.setAttribute('aria-expanded', 'true');
    navDrawer.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    var first = navDrawer.querySelector('a');
    if (first) first.focus();
    trapFocus(navDrawer);
  }

  function closeNav() {
    if (!header || !navToggle || !navDrawer) return;
    header.classList.remove('nav-open');
    navToggle.setAttribute('aria-expanded', 'false');
    navDrawer.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    releaseFocusTrap();
    navToggle.focus();
  }

  function toggleNav() {
    if (header && header.classList.contains('nav-open')) closeNav();
    else openNav();
  }

  if (navToggle) {
    navToggle.addEventListener('click', toggleNav);
    navToggle.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        toggleNav();
      }
    });
  }
  if (navClose) navClose.addEventListener('click', closeNav);
  if (navDrawer) {
    navDrawer.addEventListener('click', function (e) {
      if (e.target === navDrawer) closeNav();
    });
    $$('a', navDrawer).forEach(function (a) {
      a.addEventListener('click', closeNav);
    });
  }

  var trapHandler = null;
  var trapContainer = null;

  function trapFocus(container) {
    releaseFocusTrap();
    trapContainer = container;
    var focusable = $$('a, button, input, textarea, select, [tabindex]:not([tabindex="-1"])', container)
      .filter(function (el) {
        return !el.disabled && el.offsetParent !== null;
      });
    if (!focusable.length) return;
    var first = focusable[0];
    var last = focusable[focusable.length - 1];
    trapHandler = function (e) {
      if (e.key !== 'Tab') return;
      if (focusable.length === 1) {
        e.preventDefault();
        first.focus();
        return;
      }
      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    };
    document.addEventListener('keydown', trapHandler);
  }

  function releaseFocusTrap() {
    if (trapHandler) document.removeEventListener('keydown', trapHandler);
    trapHandler = null;
    trapContainer = null;
  }

  function announceSlide(i) {
    if (!sliderLive) return;
    var slide = slides[i];
    var label = 'Slide ' + (i + 1);
    if (slide) {
      if (slide.tagName === 'IMG' && slide.getAttribute('alt')) {
        label = slide.getAttribute('alt');
      } else if (slide.querySelector && slide.querySelector('img[alt]')) {
        label = slide.querySelector('img').getAttribute('alt') || label;
      }
    }
    sliderLive.textContent = 'Image ' + (i + 1) + ' of ' + slides.length + ': ' + label;
  }

  function showHeroSlide(index) {
    if (!slides.length) return;
    slides.forEach(function (s, i) {
      s.classList.toggle('active', i === index);
      s.setAttribute('aria-hidden', i === index ? 'false' : 'true');
    });
    if (dotsWrap) {
      $$('.hero-dot', dotsWrap).forEach(function (d, i) {
        d.setAttribute('aria-selected', i === index ? 'true' : 'false');
      });
    }
    heroIndex = index;
    announceSlide(index);
  }

  function nextHero() {
    showHeroSlide((heroIndex + 1) % slides.length);
  }

  function prevHero() {
    showHeroSlide((heroIndex - 1 + slides.length) % slides.length);
  }

  function startHeroAutoplay() {
    clearInterval(heroTimer);
    if (heroUserPaused) return;
    heroTimer = setInterval(nextHero, heroInterval);
  }

  function pauseHeroAutoplay(userPause) {
    clearInterval(heroTimer);
    heroTimer = null;
    if (userPause) heroUserPaused = true;
  }

  function attachHeroSlideErrors() {
    slides.forEach(function (slide) {
      if (!slide || slide.tagName !== 'IMG') return;
      slide.addEventListener('error', function onHeroImgErr() {
        var fb = slide.getAttribute('data-hero-fallback');
        var cur = slide.getAttribute('src') || '';
        if (fb && cur !== fb) {
          slide.src = fb;
          return;
        }
        var nxt = nextPoolImage(cur);
        if (nxt && imgBasename(cur) !== imgBasename(nxt)) {
          slide.src = nxt;
          return;
        }
        slide.removeEventListener('error', onHeroImgErr);
        slide.src = HERO_IMG_FALLBACK;
      });
    });
  }

  function refreshHeroSlider() {
    if (!heroSlider) return;
    slides = $$('.hero-slide', heroSlider);
    if (dotsWrap) dotsWrap.innerHTML = '';
    if (dotsWrap && slides.length) {
      slides.forEach(function (_, i) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'hero-dot';
        b.setAttribute('role', 'tab');
        b.setAttribute('aria-label', 'Show slide ' + (i + 1));
        b.addEventListener('click', function () {
          showHeroSlide(i);
          pauseHeroAutoplay(true);
        });
        dotsWrap.appendChild(b);
      });
    }
    slides.forEach(function (s, i) {
      s.setAttribute('role', 'group');
      s.setAttribute('aria-roledescription', 'slide');
      s.setAttribute('aria-label', (i + 1) + ' of ' + slides.length);
    });
    attachHeroSlideErrors();
    heroIndex = 0;
    if (slides.length) {
      showHeroSlide(0);
      heroUserPaused = false;
      startHeroAutoplay();
    }
  }

  document.addEventListener('spangle:hero-slides-rendered', refreshHeroSlider);

  if (nextBtn) {
    nextBtn.addEventListener('click', function () {
      nextHero();
      pauseHeroAutoplay(true);
    });
  }
  if (prevBtn) {
    prevBtn.addEventListener('click', function () {
      prevHero();
      pauseHeroAutoplay(true);
    });
  }

  if (heroSlider) {
    heroSlider.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowRight') {
        nextHero();
        pauseHeroAutoplay(true);
      }
      if (e.key === 'ArrowLeft') {
        prevHero();
        pauseHeroAutoplay(true);
      }
    });
    heroSlider.addEventListener('mouseenter', function () {
      pauseHeroAutoplay();
    });
    heroSlider.addEventListener('mouseleave', function () {
      if (!heroUserPaused) startHeroAutoplay();
    });
    heroSlider.addEventListener('focusin', function () {
      pauseHeroAutoplay();
    });
    heroSlider.addEventListener('focusout', function () {
      if (!heroUserPaused) startHeroAutoplay();
    });
  }

  (function swipe(container, prevFn, nextFn) {
    if (!container) return;
    var startX = 0;
    var startY = 0;
    var threshold = 48;
    container.addEventListener('touchstart', function (e) {
      var t = e.changedTouches[0];
      startX = t.pageX;
      startY = t.pageY;
    }, { passive: true });
    container.addEventListener('touchend', function (e) {
      var t = e.changedTouches[0];
      var distX = t.pageX - startX;
      var distY = t.pageY - startY;
      if (Math.abs(distX) > Math.abs(distY) && Math.abs(distX) > threshold) {
        if (distX < 0) nextFn();
        else prevFn();
        pauseHeroAutoplay(true);
      }
    }, { passive: true });
  }(heroSlider, prevHero, nextHero));

  var LOCAL_IMG_POOL = [
    'uploads/ENTRY.jpg',
    'uploads/1228_HARESHBHAI_LIVING_5.jpg',
    'uploads/1159-VISALBHAI%20RAMPARIYA-5.jpg',
    'uploads/LIVING%2001.jpg',
    'uploads/1228-HARESHBHAI_BED_ROOM-2.jpg',
    'uploads/054-KANTILAL-3D-6.jpg',
    'uploads/LIVING_ROOM_2-1.jpg',
    'uploads/066-UPENDRASINH-3D-3.jpg'
  ];
  var HERO_IMG_FALLBACK = 'uploads/ENTRY.jpg';
  var IMG_PLACEHOLDER_FALLBACK = 'uploads/1228_HARESHBHAI_LIVING_3.jpg';
  var LOCAL_PROJECT_FALLBACK = 'uploads/ENTRY.jpg';

  function imgBasename(u) {
    try {
      var path = new URL(u, window.location.href).pathname;
      return decodeURIComponent(path.split('/').pop() || '');
    } catch (err) {
      return decodeURIComponent((u || '').split('/').pop() || '');
    }
  }

  function nextPoolImage(excludeSrc) {
    var ex = imgBasename(excludeSrc);
    var i;
    for (i = 0; i < LOCAL_IMG_POOL.length; i += 1) {
      if (imgBasename(LOCAL_IMG_POOL[i]) !== ex) return LOCAL_IMG_POOL[i];
    }
    return LOCAL_IMG_POOL[0];
  }

  $$('img[data-img-fallback]').forEach(function (img) {
    img.addEventListener('error', function onDataImgErr() {
      var fb = img.getAttribute('data-img-fallback');
      var cur = img.getAttribute('src') || '';
      if (fb && cur !== fb) {
        img.src = fb;
        return;
      }
      var nxt = nextPoolImage(cur);
      if (nxt && imgBasename(cur) !== imgBasename(nxt)) {
        img.src = nxt;
        return;
      }
      img.removeEventListener('error', onDataImgErr);
      img.src = IMG_PLACEHOLDER_FALLBACK;
    });
  });

  $$('.project-tile img, .work-card img').forEach(function (img) {
    img.addEventListener('error', function onProjImgErr() {
      img.removeEventListener('error', onProjImgErr);
      if (img.getAttribute('data-proj-fb')) return;
      img.setAttribute('data-proj-fb', '1');
      img.src = LOCAL_PROJECT_FALLBACK;
    });
  });

  $$('.blog-img img').forEach(function (img) {
    var step = 0;
    img.addEventListener('error', function onBlogImgErr() {
      step += 1;
      var fb = img.getAttribute('data-blog-fallback');
      if (step === 1 && fb) {
        img.src = fb;
        return;
      }
      img.removeEventListener('error', onBlogImgErr);
      if (step > 1 || !fb) {
        img.src = nextPoolImage(img.getAttribute('src') || '') || LOCAL_PROJECT_FALLBACK;
      }
    });
  });

  refreshHeroSlider();

  function scrollToTestimonial(index) {
    if (!testSlider || !testCards.length) return;
    var card = testCards[index];
    testSlider.scrollTo({ left: card.offsetLeft - 16, behavior: 'smooth' });
    testIndex = index;
  }

  function nextTestimonial() {
    if (!testCards.length) return;
    testIndex = (testIndex + 1) % testCards.length;
    scrollToTestimonial(testIndex);
  }

  function startTestimonialsAutoplay() {
    clearInterval(testTimer);
    if (testUserPaused) return;
    testTimer = setInterval(nextTestimonial, 5200);
  }

  function pauseTestimonialsAutoplay(userPause) {
    clearInterval(testTimer);
    testTimer = null;
    if (userPause) testUserPaused = true;
  }

  if (testSlider) {
    testSlider.addEventListener('mouseenter', function () {
      pauseTestimonialsAutoplay();
    });
    testSlider.addEventListener('mouseleave', function () {
      if (!testUserPaused) startTestimonialsAutoplay();
    });
    testSlider.addEventListener('focusin', function () {
      pauseTestimonialsAutoplay();
    });
    testSlider.addEventListener('focusout', function () {
      if (!testUserPaused) startTestimonialsAutoplay();
    });
    testSlider.addEventListener('touchstart', function () {
      pauseTestimonialsAutoplay(true);
    }, { passive: true });
  }

  if (testCards.length) {
    scrollToTestimonial(0);
    startTestimonialsAutoplay();
  }

  var heroContentEl = heroSlider ? $('.hero-content', heroSlider) : null;
  var prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (heroContentEl && document.body.classList.contains('home') && !prefersReducedMotion) {
    function onHeroReadFade() {
      var sy = window.scrollY;
      var fade = Math.max(0.38, Math.min(1, 1 - sy / (window.innerHeight * 0.52)));
      heroContentEl.style.setProperty('--hero-read-opacity', String(fade));
    }
    window.addEventListener('scroll', onHeroReadFade, { passive: true });
    onHeroReadFade();
  } else if (heroContentEl) {
    heroContentEl.style.setProperty('--hero-read-opacity', '1');
  }

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      if (header && header.classList.contains('nav-open')) closeNav();
      pauseHeroAutoplay(true);
      pauseTestimonialsAutoplay(true);
    }
  });

  $$('.formsubmit-next-url').forEach(function (input) {
    input.value = SITE_PUBLIC_BASE + '/thanks.html';
  });

  $$('.enquiry-form').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!form.checkValidity()) {
        e.preventDefault();
        form.reportValidity();
        return;
      }
      var btn = form.querySelector('[type="submit"]');
      if (btn && !btn.disabled) {
        btn.disabled = true;
        btn.classList.add('is-loading');
        if (!btn.dataset.uxLabel) btn.dataset.uxLabel = btn.textContent.trim();
        btn.textContent = 'Sending…';
      }
    });
  });

  (function enquiryUrlFeedback() {
    var qs = '';
    try {
      qs = window.location.search || '';
    } catch (err) {
      qs = '';
    }
    if (!qs || qs.indexOf('enquiry=') === -1) return;
    var params = new URLSearchParams(qs);
    var code = params.get('enquiry');
    var banner = $('#enquiry-status-banner');
    if (!banner || !code) return;
    var messages = {
      invalid: 'Something was missing or invalid. Please check your name, email, and message.',
      save: 'We could not save your enquiry. If you are on local PHP, ensure the project folder is writable. On a live server, set the data folder and enquiries file to be writable by PHP (see developer notes).',
      perm: 'The server cannot write to data/enquiries.json. In your hosting file manager or SSH: chmod 775 the data folder and chmod 664 data/enquiries.json (or chown to the web user).',
      spam: 'Submission blocked.'
    };
    banner.textContent = messages[code] || 'Please try again.';
    banner.hidden = false;
  }());

  (function thanksPhpNote() {
    var path = '';
    try {
      path = window.location.pathname || '';
    } catch (err2) {
      path = '';
    }
    if (path.indexOf('thanks.html') === -1) return;
    var sent = '';
    try {
      sent = new URLSearchParams(window.location.search || '').get('sent');
    } catch (err3) {
      sent = '';
    }
    var phpNote = $('#thanks-php-note');
    var defNote = $('#thanks-default-note');
    if (sent === '1' && phpNote && defNote) {
      phpNote.hidden = false;
      defNote.hidden = true;
    }
  }());

  $$('a[target="_blank"]').forEach(function (a) {
    a.setAttribute('rel', 'noopener noreferrer');
  });

  if (document.body.classList.contains('page-project-detail') || document.querySelector('.article-hero-img')) {
    var projectLb = document.getElementById('project-lightbox');
    if (!projectLb) {
      projectLb = document.createElement('div');
      projectLb.id = 'project-lightbox';
      projectLb.className = 'site-gallery-lightbox';
      projectLb.hidden = true;
      projectLb.innerHTML =
        '<div class="site-gallery-lightbox-scrim" data-close="1" role="presentation"></div>' +
        '<div class="site-gallery-lightbox-panel" role="dialog" aria-modal="true" aria-label="Project image">' +
        '<button type="button" class="site-gallery-lightbox-close" aria-label="Close">&times;</button>' +
        '<img src="" alt="" />' +
        '</div>';
      document.body.appendChild(projectLb);
      projectLb.addEventListener('click', function (e) {
        if (e.target.getAttribute('data-close') || e.target.closest('.site-gallery-lightbox-close')) {
          projectLb.hidden = true;
          projectLb.querySelector('img').removeAttribute('src');
        }
      });
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && projectLb && !projectLb.hidden) {
          projectLb.hidden = true;
          projectLb.querySelector('img').removeAttribute('src');
        }
      });
    }
    var projectLbImg = projectLb.querySelector('img');
    $$('.page-project-detail .project-gallery-grid img, .page-project-detail .container > p > img, .article-hero-img').forEach(function (img) {
      img.addEventListener('click', function () {
        var src = img.currentSrc || img.src;
        if (!src) return;
        projectLbImg.src = src.replace(/&w=\d+/, '&w=1600');
        projectLbImg.alt = img.alt || '';
        projectLb.hidden = false;
      });
    });
  }

  window.addEventListener('beforeunload', function () {
    clearInterval(heroTimer);
    clearInterval(testTimer);
    releaseFocusTrap();
  });
}());
