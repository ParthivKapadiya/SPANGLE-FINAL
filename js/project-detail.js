(function () {
  'use strict';
  var slides = document.querySelectorAll('.project-gallery-slide');
  if (!slides.length) return;
  var i = 0;
  function show(n) {
    slides[i].classList.remove('is-active');
    i = (n + slides.length) % slides.length;
    slides[i].classList.add('is-active');
  }
  var prev = document.getElementById('gallery-prev');
  var next = document.getElementById('gallery-next');
  if (prev) prev.addEventListener('click', function () { show(i - 1); });
  if (next) next.addEventListener('click', function () { show(i + 1); });
})();
