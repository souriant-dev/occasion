<?php
require_once 'includes/functions.php';
$user = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'checkout') {
    csrf_check();
    $pdo = db();
    try {
        $pdo->beginTransaction();
        $s = $pdo->prepare('SELECT p.id, p.seller_id, p.title, p.price, p.status FROM cart_items c JOIN products p ON p.id = c.product_id WHERE c.user_id = ? FOR UPDATE');
        $s->execute([$user['id']]);
        $items = $s->fetchAll();
        if (!$items) { $pdo->rollBack(); flash('Votre panier est vide.', 'error'); redirect('cart.php'); }

        $bad = array_filter($items, fn($i) => $i['status'] !== 'available' || (int)$i['seller_id'] === (int)$user['id']);
        if ($bad) {
            $ids = array_column($bad, 'id');
            $in = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id IN ($in)")->execute(array_merge([$user['id']], $ids));
            $pdo->commit();
            flash('Certains articles ne sont plus disponibles et ont été retirés de votre panier.', 'error');
            redirect('cart.php');
        }

        $total = array_sum(array_column($items, 'price'));
        $pdo->prepare('INSERT INTO orders (buyer_id, total) VALUES (?, ?)')->execute([$user['id'], $total]);
        $orderId = (int)$pdo->lastInsertId();
        $ins = $pdo->prepare('INSERT INTO order_items (order_id, product_id, seller_id, title, price) VALUES (?,?,?,?,?)');
        $upd = $pdo->prepare("UPDATE products SET status = 'sold' WHERE id = ?");
        $del = $pdo->prepare('DELETE FROM cart_items WHERE product_id = ?');
        foreach ($items as $i) {
            $ins->execute([$orderId, $i['id'], $i['seller_id'], $i['title'], $i['price']]);
            $upd->execute([$i['id']]);
            $del->execute([$i['id']]); // retire l'article de tous les paniers
        }
        $pdo->commit();
        flash('Commande #' . $orderId . ' confirmée ! Contactez les vendeurs pour organiser la remise.', 'success');
        redirect('orders.php');
    } catch (PDOException $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash('Une erreur est survenue, réessayez.', 'error');
        redirect('cart.php');
    }
}

$s = db()->prepare("SELECT p.*, u.name AS seller_name FROM cart_items c JOIN products p ON p.id = c.product_id JOIN users u ON u.id = p.seller_id WHERE c.user_id = ? ORDER BY c.added_at DESC");
$s->execute([$user['id']]);
$items = $s->fetchAll();
$total = array_sum(array_map(fn($i) => $i['status'] === 'available' ? (int)$i['price'] : 0, $items));

$pageTitle = 'Mon panier';
include 'includes/header.php';
?>
<h1>Mon panier</h1>
<?php if (!$items): ?>
  <div class="empty"><p>Votre panier est vide.</p><a class="btn btn-primary" href="shop.php">Parcourir la boutique</a></div>
<?php else: ?>
<div class="cart">
  <div class="cart-list" id="cart-list">
    <?php foreach ($items as $i): ?>
      <div class="cart-row" data-row="<?= (int)$i['id'] ?>">
        <div class="thumb"><?= $i['image'] ? '<img src="uploads/' . e($i['image']) . '" alt="">' : '<div class="noimg">📦</div>' ?></div>
        <div class="grow">
          <a href="product.php?id=<?= (int)$i['id'] ?>"><strong><?= e($i['title']) ?></strong></a>
          <p class="muted small">Vendeur : <?= e($i['seller_name']) ?> · <?= e($i['item_condition']) ?></p>
          <?php if ($i['status'] !== 'available'): ?><span class="tag tag-sold">Plus disponible</span><?php endif; ?>
        </div>
        <div class="price"><?= price_fmt($i['price']) ?></div>
        <button class="btn btn-ghost btn-sm" data-remove-cart="<?= (int)$i['id'] ?>" aria-label="Retirer">✕</button>
      </div>
    <?php endforeach; ?>
  </div>
  <aside class="summary">
    <h3>Récapitulatif</h3>
    <p>Articles : <strong id="cart-n"><?= count($items) ?></strong></p>
    <p class="big">Total : <strong id="cart-total"><?= price_fmt($total) ?></strong></p>
    <p class="muted small">Paiement et remise en main propre à convenir avec chaque vendeur (via la messagerie).</p>
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="checkout">
      <button class="btn btn-primary btn-block" type="submit">Valider la commande</button>
    </form>
  </aside>
</div>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>
