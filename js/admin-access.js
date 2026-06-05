(function () {
  'use strict';

  var openBtn = document.getElementById('admin-access-open');
  var modal = document.getElementById('admin-access-modal');
  var form = document.getElementById('admin-access-form');
  var errEl = document.getElementById('admin-access-error');
  if (!openBtn || !modal || !form) return;

  var csrfToken = '';

  function showModal() {
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
    var first = form.querySelector('input');
    if (first) first.focus();
  }

  function hideModal() {
    modal.hidden = true;
    document.body.style.overflow = '';
  }

  function showError(msg) {
    if (!errEl) return;
    errEl.textContent = msg;
    errEl.hidden = !msg;
  }

  function fetchCsrf() {
    return fetch('api/csrf.php', { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        csrfToken = data.token || data.csrf || '';
      })
      .catch(function () {});
  }

  openBtn.addEventListener('click', function () {
    showError('');
    showModal();
    if (!csrfToken) fetchCsrf();
  });

  modal.querySelectorAll('[data-admin-access-close]').forEach(function (el) {
    el.addEventListener('click', hideModal);
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !modal.hidden) hideModal();
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    showError('');
    var fd = new FormData(form);
    var payload = {
      email: fd.get('email'),
      password: fd.get('password'),
      csrf_token: csrfToken
    };
    fetch('api/admin-login.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify(payload)
    })
      .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
      .then(function (res) {
        if (res.body && res.body.ok) {
          window.location.href = res.body.redirect || 'admin/index.php';
          return;
        }
        var code = (res.body && res.body.error) || 'invalid_credentials';
        var messages = {
          locked: 'Too many attempts. Please wait and try again.',
          csrf: 'Session expired. Close this window and try again.',
          missing_fields: 'Enter your email and password.',
          invalid_credentials: 'Email or password is incorrect.'
        };
        showError(messages[code] || 'Could not sign in. Please try again.');
      })
      .catch(function () {
        showError('Connection error. Check that the website database is configured.');
      });
  });

  fetchCsrf();
})();
