<?php
require_once __DIR__ . '/../includes/functions.php';
$me = api_user();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'error' => 'Méthode invalide.'], 405);
csrf_check(true);

$action = $_POST['action'] ?? '';
$pid = (int)($_POST['product_id'] ?? 0);

if ($action === 'add') {
    $s = db()->prepare('SELECT seller_id, status FROM products WHERE id = ?');
    $s->execute([$pid]);
    $p = $s->fetch();
    if (!$p) json_out(['ok' => false, 'error' => 'Article introuvable.'], 404);
    if ((int)$p['seller_id'] === (int)$me['id']) json_out(['ok' => false, 'error' => 'Vous ne pouvez pas acheter votre propre article.'], 422);
    if ($p['status'] !== 'available') json_out(['ok' => false, 'error' => "Cet article n'est plus disponible."], 422);
    db()->prepare('INSERT IGNORE INTO cart_items (user_id, product_id) VALUES (?, ?)')->execute([$me['id'], $pid]);
} elseif ($action === 'remove') {
    db()->prepare('DELETE FROM cart_items WHERE user_id = ? AND product_id = ?')->execute([$me['id'], $pid]);
} else {
    json_out(['ok' => false, 'error' => 'Action inconnue.'], 400);
}

$s = db()->prepare("SELECT COUNT(*) n, COALESCE(SUM(CASE WHEN p.status='available' THEN p.price ELSE 0 END),0) t
    FROM cart_items c JOIN products p ON p.id = c.product_id WHERE c.user_id = ?");
$s->execute([$me['id']]);
$r = $s->fetch();
json_out(['ok' => true, 'count' => (int)$r['n'], 'total' => price_fmt($r['t'])]);
