<?php
require_once 'includes/functions.php';
if (current_user()) redirect('shop.php');

$latest = db()->query(PRODUCT_SELECT . " WHERE p.status = 'available' GROUP BY p.id, u.name ORDER BY p.created_at DESC LIMIT 8")->fetchAll();
$total  = (int)db()->query("SELECT COUNT(*) FROM products WHERE status = 'available'")->fetchColumn();
$pageTitle = 'Achetez et vendez d\'occasion';
include 'includes/header.php';
?>
<section class="hero">
  <h1>Donnez une seconde vie à vos objets</h1>
  <p>Achetez malin, vendez facilement. Rejoignez la communauté <?= e(SITE_NAME) ?>.</p>
  <div class="hero-actions">
    <a class="btn btn-primary" href="register.php">Créer un compte gratuit</a>
    <a class="btn btn-light" href="login.php">Se connecter</a>
  </div>
  <p class="small"><?= $total ?> article<?= $total > 1 ? 's' : '' ?> disponible<?= $total > 1 ? 's' : '' ?> en ce moment</p>
</section>

<section class="steps">
  <div><span>1</span><h3>Créez votre compte</h3><p>Inscription gratuite en quelques secondes.</p></div>
  <div><span>2</span><h3>Trouvez ou publiez</h3><p>Parcourez les annonces ou mettez les vôtres en ligne.</p></div>
  <div><span>3</span><h3>Discutez &amp; achetez</h3><p>Échangez avec le vendeur, remplissez votre panier.</p></div>
  <div><span>4</span><h3>Notez</h3><p>Donnez de 1 à 5 étoiles aux articles.</p></div>
</section>

<section>
  <h2>Dernières annonces</h2>
  <?php if (!$latest): ?>
    <p class="muted">Aucune annonce pour le moment. Soyez le premier à publier !</p>
  <?php else: ?>
    <p class="muted">Connectez-vous pour voir le détail, contacter les vendeurs et acheter.</p>
    <div class="grid">
      <?php foreach ($latest as $p):
        $img = $p['image'] ? '<img src="uploads/' . e($p['image']) . '" alt="" loading="lazy">' : '<div class="noimg">📦</div>'; ?>
        <a class="card" href="login.php?next=<?= urlencode('product.php?id=' . (int)$p['id']) ?>">
          <div class="card-img"><?= $img ?></div>
          <div class="card-body">
            <h3><?= e($p['title']) ?></h3>
            <p class="price"><?= price_fmt($p['price']) ?></p>
            <?php if ($p['seller_stars'] !== null): ?><p><?= seller_stars_html($p['seller_stars']) ?></p><?php endif; ?>
            <p class="small"><span class="muted">Avis acheteurs :</span> <?= stars_html($p['avg_stars'], $p['n_ratings']) ?></p>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php include 'includes/footer.php'; ?>
