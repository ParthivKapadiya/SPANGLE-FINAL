(function () {
  'use strict';

  var MODAL_ID = 'spangle-consult-modal';
  var csrfToken = '';

  function $(sel, ctx) {
    return (ctx || document).querySelector(sel);
  }

  function projectTypes() {
    return [
      'Residential',
      'Commercial',
      'Interior Design',
      'Architecture',
      'Landscape',
    ];
  }

  function budgetRanges() {
    return [
      'Under 10 Lakh',
      '10–25 Lakh',
      '25–50 Lakh',
      '50 Lakh–1 Crore',
      'Above 1 Crore',
    ];
  }

  function apiBase() {
    var path = window.location.pathname.replace(/\/[^/]*$/, '');
    return window.location.origin + path;
  }

  function fetchCsrf() {
    return fetch(apiBase() + '/api/csrf.php', { credentials: 'same-origin' })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        if (data && data.token) csrfToken = data.token;
      })
      .catch(function () {});
  }

  function buildModal() {
    if (document.getElementById(MODAL_ID)) return;

    var types = projectTypes()
      .map(function (t) {
        return '<option value="' + t + '">' + t + '</option>';
      })
      .join('');
    var budgets = budgetRanges()
      .map(function (b) {
        return '<option value="' + b + '">' + b + '</option>';
      })
      .join('');

    var modal = document.createElement('div');
    modal.id = MODAL_ID;
    modal.className = 'consult-modal';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-labelledby', 'consult-modal-title');
    modal.hidden = true;
    modal.innerHTML =
      '<div class="consult-modal-backdrop" data-consult-close></div>' +
      '<div class="consult-modal-panel">' +
      '<button type="button" class="consult-modal-close" data-consult-close aria-label="Close">&times;</button>' +
      '<div class="consult-modal-body">' +
      '<p class="consult-modal-eyebrow">Free consultation</p>' +
      '<h2 id="consult-modal-title" class="consult-modal-title">Book your project call</h2>' +
      '<p class="consult-modal-lead">Share a few details — our studio will respond within two business days.</p>' +
      '<form class="consult-form" id="consult-form" novalidate>' +
      '<label>Name<input type="text" name="name" required autocomplete="name" maxlength="200" /></label>' +
      '<div class="consult-form-row">' +
      '<label>Mobile<input type="tel" name="phone" required autocomplete="tel" inputmode="tel" maxlength="20" /></label>' +
      '<label>Email<input type="email" name="email" required autocomplete="email" maxlength="254" /></label>' +
      '</div>' +
      '<div class="consult-form-row">' +
      '<label>Project type<select name="project_type" required><option value="">Select…</option>' +
      types +
      '</select></label>' +
      '<label>Budget range<select name="budget_range" required><option value="">Select…</option>' +
      budgets +
      '</select></label>' +
      '</div>' +
      '<label>Location<input type="text" name="location" required maxlength="200" placeholder="City, state" /></label>' +
      '<label>Message<textarea name="message" required maxlength="5000" placeholder="Brief overview of your project…"></textarea></label>' +
      '<p class="consult-form-error" id="consult-form-error" hidden></p>' +
      '<button type="submit" class="btn btn-primary consult-form-submit">Request consultation</button>' +
      '</form>' +
      '</div>' +
      '<div class="consult-success" id="consult-success" hidden>' +
      '<div class="consult-success-icon" aria-hidden="true"><i class="fas fa-check"></i></div>' +
      '<h3>Thank you</h3>' +
      '<p>Your consultation request is with our team. We will be in touch shortly.</p>' +
      '<button type="button" class="btn btn-ghost" data-consult-close>Close</button>' +
      '</div>' +
      '</div>';

    document.body.appendChild(modal);

    modal.querySelectorAll('[data-consult-close]').forEach(function (el) {
      el.addEventListener('click', closeModal);
    });

    document.getElementById('consult-form').addEventListener('submit', onSubmit);
  }

  function injectCtas() {
    if (document.body.classList.contains('home')) {
      return;
    }
    if (!$('.consult-cta-desktop')) {
      var desk = document.createElement('button');
      desk.type = 'button';
      desk.className = 'consult-cta-desktop';
      desk.setAttribute('aria-haspopup', 'dialog');
      desk.innerHTML = '<i class="fas fa-calendar-check" aria-hidden="true"></i> Book Free Consultation';
      desk.addEventListener('click', openModal);
      document.body.appendChild(desk);
    }

    if (!$('.consult-cta-mobile')) {
      var mob = document.createElement('div');
      mob.className = 'consult-cta-mobile';
      mob.innerHTML =
        '<button type="button" aria-haspopup="dialog">Book Free Consultation</button>';
      mob.querySelector('button').addEventListener('click', openModal);
      document.body.appendChild(mob);
    }
  }

  function openModal() {
    buildModal();
    var modal = document.getElementById(MODAL_ID);
    if (!modal) return;
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
    var err = document.getElementById('consult-form-error');
    if (err) err.hidden = true;
    var first = modal.querySelector('input[name="name"]');
    if (first) first.focus();
  }

  function closeModal() {
    var modal = document.getElementById(MODAL_ID);
    if (!modal) return;
    modal.hidden = true;
    document.body.style.overflow = '';
  }

  function showError(msg) {
    var err = document.getElementById('consult-form-error');
    if (!err) return;
    err.textContent = msg;
    err.hidden = false;
  }

  function validatePhone(phone) {
    var digits = String(phone).replace(/\D/g, '');
    return digits.length >= 10 && digits.length <= 15;
  }

  function onSubmit(e) {
    e.preventDefault();
    var form = e.target;
    var fd = new FormData(form);
    var name = String(fd.get('name') || '').trim();
    var phone = String(fd.get('phone') || '').trim();
    var email = String(fd.get('email') || '').trim();
    var projectType = String(fd.get('project_type') || '').trim();
    var budget = String(fd.get('budget_range') || '').trim();
    var location = String(fd.get('location') || '').trim();
    var message = String(fd.get('message') || '').trim();

    if (!name || name.length < 2) {
      showError('Please enter your name.');
      return;
    }
    if (!validatePhone(phone)) {
      showError('Please enter a valid mobile number (10+ digits).');
      return;
    }
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      showError('Please enter a valid email address.');
      return;
    }
    if (!projectType || !budget || !location || !message) {
      showError('Please complete all fields.');
      return;
    }

    var err = document.getElementById('consult-form-error');
    if (err) err.hidden = true;

    var submitBtn = form.querySelector('[type="submit"]');
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = 'Sending…';
    }

    fd.append('form_source', 'consultation');
    fd.append('csrf_token', csrfToken);

    fetch(apiBase() + '/api/submit-consultation.php', {
      method: 'POST',
      body: fd,
      credentials: 'same-origin',
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        if (!data || !data.ok) {
          throw new Error((data && data.error) || 'Unable to send. Please try again.');
        }
        form.hidden = true;
        var success = document.getElementById('consult-success');
        if (success) success.hidden = false;
      })
      .catch(function (ex) {
        showError(ex.message || 'Something went wrong. Please try the contact page.');
      })
      .finally(function () {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = 'Request consultation';
        }
      });
  }

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeModal();
  });

  function init() {
    fetchCsrf();
    buildModal();
    injectCtas();
    document.addEventListener('click', function (e) {
      var trigger = e.target.closest('[data-consult-open]');
      if (!trigger) return;
      e.preventDefault();
      openModal();
    });
    if (!document.body.classList.contains('home')) {
      document.body.classList.add('has-consult-cta');
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  window.SpangleConsultation = { open: openModal, close: closeModal };
})();
