</main>
<footer class="footer">
  <div class="container">© <?= date('Y') ?> <?= e(SITE_NAME) ?> — Donnez une seconde vie à vos objets.</div>
</footer>
<script src="assets/js/app.js"></script>
<?php if (!empty($extraScript)): ?><script src="<?= e($extraScript) ?>"></script><?php endif; ?>
</body>
</html>
