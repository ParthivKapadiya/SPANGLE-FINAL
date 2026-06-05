/**
 * SPANGLE — site-wide motion (scroll reveals, hero, parallax, counters)
 */
(function () {
  'use strict';

  function $(sel, root) {
    return (root || document).querySelector(sel);
  }

  function $$(sel, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(sel));
  }

  var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  var STAGGER_CHILDREN = [
    '.service-card',
    '.project-tile',
    '.quote-card',
    '.team-card',
    '.award-item',
    '.value-card',
    '.journal-row',
    '.blog-card',
    '.site-gallery-card',
    '.stat',
    '.section-head > *',
    '.split-copy > .section-eyebrow',
    '.split-copy > .section-title',
    '.split-copy > .section-lead',
    '.split-copy > .site-about-lead-wrap',
    '.split-copy > .text-link',
    '.split-media',
    '.cta-inline > *',
  ].join(',');

  function revealAllStatic() {
    $$('.fade-slide, .motion-item, .motion-title').forEach(function (el) {
      el.classList.add('active', 'is-revealed', 'is-visible', 'motion-in');
    });
    document.documentElement.classList.add('motion-ready');
  }

  if (reduced) {
    revealAllStatic();
    return;
  }

  /* —— Page load —— */
  function onPageReady() {
    document.documentElement.classList.add('motion-ready');
  }

  if (document.readyState === 'complete') {
    requestAnimationFrame(onPageReady);
  } else {
    window.addEventListener('load', onPageReady, { once: true });
    setTimeout(onPageReady, 2800);
  }

  /* —— Scroll progress bar —— */
  var progress = document.createElement('div');
  progress.className = 'motion-progress';
  progress.setAttribute('role', 'presentation');
  progress.setAttribute('aria-hidden', 'true');
  document.body.appendChild(progress);

  function updateProgress() {
    var doc = document.documentElement;
    var scrollTop = window.scrollY || doc.scrollTop;
    var max = Math.max(1, doc.scrollHeight - window.innerHeight);
    progress.style.transform = 'scaleX(' + Math.min(1, scrollTop / max) + ')';
  }

  window.addEventListener('scroll', updateProgress, { passive: true });
  updateProgress();

  /* —— Alternating split slide directions —— */
  $$('.split').forEach(function (split, idx) {
    var media = $('.split-media', split);
    var copy = $('.split-copy', split);
    if (media) media.classList.add(idx % 2 === 0 ? 'motion-from-left' : 'motion-from-right');
    if (copy) copy.classList.add(idx % 2 === 0 ? 'motion-from-right' : 'motion-from-left');
  });

  /* —— Stagger items inside sections —— */
  function bindStagger(section) {
    if (section.dataset.motionStaggered) return;
    section.dataset.motionStaggered = '1';
    var items = $$(STAGGER_CHILDREN, section);
    items.forEach(function (item, i) {
      if (item.closest('.hero')) return;
      item.classList.add('motion-item');
      item.style.setProperty('--motion-i', String(i));
    });
    $$('.process-list li', section).forEach(function (li, i) {
      li.style.setProperty('--motion-i', String(i));
    });
    $$('.timeline-vertical li', section).forEach(function (li, i) {
      li.style.setProperty('--motion-i', String(i));
    });
  }

  $$('.fade-slide, .motion-group').forEach(bindStagger);

  /* —— Split section titles into words —— */
  function splitTitle(el) {
    if (!el || el.classList.contains('motion-title') || el.dataset.motionSplit) return;
    var text = el.textContent.trim();
    if (!text || text.length > 80) return;
    if (el.querySelector('em, span, a')) return;
    el.dataset.motionSplit = '1';
    el.classList.add('motion-title');
    el.setAttribute('aria-label', text);
    el.textContent = '';
    text.split(/\s+/).forEach(function (word, i) {
      var span = document.createElement('span');
      span.className = 'motion-word';
      span.style.setProperty('--word-i', String(i));
      span.textContent = word;
      el.appendChild(span);
      if (i < text.split(/\s+/).length - 1) {
        el.appendChild(document.createTextNode(' '));
      }
    });
  }

  $$('.section-title, .page-hero-title').forEach(splitTitle);

  /* —— Scroll reveal (replaces basic observer) —— */
  var revealEls = $$('.fade-slide');

  function activateReveal(el) {
    el.classList.add('active');
    $$('.motion-title', el).forEach(function (t) {
      t.classList.add('is-revealed');
    });
    $$('.media-frame, .service-detail-img, .journal-row-img', el).forEach(function (frame) {
      frame.classList.add('motion-media-revealed');
    });
    countStatsIn(el);
  }

  if ('IntersectionObserver' in window && revealEls.length) {
    var revealIo = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            activateReveal(entry.target);
            revealIo.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.08, rootMargin: '0px 0px -6% 0px' }
    );
    revealEls.forEach(function (el) {
      bindStagger(el);
      revealIo.observe(el);
    });
    requestAnimationFrame(function () {
      revealEls.forEach(function (el) {
        if (el.getBoundingClientRect().top < window.innerHeight * 0.92) {
          activateReveal(el);
          revealIo.unobserve(el);
        }
      });
    });
  } else {
    revealEls.forEach(activateReveal);
  }

  /* —— Observe motion titles outside fade-slide —— */
  $$('.motion-title').forEach(function (title) {
    if (title.closest('.fade-slide')) return;
    if (!('IntersectionObserver' in window)) {
      title.classList.add('is-revealed');
      return;
    }
    var io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-revealed');
            io.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.2 }
    );
    io.observe(title);
  });

  /* —— Parallax —— */
  var parallaxNodes = [];

  function registerParallax(el, strength) {
    if (!el || el.dataset.parallaxBound) return;
    el.dataset.parallaxBound = '1';
    el.setAttribute('data-parallax', String(strength));
    parallaxNodes.push({ el: el, strength: strength });
  }

  $$('.page-hero').forEach(function (hero) {
    registerParallax(hero, 0.05);
  });

  $$('.media-frame').forEach(function (frame) {
    registerParallax(frame, 0.025);
  });

  var parallaxTicking = false;

  function runParallax() {
    parallaxTicking = false;
    parallaxNodes.forEach(function (node) {
      var rect = node.el.getBoundingClientRect();
      var center = rect.top + rect.height * 0.5;
      var viewCenter = window.innerHeight * 0.5;
      var offset = (center - viewCenter) * node.strength;
      if (node.el.classList.contains('page-hero')) {
        node.el.style.backgroundPosition = 'center calc(50% + ' + offset.toFixed(1) + 'px)';
        return;
      }
      node.el.style.transform = 'translate3d(0, ' + offset.toFixed(2) + 'px, 0)';
    });
  }

  function onParallaxScroll() {
    if (!parallaxTicking) {
      parallaxTicking = true;
      requestAnimationFrame(runParallax);
    }
  }

  window.addEventListener('scroll', onParallaxScroll, { passive: true });
  window.addEventListener('resize', onParallaxScroll, { passive: true });
  runParallax();

  /* —— Stat counters —— */
  var countedStats = new WeakSet();

  function parseStatValue(text) {
    var raw = (text || '').trim();
    var suffix = raw.replace(/[\d.,]/g, '');
    var num = parseFloat(raw.replace(/[^0-9.]/g, ''));
    if (isNaN(num)) return null;
    return { num: num, suffix: suffix, raw: raw };
  }

  function animateValue(el, parsed, duration) {
    var start = performance.now();
    var from = 0;
    var to = parsed.num;
    var isInt = parsed.raw.indexOf('.') === -1 && to % 1 === 0;

    function frame(now) {
      var t = Math.min(1, (now - start) / duration);
      var eased = 1 - Math.pow(1 - t, 3);
      var current = from + (to - from) * eased;
      var display = isInt ? Math.round(current) : current.toFixed(1);
      el.textContent = display + parsed.suffix;
      if (t < 1) {
        requestAnimationFrame(frame);
      } else {
        el.textContent = parsed.raw;
        el.classList.add('motion-counted');
      }
    }
    requestAnimationFrame(frame);
  }

  function countStatsIn(root) {
    $$('.stat-value', root).forEach(function (el) {
      if (countedStats.has(el)) return;
      var parsed = parseStatValue(el.textContent);
      if (!parsed || parsed.num <= 0) return;
      countedStats.add(el);
      animateValue(el, parsed, 1400);
    });
  }

  var statsBar = $('.stats-bar');
  if (statsBar && 'IntersectionObserver' in window) {
    var statsIo = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            countStatsIn(entry.target);
            statsIo.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.35 }
    );
    statsIo.observe(statsBar);
  }

  /* —— Magnetic buttons —— */
  $$('.btn-primary, .btn-ghost, .nav-cta').forEach(function (btn) {
    if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
      btn.setAttribute('data-magnetic', '');
      btn.addEventListener('mousemove', function (e) {
        var rect = btn.getBoundingClientRect();
        var x = (e.clientX - rect.left - rect.width / 2) * 0.12;
        var y = (e.clientY - rect.top - rect.height / 2) * 0.12;
        btn.style.transform = 'translate(' + x.toFixed(1) + 'px, ' + y.toFixed(1) + 'px)';
      });
      btn.addEventListener('mouseleave', function () {
        btn.style.transform = '';
      });
    }
    btn.addEventListener('mouseup', function () {
      btn.style.transform = '';
    });
    btn.addEventListener('blur', function () {
      btn.style.transform = '';
    });
  });

  /* —— Work filter re-animation —— */
  var workFilter = $('[data-work-filter]');
  if (workFilter) {
    workFilter.addEventListener('click', function (e) {
      var btn = e.target.closest('.work-filter-btn');
      if (!btn) return;
      $$('.work-card', workFilter).forEach(function (card, i) {
        if (card.hidden) return;
        card.classList.remove('motion-filter-in');
        void card.offsetWidth;
        card.style.setProperty('--motion-i', String(i));
        card.classList.add('motion-filter-in');
      });
    });
  }

  /* —— Re-bind when dynamic content injects (content-bridge) —— */
  window.SpangleMotion = {
    refresh: function () {
      $$('.fade-slide').forEach(function (section) {
        bindStagger(section);
        if (section.classList.contains('active')) {
          $$('.motion-item', section).forEach(function (item) {
            item.classList.add('motion-in');
          });
          $$('.journal-row-img', section).forEach(function (frame) {
            frame.classList.add('motion-media-revealed');
          });
          return;
        }
        if (section.getBoundingClientRect().top < window.innerHeight * 0.92) {
          activateReveal(section);
        }
      });
      $$('.project-tile, .site-gallery-card, .blog-card').forEach(function (el, i) {
        if (!el.classList.contains('motion-item')) {
          el.classList.add('motion-item');
          el.style.setProperty('--motion-i', String(i));
        }
        var parent = el.closest('.fade-slide');
        if (parent && parent.classList.contains('active')) {
          el.classList.add('motion-in');
        }
      });
      onParallaxScroll();
    },
  };

  document.addEventListener('spangle:content-updated', function () {
    if (window.SpangleMotion) window.SpangleMotion.refresh();
  });
})();
