// index.js - slider, mobile menu, header scroll, accessibility helpers
(function () {
  'use strict';

  // Helpers
  const $ = sel => document.querySelector(sel);
  const $$ = sel => Array.from(document.querySelectorAll(sel));

  // Elements
  const header = $('#site-header');
  const mobileToggle = $('#mobile-menu');
  const mobileOverlay = $('#mobile-overlay');
  const closeOverlay = $('#close-overlay');
  const overlayLinks = mobileOverlay ? Array.from(mobileOverlay.querySelectorAll('a')) : [];
  const heroSlider = $('#hero-slider');
  const slides = heroSlider ? Array.from(heroSlider.querySelectorAll('.slide')) : [];
  const prevBtn = $('#prev');
  const nextBtn = $('#next');
  const sliderLive = $('#slider-live');

  /* ---------- Header scroll ---------- */
  function handleScroll() {
    if (!header) return;
    if (window.scrollY > 20) header.classList.add('scrolled');
    else header.classList.remove('scrolled');
  }
  window.addEventListener('scroll', handleScroll, { passive: true });
  handleScroll();

  /* ---------- Mobile nav open/close + accessibility ---------- */
  function openMobileNav() {
    if (!mobileOverlay || !mobileToggle) return;
    mobileOverlay.classList.add('active');
    mobileOverlay.setAttribute('aria-hidden', 'false');
    mobileToggle.setAttribute('aria-expanded', 'true');
    const first = mobileOverlay.querySelector('a');
    if (first) first.focus();
    document.body.style.overflow = 'hidden';
    trapFocus(mobileOverlay);
  }

  function closeMobileNav() {
    if (!mobileOverlay || !mobileToggle) return;
    mobileOverlay.classList.remove('active');
    mobileOverlay.setAttribute('aria-hidden', 'true');
    mobileToggle.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
    releaseFocusTrap();
    mobileToggle.focus();
  }

  if (mobileToggle) {
    mobileToggle.addEventListener('click', () => openMobileNav());
    mobileToggle.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openMobileNav(); }
    });
  }
  if (closeOverlay) {
    closeOverlay.addEventListener('click', closeMobileNav);
    closeOverlay.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); closeMobileNav(); }
    });
  }
  if (mobileOverlay) {
    mobileOverlay.addEventListener('click', (e) => {
      if (e.target === mobileOverlay) closeMobileNav();
    });
  }
  overlayLinks.forEach(a => 
    a.addEventListener('click', () => setTimeout(closeMobileNav, 150))
  );

  // ESC to close overlay and pause sliders
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      if (mobileOverlay && mobileOverlay.classList.contains('active')) closeMobileNav();
      pauseHeroAutoplay();
      pauseTestimonialsAutoplay();
    }
  });

  /* ---------- Focus trap ---------- */
  let _lastFocusedBeforeTrap = null;
  let _trapContainer = null;
  function trapFocus(container) {
    releaseFocusTrap();
    _trapContainer = container;
    _lastFocusedBeforeTrap = document.activeElement;
    const focusable = Array.from(container.querySelectorAll('a, button, input, textarea, [tabindex]:not([tabindex="-1"])'))
      .filter(el => !el.hasAttribute('disabled'));
    if (!focusable.length) return;
    const first = focusable[0], last = focusable[focusable.length - 1];
    function handle(e) {
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
    }
    container.__focusHandler = handle;
    document.addEventListener('keydown', handle);
  }
  function releaseFocusTrap() {
    if (_trapContainer && _trapContainer.__focusHandler) {
      document.removeEventListener('keydown', _trapContainer.__focusHandler);
      _trapContainer.__focusHandler = null;
    }
    if (_lastFocusedBeforeTrap) {
      try { _lastFocusedBeforeTrap.focus(); } catch (e) {}
    }
    _trapContainer = null;
    _lastFocusedBeforeTrap = null;
  }

  /* ---------- HERO SLIDER ---------- */
  let heroIndex = slides.findIndex(s => s.classList.contains('active'));
  if (heroIndex < 0) heroIndex = 0;
  let heroInterval = 6000;
  let heroTimer = null;
  let heroUserPaused = false;

  function announceSlide(i) {
    if (!sliderLive) return;
    const slide = slides[i];
    const label = slide ? slide.getAttribute('alt') || `Slide ${i + 1}` : `Slide ${i + 1}`;
    sliderLive.textContent = `Slide ${i + 1} - ${label}`;
  }

  function showHeroSlide(index) {
    if (!slides.length) return;
    slides.forEach((s, i) => {
      s.classList.toggle('active', i === index);
      s.setAttribute('aria-hidden', i === index ? 'false' : 'true');
    });
    heroIndex = index;
    announceSlide(index);
  }

  function nextHero() { showHeroSlide((heroIndex + 1) % slides.length); }
  function prevHero() { showHeroSlide((heroIndex - 1 + slides.length) % slides.length); }

  function startHeroAutoplay() {
    clearInterval(heroTimer);
    if (heroUserPaused) return; // don't restart if user paused
    heroTimer = setInterval(() => { nextHero(); }, heroInterval);
  }
  function pauseHeroAutoplay(userPause = false) {
    clearInterval(heroTimer);
    heroTimer = null;
    if (userPause) heroUserPaused = true;
  }

  if (nextBtn) nextBtn.addEventListener('click', () => { nextHero(); pauseHeroAutoplay(true); });
  if (prevBtn) prevBtn.addEventListener('click', () => { prevHero(); pauseHeroAutoplay(true); });

  if (heroSlider) {
    heroSlider.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowRight') { nextHero(); pauseHeroAutoplay(true); }
      if (e.key === 'ArrowLeft')  { prevHero(); pauseHeroAutoplay(true); }
    });
    heroSlider.addEventListener('mouseenter', () => pauseHeroAutoplay());
    heroSlider.addEventListener('mouseleave', () => { if (!heroUserPaused) startHeroAutoplay(); });
    heroSlider.addEventListener('focusin', () => pauseHeroAutoplay());
    heroSlider.addEventListener('focusout', () => { if (!heroUserPaused) startHeroAutoplay(); });
  }

  // touch swipe for hero
  (function addSwipe(container, prevFn, nextFn) {
    if (!container) return;
    let startX = 0, startY = 0;
    const threshold = 40;
    container.addEventListener('touchstart', (e) => {
      const t = e.changedTouches[0]; startX = t.pageX; startY = t.pageY;
    }, { passive: true });
    container.addEventListener('touchend', (e) => {
      const t = e.changedTouches[0];
      const distX = t.pageX - startX, distY = t.pageY - startY;
      if (Math.abs(distX) > Math.abs(distY) && Math.abs(distX) > threshold) {
        if (distX < 0) nextFn(); else prevFn();
        pauseHeroAutoplay(true);
      }
    }, { passive: true });
  })(heroSlider, prevHero, nextHero);

  // init hero
  if (slides.length) {
    slides.forEach((s, i) => {
      s.setAttribute('role', 'group');
      s.setAttribute('aria-roledescription', 'slide');
      s.setAttribute('aria-label', `${i + 1} of ${slides.length}`);
    });
    showHeroSlide(heroIndex);
    startHeroAutoplay();
  }

  /* ---------- TESTIMONIALS SLIDER ---------- */
  const testimonialsSlider = document.querySelector('.testimonials-slider');
  const testimonialsTrack = document.querySelector('.testimonials-track');
  const testimonialCards = testimonialsTrack ? Array.from(testimonialsTrack.querySelectorAll('.testimonial-card')) : [];
  let testIndex = 0;
  let testTimer = null;
  let testUserPaused = false;

  function scrollToTestimonial(index) {
    if (!testimonialCards.length || !testimonialsSlider) return;
    const card = testimonialCards[index];
    testimonialsSlider.scrollTo({ left: card.offsetLeft - 12, behavior: 'smooth' });
    testIndex = index;
  }
  function nextTestimonial() {
    testIndex = (testIndex + 1) % testimonialCards.length;
    scrollToTestimonial(testIndex);
  }
  function startTestimonialsAutoplay() {
    clearInterval(testTimer);
    if (testUserPaused) return;
    testTimer = setInterval(nextTestimonial, 5000);
  }
  function pauseTestimonialsAutoplay(userPause = false) {
    clearInterval(testTimer);
    testTimer = null;
    if (userPause) testUserPaused = true;
  }

  if (testimonialsSlider) {
    testimonialsSlider.addEventListener('mouseenter', () => pauseTestimonialsAutoplay());
    testimonialsSlider.addEventListener('mouseleave', () => { if (!testUserPaused) startTestimonialsAutoplay(); });
    testimonialsSlider.addEventListener('focusin', () => pauseTestimonialsAutoplay());
    testimonialsSlider.addEventListener('focusout', () => { if (!testUserPaused) startTestimonialsAutoplay(); });
    testimonialsSlider.addEventListener('touchstart', () => pauseTestimonialsAutoplay(true), { passive: true });
  }

  if (testimonialCards.length) {
    scrollToTestimonial(0);
    startTestimonialsAutoplay();
  }

  /* ---------- Scroll reveal ---------- */
  const revealEls = Array.from(document.querySelectorAll('.fade-slide'));
  function revealOnScroll() {
    const trigger = window.innerHeight * 0.9;
    revealEls.forEach(el => {
      const top = el.getBoundingClientRect().top;
      if (top < trigger) el.classList.add('active');
    });
  }
  window.addEventListener('scroll', revealOnScroll, { passive: true });
  window.addEventListener('load', revealOnScroll);
  document.addEventListener('DOMContentLoaded', revealOnScroll);

  /* ---------- small utilities ---------- */
  $$('.desktop-nav a').forEach(a => a.setAttribute('tabindex', '0'));
  $$('a[target="_blank"]').forEach(a => a.setAttribute('rel', 'noopener noreferrer'));

  // Fix: Ensure mobile nav closes immediately on link click (no delay)
  overlayLinks.forEach(a => {
    a.addEventListener('click', closeMobileNav);
  });

  window.addEventListener('beforeunload', () => {
    clearInterval(heroTimer);
    clearInterval(testTimer);
    releaseFocusTrap();
  });
})();
