<?php
require_once 'includes/functions.php';
$user = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pid = (int)($_POST['delete'] ?? 0);
    $s = db()->prepare("SELECT image FROM products WHERE id = ? AND seller_id = ? AND status = 'available'");
    $s->execute([$pid, $user['id']]);
    if ($row = $s->fetch()) {
        db()->prepare('DELETE FROM products WHERE id = ?')->execute([$pid]);
        if ($row['image']) @unlink(UPLOAD_DIR . basename($row['image']));
        flash('Annonce supprimée.', 'success');
    } else {
        flash('Suppression impossible (annonce vendue ou introuvable).', 'error');
    }
    redirect('my_products.php');
}

$s = db()->prepare(PRODUCT_SELECT . ' WHERE p.seller_id = ? GROUP BY p.id, u.name ORDER BY p.created_at DESC');
$s->execute([$user['id']]);
$mine = $s->fetchAll();

$pageTitle = 'Mes annonces';
include 'includes/header.php';
?>
<div class="page-head"><h1>Mes annonces</h1><a class="btn btn-primary" href="sell.php">+ Nouvelle annonce</a></div>
<?php if (!$mine): ?>
  <div class="empty"><p>Vous n'avez publié aucune annonce.</p></div>
<?php else: ?>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Article</th><th>Prix</th><th>Note vendeur</th><th>Avis acheteurs</th><th>Statut</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($mine as $p): ?>
    <tr>
      <td><a href="product.php?id=<?= (int)$p['id'] ?>"><?= e($p['title']) ?></a></td>
      <td><?= price_fmt($p['price']) ?></td>
      <td><?= $p['seller_stars'] !== null ? stars_html($p['seller_stars']) : '—' ?></td>
      <td><?= stars_html($p['avg_stars'], $p['n_ratings']) ?></td>
      <td><?= $p['status'] === 'sold' ? '<span class="tag tag-sold">Vendu</span>' : '<span class="tag tag-ok">En ligne</span>' ?></td>
      <td>
        <?php if ($p['status'] === 'available'): ?>
        <form method="post" class="inline" onsubmit="return confirm('Supprimer cette annonce ?')">
          <?= csrf_field() ?><input type="hidden" name="delete" value="<?= (int)$p['id'] ?>">
          <button class="btn btn-danger btn-sm" type="submit">Supprimer</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>
