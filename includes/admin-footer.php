</main>
  </div><!-- /admin-content -->
</div><!-- /admin-shell -->

<script>
(function () {
  // Mobile sidebar
  var sb = document.getElementById('adminSidebar'),
      ov = document.getElementById('adminOverlay'),
      tg = document.getElementById('adminToggle');
  function setOpen(open) {
    sb.classList.toggle('open', open);
    ov.classList.toggle('open', open);
    tg.setAttribute('aria-expanded', open ? 'true' : 'false');
    document.body.style.overflow = open ? 'hidden' : '';
  }
  if (sb && ov && tg) {
    tg.addEventListener('click', function () { setOpen(!sb.classList.contains('open')); });
    ov.addEventListener('click', function () { setOpen(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });
  }
  // Confirm dialogs for forms with data-confirm="..."
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!confirm(f.getAttribute('data-confirm'))) e.preventDefault();
    });
  });
})();
</script>
</body>
</html>