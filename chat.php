<?php
require_once 'includes/functions.php';
$user = require_login();
$me = (int)$user['id'];

$selPid   = (int)($_GET['product'] ?? 0);
$selOther = (int)($_GET['with'] ?? 0);
$ctx = ($selPid && $selOther) ? chat_context($selPid, $me, $selOther) : null;

// Conversations existantes
$s = db()->prepare('SELECT m.product_id,
        CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END AS other_id,
        MAX(m.id) AS last_id
    FROM messages m WHERE m.sender_id = ? OR m.receiver_id = ?
    GROUP BY m.product_id, other_id ORDER BY last_id DESC');
$s->execute([$me, $me, $me]);
$convs = $s->fetchAll();

$info = db()->prepare('SELECT p.title, p.image, u.name AS other_name,
    (SELECT body FROM messages WHERE id = ?) AS last_body,
    (SELECT COUNT(*) FROM messages WHERE product_id = ? AND sender_id = ? AND receiver_id = ? AND is_read = 0) AS unread
    FROM products p JOIN users u ON u.id = ? WHERE p.id = ?');
foreach ($convs as $k => &$c) {
    $info->execute([$c['last_id'], $c['product_id'], $c['other_id'], $me, $c['other_id'], $c['product_id']]);
    $row = $info->fetch();
    if (!$row) { unset($convs[$k]); continue; }
    $c += $row;
}
unset($c);

// Nouvelle conversation (depuis la fiche produit) non encore présente dans la liste
$found = false;
foreach ($convs as $c) if ((int)$c['product_id'] === $selPid && (int)$c['other_id'] === $selOther) $found = true;
if ($ctx && !$found) {
    $o = db()->prepare('SELECT name FROM users WHERE id = ?'); $o->execute([$selOther]);
    array_unshift($convs, ['product_id' => $selPid, 'other_id' => $selOther, 'title' => $ctx['title'],
        'other_name' => $o->fetchColumn(), 'last_body' => 'Nouvelle conversation', 'unread' => 0, 'image' => null]);
}

$pageTitle = 'Messages';
$extraScript = 'assets/js/chat.js';
include 'includes/header.php';
?>
<h1>Messages</h1>
<div class="chat-layout">
  <aside class="conv-list">
    <?php if (!$convs): ?><p class="muted pad">Aucune conversation. Ouvrez une annonce puis « Contacter le vendeur ».</p><?php endif; ?>
    <?php foreach ($convs as $c):
      $active = (int)$c['product_id'] === $selPid && (int)$c['other_id'] === $selOther; ?>
      <a class="conv <?= $active ? 'active' : '' ?>" href="chat.php?product=<?= (int)$c['product_id'] ?>&with=<?= (int)$c['other_id'] ?>">
        <strong><?= e($c['other_name']) ?></strong>
        <?php if (!empty($c['unread'])): ?><span class="badge"><?= (int)$c['unread'] ?></span><?php endif; ?>
        <span class="small muted ellipsis">📦 <?= e($c['title']) ?></span>
        <span class="small ellipsis"><?= e(mb_strimwidth($c['last_body'] ?? '', 0, 50, '…')) ?></span>
      </a>
    <?php endforeach; ?>
  </aside>

  <section class="thread">
  <?php if ($ctx):
    $o = db()->prepare('SELECT name FROM users WHERE id = ?'); $o->execute([$selOther]); ?>
    <div id="chat" data-me="<?= $me ?>" data-product="<?= $selPid ?>" data-with="<?= $selOther ?>">
      <header class="thread-head">
        <strong><?= e($o->fetchColumn()) ?></strong>
        <a href="product.php?id=<?= $selPid ?>">📦 <?= e($ctx['title']) ?></a>
      </header>
      <div class="messages" id="messages" aria-live="polite"></div>
      <form class="composer" id="composer" autocomplete="off">
        <input type="text" id="msg-input" maxlength="1000" placeholder="Écrivez votre message…" required>
        <button class="btn btn-primary" type="submit">Envoyer</button>
      </form>
    </div>
  <?php else: ?>
    <div class="empty"><p>Sélectionnez une conversation.</p></div>
  <?php endif; ?>
  </section>
</div>
<?php include 'includes/footer.php'; ?>
