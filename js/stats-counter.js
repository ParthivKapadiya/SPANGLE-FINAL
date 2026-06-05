(function () {
  'use strict';

  var DURATION = 1400;

  function parseTarget(el) {
    var raw = el.getAttribute('data-count') || el.textContent.trim();
    var match = String(raw).match(/^([\d,.]+)(\+?)(.*)$/);
    if (!match) return null;
    var num = parseFloat(match[1].replace(/,/g, ''));
    if (isNaN(num)) return null;
    return {
      value: num,
      suffix: (match[2] || '') + (match[3] || ''),
      decimals: (match[1].split('.')[1] || '').length,
    };
  }

  function animateValue(el, target, start, duration) {
    var startTime = null;
    function step(ts) {
      if (!startTime) startTime = ts;
      var p = Math.min((ts - startTime) / duration, 1);
      var eased = 1 - Math.pow(1 - p, 3);
      var current = start + (target.value - start) * eased;
      var text =
        target.decimals > 0
          ? current.toFixed(target.decimals)
          : String(Math.round(current));
      el.textContent = text + target.suffix;
      if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }

  function run(section) {
    if (section.classList.contains('is-counted')) return;
    section.classList.add('is-counted');
    section.querySelectorAll('.stat-value[data-count]').forEach(function (el) {
      var target = parseTarget(el);
      if (!target) return;
      animateValue(el, target, 0, DURATION);
    });
  }

  function init() {
    var sections = document.querySelectorAll('.stats-bar');
    if (!sections.length || !('IntersectionObserver' in window)) return;

    var io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            run(entry.target);
            io.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.35 }
    );

    sections.forEach(function (section) {
      section.querySelectorAll('.stat-value').forEach(function (el) {
        if (!el.hasAttribute('data-count')) {
          var t = el.textContent.trim();
          if (/^\d/.test(t)) el.setAttribute('data-count', t);
        }
      });
      io.observe(section);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
