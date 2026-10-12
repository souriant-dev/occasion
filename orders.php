<?php
require_once 'includes/functions.php';
$user = require_login();

$s = db()->prepare('SELECT * FROM orders WHERE buyer_id = ? ORDER BY created_at DESC');
$s->execute([$user['id']]);
$orders = $s->fetchAll();

$itemsStmt = db()->prepare('SELECT oi.*, u.name AS seller_name FROM order_items oi JOIN users u ON u.id = oi.seller_id WHERE oi.order_id = ?');

$s = db()->prepare('SELECT oi.title, oi.price, oi.product_id, o.created_at, o.buyer_id, u.name AS buyer_name
    FROM order_items oi JOIN orders o ON o.id = oi.order_id JOIN users u ON u.id = o.buyer_id
    WHERE oi.seller_id = ? ORDER BY o.created_at DESC');
$s->execute([$user['id']]);
$sales = $s->fetchAll();

$pageTitle = 'Commandes';
include 'includes/header.php';
?>
<h1>Mes achats</h1>
<?php if (!$orders): ?>
  <div class="empty"><p>Aucun achat pour le moment.</p></div>
<?php endif; ?>
<?php foreach ($orders as $o): $itemsStmt->execute([$o['id']]); $its = $itemsStmt->fetchAll(); ?>
  <section class="order">
    <header><strong>Commande #<?= (int)$o['id'] ?></strong> · <?= e(date('d/m/Y H:i', strtotime($o['created_at']))) ?>
      <span class="grow"></span><strong><?= price_fmt($o['total']) ?></strong></header>
    <ul>
      <?php foreach ($its as $i): ?>
      <li><span class="grow"><?= $i['product_id'] ? '<a href="product.php?id=' . (int)$i['product_id'] . '">' . e($i['title']) . '</a>' : e($i['title']) ?>
          <small class="muted">— vendeur : <?= e($i['seller_name']) ?></small></span>
        <?= price_fmt($i['price']) ?>
        <?php if ($i['product_id']): ?><a class="btn btn-light btn-sm" href="chat.php?product=<?= (int)$i['product_id'] ?>&with=<?= (int)$i['seller_id'] ?>">💬 Vendeur</a><?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ul>
  </section>
<?php endforeach; ?>

<h1 class="mt">Mes ventes</h1>
<?php if (!$sales): ?>
  <div class="empty"><p>Aucune vente pour le moment.</p></div>
<?php else: ?>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Article</th><th>Prix</th><th>Acheteur</th><th>Date</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($sales as $v): ?>
    <tr>
      <td><?= e($v['title']) ?></td><td><?= price_fmt($v['price']) ?></td><td><?= e($v['buyer_name']) ?></td>
      <td><?= e(date('d/m/Y', strtotime($v['created_at']))) ?></td>
      <td><?php if ($v['product_id']): ?><a class="btn btn-light btn-sm" href="chat.php?product=<?= (int)$v['product_id'] ?>&with=<?= (int)$v['buyer_id'] ?>">💬 Acheteur</a><?php endif; ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>
