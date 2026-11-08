// =========================================================
// Lost & Found System - main.js
// =========================================================

document.addEventListener('DOMContentLoaded', function () {

  // ---------- Scroll-reveal animation ----------
  const revealEls = document.querySelectorAll('.reveal');
  if (revealEls.length) {
    if ('IntersectionObserver' in window) {
      const io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('in');
            io.unobserve(entry.target);
          }
        });
      }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
      revealEls.forEach(function (el) { io.observe(el); });
    } else {
      revealEls.forEach(function (el) { el.classList.add('in'); });
    }
  }

  // ---------- Animated stat counters (hero + dashboard) ----------
  const counters = document.querySelectorAll('[data-count]');
  if (counters.length) {
    const animateCount = function (el) {
      const target = parseInt(el.getAttribute('data-count'), 10) || 0;
      const duration = 900;
      const start = performance.now();
      function tick(now) {
        const progress = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        el.textContent = Math.round(eased * target).toLocaleString();
        if (progress < 1) requestAnimationFrame(tick);
      }
      requestAnimationFrame(tick);
    };
    if ('IntersectionObserver' in window) {
      const cio = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            animateCount(entry.target);
            cio.unobserve(entry.target);
          }
        });
      }, { threshold: 0.4 });
      counters.forEach(function (el) { cio.observe(el); });
    } else {
      counters.forEach(animateCount);
    }
  }

  // ---------- Flash alerts -> auto-dismissing toasts ----------
  const flashAlerts = document.querySelectorAll('main .alert');
  if (flashAlerts.length) {
    let stack = document.querySelector('.toast-stack');
    if (!stack) {
      stack = document.createElement('div');
      stack.className = 'toast-stack';
      document.body.appendChild(stack);
    }
    flashAlerts.forEach(function (alertEl) {
      const isError = alertEl.classList.contains('alert-error');
      const toast = document.createElement('div');
      toast.className = 'toast ' + (isError ? 'toast-error' : 'toast-success');
      toast.textContent = alertEl.textContent;
      stack.appendChild(toast);
      alertEl.style.display = 'none';
      setTimeout(function () {
        toast.classList.add('hide');
        setTimeout(function () { toast.remove(); }, 300);
      }, 4500);
    });
  }

  // ---------- Mobile nav toggle (slide-in panel + overlay) ----------
  const navToggle = document.getElementById('navToggle');
  const navClose = document.getElementById('navClose');
  const navOverlay = document.getElementById('navOverlay');
  const mainNav = document.getElementById('mainNav');

  function openNav() {
    mainNav.classList.add('open');
    navOverlay.classList.add('open');
    navToggle.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
  }
  function closeNav() {
    mainNav.classList.remove('open');
    navOverlay.classList.remove('open');
    navToggle.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  }

  if (navToggle && mainNav) {
    navToggle.addEventListener('click', function () {
      mainNav.classList.contains('open') ? closeNav() : openNav();
    });
    if (navClose) navClose.addEventListener('click', closeNav);
    if (navOverlay) navOverlay.addEventListener('click', closeNav);
    // Close the panel when a nav link is tapped (mobile)
    mainNav.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', closeNav);
    });
    // Close on escape key
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeNav();
    });
  }

  // ---------- Photo preview on file input ----------
  document.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
    const previewId = input.getAttribute('data-preview');
    const preview = document.getElementById(previewId);
    input.addEventListener('change', function () {
      if (!preview) return;
      const file = input.files && input.files[0];
      if (!file) { preview.style.display = 'none'; return; }
      const reader = new FileReader();
      reader.onload = function (e) {
        preview.src = e.target.result;
        preview.style.display = 'block';
      };
      reader.readAsDataURL(file);
    });
  });

  // ---------- Confirm before destructive actions ----------
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      const msg = el.getAttribute('data-confirm') || 'Are you sure?';
      if (!confirm(msg)) e.preventDefault();
    });
  });

  // ---------- Hero search box feeds the main filter form (index.php) ----------
  const heroForm = document.getElementById('heroSearchForm');
  const heroKeyword = document.getElementById('heroKeyword');
  const keywordField = document.getElementById('keyword');

  if (heroForm && heroKeyword && keywordField) {
    heroForm.addEventListener('submit', function (e) {
      e.preventDefault();
      keywordField.value = heroKeyword.value;
      keywordField.dispatchEvent(new Event('input', { bubbles: true }));
      document.getElementById('itemResults').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  }

  // ---------- Live search / filter (index.php) ----------
  const filterForm = document.getElementById('filterForm');
  const resultsGrid = document.getElementById('itemResults');

  if (filterForm && resultsGrid) {
    let debounceTimer = null;
    const resultsCount = document.getElementById('resultsCount');

    function showSkeleton() {
      let html = '';
      for (let i = 0; i < 4; i++) {
        html +=
          '<div class="skeleton-card">' +
            '<div class="skeleton-block skeleton-photo"></div>' +
            '<div class="skeleton-lines">' +
              '<div class="skeleton-block" style="width:70%;"></div>' +
              '<div class="skeleton-block" style="width:45%;"></div>' +
              '<div class="skeleton-block" style="width:90%;"></div>' +
            '</div>' +
          '</div>';
      }
      resultsGrid.innerHTML = html;
    }

    function runSearch() {
      resultsGrid.setAttribute('aria-busy', 'true');
      showSkeleton();

      const params = new URLSearchParams(new FormData(filterForm));

      fetch('api/search.php?' + params.toString())
        .then(function (res) { return res.json(); })
        .then(function (data) {
          renderResults(data.items || []);
        })
        .catch(function () {
          resultsGrid.innerHTML = '<div class="empty-state"><p>Something went wrong loading results. Please try again.</p></div>';
        })
        .finally(function () {
          resultsGrid.removeAttribute('aria-busy');
        });
    }

    function escapeHtml(str) {
      const div = document.createElement('div');
      div.textContent = str || '';
      return div.innerHTML;
    }

    function renderResults(items) {
      if (resultsCount) {
        resultsCount.textContent = items.length + (items.length === 1 ? ' item found' : ' items found');
      }

      if (!items.length) {
        resultsGrid.innerHTML = '<div class="empty-state"><div class="icon">🔍</div><p>No items match your search.</p></div>';
        return;
      }

      resultsGrid.innerHTML = items.map(function (item, idx) {
        const photo = item.photo
          ? '<img class="item-photo" src="uploads/' + escapeHtml(item.photo) + '" alt="' + escapeHtml(item.item_name) + '">'
          : '<div class="item-photo placeholder">📦</div>';

        const badgeClass = 'badge badge-' + item.status.toLowerCase();
        const delay = Math.min(idx, 8) * 40;

        return (
          '<a class="item-card reveal in" style="transition-delay:' + delay + 'ms" href="item-details.php?id=' + item.id + '">' +
            photo +
            '<div class="item-body">' +
              '<div class="flex-between">' +
                '<h3>' + escapeHtml(item.item_name) + '</h3>' +
                '<span class="' + badgeClass + '">' + escapeHtml(item.status) + '</span>' +
              '</div>' +
              '<div class="item-meta">' + escapeHtml(item.category) + ' &middot; ' + escapeHtml(item.location) + '</div>' +
              '<div class="item-desc">' + escapeHtml(item.description) + '</div>' +
            '</div>' +
          '</a>'
        );
      }).join('');
    }

    filterForm.addEventListener('input', function () {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(runSearch, 350);
    });
    filterForm.addEventListener('change', runSearch);
    filterForm.addEventListener('submit', function (e) { e.preventDefault(); runSearch(); });
  }

  // ---------- Admin sidebar toggle (mobile off-canvas) ----------
  const adminSidebar = document.getElementById('adminSidebar');
  const adminSidebarToggle = document.getElementById('adminSidebarToggle');
  const adminSidebarOverlay = document.getElementById('adminSidebarOverlay');

  if (adminSidebar && adminSidebarToggle && adminSidebarOverlay) {
    function openAdminSidebar() {
      adminSidebar.classList.add('open');
      adminSidebarOverlay.classList.add('open');
    }
    function closeAdminSidebar() {
      adminSidebar.classList.remove('open');
      adminSidebarOverlay.classList.remove('open');
    }
    adminSidebarToggle.addEventListener('click', function () {
      adminSidebar.classList.contains('open') ? closeAdminSidebar() : openAdminSidebar();
    });
    adminSidebarOverlay.addEventListener('click', closeAdminSidebar);
  }

});