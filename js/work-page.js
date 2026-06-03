/**
 * Work page — category filters (All / Residential / Commercial / Retail)
 */
(function () {
  'use strict';

  function $(sel, root) {
    return (root || document).querySelector(sel);
  }

  function $$(sel, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(sel));
  }

  var workRoot = null;
  var statusEl = null;
  var emptyEl = null;
  var clickBound = false;

  function getCards() {
    if (!workRoot) return [];
    return $$('.work-card[data-work-cat]', workRoot);
  }

  function ensureChrome() {
    if (!workRoot) return;

    if (!statusEl) {
      statusEl = $('.work-filter-status', workRoot);
      if (!statusEl) {
        statusEl = document.createElement('p');
        statusEl.className = 'work-filter-status';
        statusEl.setAttribute('aria-live', 'polite');
        var filterBar = $('.work-filter-bar', workRoot);
        if (filterBar && filterBar.parentNode) {
          filterBar.parentNode.insertBefore(statusEl, filterBar.nextSibling);
        }
      }
    }

    if (!emptyEl || !emptyEl.parentNode) {
      emptyEl = $('.work-filter-empty', workRoot);
      if (!emptyEl || !emptyEl.parentNode) {
        emptyEl = null;
        var archive = $('.work-archive', workRoot);
        if (archive) {
          emptyEl = document.createElement('div');
          emptyEl.className = 'work-filter-empty';
          emptyEl.hidden = true;
          emptyEl.innerHTML =
            '<p>No projects in this category yet.</p>' +
            '<p class="work-filter-empty-actions">' +
            '<button type="button" class="btn btn-ghost work-filter-reset">View all work</button> ' +
            '<a href="contact.html" class="btn btn-primary">Start a project</a>' +
            '</p>';
          archive.appendChild(emptyEl);
        }
      }
    }
  }

  function updateCounts() {
    var cards = getCards();
    var totals = { all: cards.length, residential: 0, commercial: 0, retail: 0 };
    cards.forEach(function (card) {
      var cat = card.getAttribute('data-work-cat') || '';
      if (totals[cat] !== undefined) totals[cat] += 1;
    });

    $$('.work-filter-btn', workRoot).forEach(function (btn) {
      var filter = btn.getAttribute('data-filter') || 'all';
      var count = filter === 'all' ? totals.all : totals[filter] || 0;
      var badge = btn.querySelector('.work-filter-count');
      if (badge) badge.textContent = String(count);
      btn.setAttribute('data-count', String(count));
      btn.disabled = filter !== 'all' && count === 0;
    });

    return totals;
  }

  function animateVisibleCards() {
    var index = 0;
    getCards().forEach(function (card) {
      if (card.hidden) return;
      card.classList.remove('motion-filter-in');
      void card.offsetWidth;
      card.style.setProperty('--motion-i', String(index));
      card.classList.add('motion-filter-in');
      index += 1;
    });
  }

  function applyFilter(cat) {
    cat = cat || 'all';
    var cards = getCards();
    var visible = 0;

    cards.forEach(function (card) {
      var cardCat = card.getAttribute('data-work-cat') || '';
      var show = cat === 'all' || cardCat === cat;
      card.hidden = !show;
      card.classList.toggle('is-filter-hidden', !show);
      if (show) visible += 1;
    });

    var activeBtn = $('.work-filter-btn[data-filter="' + cat + '"]', workRoot);
    var label = activeBtn ? activeBtn.getAttribute('data-label') || activeBtn.textContent.trim() : 'All';

    if (statusEl) {
      statusEl.textContent =
        visible +
        ' project' +
        (visible === 1 ? '' : 's') +
        (cat === 'all' ? '' : ' · ' + label.replace(/\s+\d+$/, ''));
    }

    if (emptyEl) emptyEl.hidden = visible > 0;

    if (history.replaceState) {
      var next = window.location.pathname + window.location.search + (cat === 'all' ? '' : '#' + cat);
      history.replaceState(null, '', next);
    }

    animateVisibleCards();
  }

  function setActiveButton(btn) {
    $$('.work-filter-btn', workRoot).forEach(function (b) {
      var on = b === btn;
      b.classList.toggle('is-active', on);
      b.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
  }

  function bindInteractions() {
    if (!workRoot || clickBound) return;
    clickBound = true;

    workRoot.addEventListener('click', function (e) {
      var btn = e.target.closest('.work-filter-btn');
      if (btn && workRoot.contains(btn) && !btn.disabled) {
        setActiveButton(btn);
        applyFilter(btn.getAttribute('data-filter') || 'all');
        return;
      }

      if (e.target.closest('.work-filter-reset')) {
        var allBtn = $('.work-filter-btn[data-filter="all"]', workRoot);
        if (allBtn) {
          setActiveButton(allBtn);
          applyFilter('all');
        }
      }
    });
  }

  function initWorkPage() {
    workRoot = $('[data-work-filter]');
    if (!workRoot || !getCards().length) return;

    ensureChrome();
    bindInteractions();
    updateCounts();

    var hash = (window.location.hash || '').replace(/^#/, '');
    var hashBtn = hash ? $('.work-filter-btn[data-filter="' + hash + '"]', workRoot) : null;
    if (hashBtn && !hashBtn.disabled) {
      setActiveButton(hashBtn);
      applyFilter(hash);
    } else {
      var active = $('.work-filter-btn.is-active', workRoot) || $('.work-filter-btn[data-filter="all"]', workRoot);
      if (active) {
        setActiveButton(active);
        applyFilter(active.getAttribute('data-filter') || 'all');
      }
    }
  }

  document.addEventListener('spangle:work-archive-rendered', initWorkPage);
  document.addEventListener('DOMContentLoaded', function () {
    workRoot = $('[data-work-filter]');
    if (getCards().length) initWorkPage();
  });

  window.SpangleWorkPage = { refresh: initWorkPage };
}());
