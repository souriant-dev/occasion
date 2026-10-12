<?php
require_once __DIR__ . '/../includes/functions.php';
$me = api_user();
$meId = (int)$me['id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'unread') {
    json_out(['ok' => true, 'unread' => unread_count($meId)]);
}

$pid   = (int)($_GET['product'] ?? $_POST['product'] ?? 0);
$other = (int)($_GET['with'] ?? $_POST['with'] ?? 0);
if (!chat_context($pid, $meId, $other)) json_out(['ok' => false, 'error' => 'Conversation non autorisée.'], 403);

if ($action === 'send') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'error' => 'Méthode invalide.'], 405);
    csrf_check(true);
    $body = trim((string)($_POST['body'] ?? ''));
    if ($body === '' || mb_strlen($body) > 1000) json_out(['ok' => false, 'error' => 'Message vide ou trop long (1000 max).'], 422);
    $s = db()->prepare('INSERT INTO messages (product_id, sender_id, receiver_id, body) VALUES (?,?,?,?)');
    $s->execute([$pid, $meId, $other, $body]);
    json_out(['ok' => true, 'id' => (int)db()->lastInsertId()]);
}

if ($action === 'fetch') {
    $after = max(0, (int)($_GET['after'] ?? 0));
    $s = db()->prepare('SELECT id, sender_id, body, created_at FROM messages
        WHERE product_id = ? AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) AND id > ?
        ORDER BY id ASC LIMIT 200');
    $s->execute([$pid, $meId, $other, $other, $meId, $after]);
    $rows = $s->fetchAll();
    foreach ($rows as &$r) { $r['created_at'] = date('d/m H:i', strtotime($r['created_at'])); }
    unset($r);
    db()->prepare('UPDATE messages SET is_read = 1 WHERE product_id = ? AND sender_id = ? AND receiver_id = ? AND is_read = 0')
        ->execute([$pid, $other, $meId]);
    json_out(['ok' => true, 'messages' => $rows]);
}

json_out(['ok' => false, 'error' => 'Action inconnue.'], 400);
