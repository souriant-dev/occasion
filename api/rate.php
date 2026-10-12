<?php
require_once __DIR__ . '/../includes/functions.php';
$me = api_user();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'error' => 'Méthode invalide.'], 405);
csrf_check(true);

$pid = (int)($_POST['product_id'] ?? 0);
$stars = (int)($_POST['stars'] ?? 0);
if ($stars < 1 || $stars > 5) json_out(['ok' => false, 'error' => 'Note invalide (1 à 5).'], 422);

$s = db()->prepare('SELECT seller_id FROM products WHERE id = ?');
$s->execute([$pid]);
$p = $s->fetch();
if (!$p) json_out(['ok' => false, 'error' => 'Article introuvable.'], 404);
if ((int)$p['seller_id'] === (int)$me['id']) json_out(['ok' => false, 'error' => 'Vous ne pouvez pas noter votre propre article.'], 422);

db()->prepare('INSERT INTO ratings (user_id, product_id, stars) VALUES (?,?,?) ON DUPLICATE KEY UPDATE stars = VALUES(stars)')
    ->execute([$me['id'], $pid, $stars]);

$s = db()->prepare('SELECT AVG(stars) a, COUNT(*) n FROM ratings WHERE product_id = ?');
$s->execute([$pid]);
$r = $s->fetch();
json_out(['ok' => true, 'avg' => round((float)$r['a'], 1), 'count' => (int)$r['n'], 'mine' => $stars]);
