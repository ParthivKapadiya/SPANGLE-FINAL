(function () {
  var section = document.getElementById('developer-connect-section');
  var list = document.getElementById('developer-connect');
  if (!section || !list) return;

  fetch('content/developer-contact.json', { cache: 'no-store' })
    .then(function (res) { return res.ok ? res.json() : null; })
    .then(function (data) {
      if (!data) return;
      var items = [];

      if (data.email) {
        items.push({
          icon: 'fas fa-envelope',
          href: 'mailto:' + data.email,
          label: data.emailLabel || data.email,
        });
      }
      if (data.linkedin) {
        items.push({
          icon: 'fab fa-linkedin-in',
          href: data.linkedin,
          label: 'LinkedIn',
        });
      }
      if (data.github) {
        items.push({
          icon: 'fab fa-github',
          href: data.github,
          label: 'GitHub',
        });
      }
      if (data.portfolio) {
        items.push({
          icon: 'fas fa-globe',
          href: data.portfolio,
          label: data.portfolioLabel || 'Portfolio',
        });
      }

      if (!items.length) return;

      items.forEach(function (item) {
        var li = document.createElement('li');
        var icon = document.createElement('i');
        icon.className = item.icon;
        icon.setAttribute('aria-hidden', 'true');
        var a = document.createElement('a');
        a.href = item.href;
        a.textContent = item.label;
        if (item.href.indexOf('http') === 0) {
          a.rel = 'noopener noreferrer';
          a.target = '_blank';
        }
        li.appendChild(icon);
        li.appendChild(document.createTextNode(' '));
        li.appendChild(a);
        list.appendChild(li);
      });

      section.hidden = false;
    })
    .catch(function () {});
})();
