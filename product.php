<?php
require_once 'includes/functions.php';
$user = require_login();

$id = (int)($_GET['id'] ?? 0);
$s = db()->prepare(PRODUCT_SELECT . ' WHERE p.id = ? GROUP BY p.id, u.name');
$s->execute([$id]);
$p = $s->fetch();
if (!$p) { http_response_code(404); flash('Article introuvable.', 'error'); redirect('shop.php'); }

$isOwner = (int)$p['seller_id'] === (int)$user['id'];
$available = $p['status'] === 'available';

$s = db()->prepare('SELECT stars FROM ratings WHERE user_id = ? AND product_id = ?');
$s->execute([$user['id'], $id]);
$myStars = (int)$s->fetchColumn();

$s = db()->prepare('SELECT 1 FROM cart_items WHERE user_id = ? AND product_id = ?');
$s->execute([$user['id'], $id]);
$inCart = (bool)$s->fetch();

$s = db()->prepare('SELECT COALESCE(AVG(r.stars),0) a, COUNT(r.id) n FROM ratings r JOIN products p ON p.id = r.product_id WHERE p.seller_id = ?');
$s->execute([$p['seller_id']]);
$sellerStats = $s->fetch();

$pageTitle = $p['title'];
include 'includes/header.php';
?>
<p><a href="shop.php">← Retour à la boutique</a></p>
<article class="product">
  <div class="product-img">
    <?php if ($p['image']): ?><img src="uploads/<?= e($p['image']) ?>" alt="<?= e($p['title']) ?>">
    <?php else: ?><div class="noimg big">📦</div><?php endif; ?>
  </div>
  <div class="product-info">
    <h1><?= e($p['title']) ?> <?= $available ? '' : '<span class="tag tag-sold">Vendu</span>' ?></h1>
    <p class="price big"><?= price_fmt($p['price']) ?></p>
    <p><span id="avg-stars"><?= stars_html($p['avg_stars']) ?></span>
       <small class="muted" id="avg-text"><?= $p['n_ratings'] ? number_format($p['avg_stars'], 1, ',', '') . '/5 · ' . (int)$p['n_ratings'] . ' avis' : 'Pas encore noté' ?></small></p>
    <ul class="meta">
      <li><strong>Catégorie :</strong> <?= e($p['category']) ?></li>
      <li><strong>État :</strong> <?= e($p['item_condition']) ?></li>
      <li><strong>Publié le :</strong> <?= e(date('d/m/Y', strtotime($p['created_at']))) ?></li>
      <li><strong>Vendeur :</strong> <?= e($p['seller_name']) ?>
        <?php if ($sellerStats['n']): ?>· <?= stars_html($sellerStats['a']) ?><?php endif; ?></li>
    </ul>
    <h3>Description</h3>
    <p class="desc"><?= nl2br(e($p['description'])) ?></p>

    <div class="actions">
      <?php if ($isOwner): ?>
        <span class="muted">Ceci est votre annonce.</span>
        <a class="btn btn-ghost" href="my_products.php">Gérer mes annonces</a>
      <?php elseif ($available): ?>
        <button class="btn btn-primary" data-add-cart="<?= $id ?>" <?= $inCart ? 'disabled' : '' ?>>
          <?= $inCart ? 'Dans le panier ✓' : '🛒 Ajouter au panier' ?></button>
        <a class="btn btn-light" href="chat.php?product=<?= $id ?>&with=<?= (int)$p['seller_id'] ?>">💬 Contacter le vendeur</a>
      <?php else: ?>
        <span class="muted">Cet article n'est plus disponible.</span>
      <?php endif; ?>
    </div>

    <?php if (!$isOwner): ?>
    <div class="rate-box">
      <strong>Votre note :</strong>
      <span class="rate-input" data-rate="<?= $id ?>" data-mine="<?= $myStars ?>" role="radiogroup" aria-label="Noter ce produit">
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <button type="button" data-v="<?= $i ?>" aria-label="<?= $i ?> étoile<?= $i > 1 ? 's' : '' ?>">★</button>
        <?php endfor; ?>
      </span>
      <small class="muted" id="my-rating-text"><?= $myStars ? 'Vous avez donné ' . $myStars . '/5' : 'Cliquez sur une étoile' ?></small>
    </div>
    <?php endif; ?>
  </div>
</article>
<?php include 'includes/footer.php'; ?>
