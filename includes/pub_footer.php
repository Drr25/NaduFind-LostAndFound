</main>

    <footer class="pf-foot">&copy; <?= date('Y') ?> <?= clean($pfSite) ?> &middot; Helping lost items find their way home.</footer>
  </div><!-- /.admin-content -->
</div><!-- /.admin-shell -->

<script>
(function () {
  // mobile sidebar toggle
  var sb = document.getElementById('pfSidebar');
  var ov = document.getElementById('pfOverlay');
  var tg = document.getElementById('pfToggle');
  function setOpen(open) {
    sb.classList.toggle('open', open);
    ov.classList.toggle('open', open);
  }
  if (tg) tg.addEventListener('click', function () { setOpen(!sb.classList.contains('open')); });
  if (ov) ov.addEventListener('click', function () { setOpen(false); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });

  // photo preview for inputs with data-preview
  document.querySelectorAll('input[type=file][data-preview]').forEach(function (inp) {
    inp.addEventListener('change', function () {
      var img = document.getElementById(inp.dataset.preview);
      if (!img) return;
      if (inp.files && inp.files[0]) {
        img.src = URL.createObjectURL(inp.files[0]);
        img.style.display = 'block';
      } else {
        img.style.display = 'none';
      }
    });
  });
})();
</script>
</body>
</html>