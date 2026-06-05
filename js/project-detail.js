(function () {
  'use strict';

  if (!document.body.classList.contains('page-project-detail')) return;

  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function $$(sel) {
    return Array.prototype.slice.call(document.querySelectorAll(sel));
  }

  function initReveals() {
    if (!('IntersectionObserver' in window)) {
      $$('.prj-reveal').forEach(function (el) {
        el.classList.add('is-in');
      });
      return;
    }
    var io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-in');
            io.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.12, rootMargin: '0px 0px -5% 0px' }
    );
    $$('.prj-reveal').forEach(function (el) {
      io.observe(el);
    });
  }

  function loadScript(src) {
    return new Promise(function (resolve, reject) {
      var s = document.createElement('script');
      s.src = src;
      s.defer = true;
      s.onload = resolve;
      s.onerror = reject;
      document.body.appendChild(s);
    });
  }

  function initGsap() {
    if (reduced || !window.gsap) {
      $$('.prj-reveal').forEach(function (el) {
        el.classList.add('is-in');
      });
      return;
    }
    if (window.ScrollTrigger) gsap.registerPlugin(ScrollTrigger);

    var heroImg = document.querySelector('.prj-hero__img');
    if (heroImg && window.ScrollTrigger) {
      gsap.to(heroImg, {
        yPercent: 12,
        ease: 'none',
        scrollTrigger: {
          trigger: '.prj-hero',
          start: 'top top',
          end: 'bottom top',
          scrub: true,
        },
      });
    }

    gsap.utils.toArray('.prj-reveal:not(.is-in)').forEach(function (el) {
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
          scrollTrigger: {
            trigger: el,
            start: 'top 88%',
            toggleActions: 'play none none none',
          },
        }
      );
    });
  }

  initReveals();

  if (!reduced) {
    var base = 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/';
    loadScript(base + 'gsap.min.js')
      .then(function () {
        return loadScript(base + 'ScrollTrigger.min.js');
      })
      .then(initGsap)
      .catch(function () {
        $$('.prj-reveal').forEach(function (el) {
          el.classList.add('is-in');
        });
      });
  }

  window.setTimeout(function () {
    $$('.prj-reveal:not(.is-in)').forEach(function (el) {
      el.classList.add('is-in');
    });
  }, 2200);
})();
