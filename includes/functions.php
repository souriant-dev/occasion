<?php
require_once __DIR__ . '/../config.php';

function db(): PDO {
    static $pdo = null;
    if (!$pdo) {
        try {
            $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            exit('Connexion à la base impossible : vérifiez config.php et importez database.sql.');
        }
    }
    return $pdo;
}

function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function redirect(string $url): void { header('Location: ' . $url); exit; }

function flash(?string $msg = null, string $type = 'info') {
    if ($msg !== null) { $_SESSION['flash'][] = ['m' => $msg, 't' => $type]; return null; }
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(bool $json = false): void {
    $t = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)$t)) {
        if ($json) json_out(['ok' => false, 'error' => 'Session expirée, rechargez la page.'], 419);
        http_response_code(419);
        exit('Jeton CSRF invalide. Rechargez la page.');
    }
}

function current_user(): ?array {
    static $u = false;
    if ($u !== false) return $u;
    if (empty($_SESSION['uid'])) return $u = null;
    $s = db()->prepare('SELECT id, name, email FROM users WHERE id = ?');
    $s->execute([$_SESSION['uid']]);
    $u = $s->fetch() ?: null;
    if (!$u) unset($_SESSION['uid']);
    return $u;
}

function require_login(): array {
    $u = current_user();
    if (!$u) {
        $next = basename($_SERVER['SCRIPT_NAME']) . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
        redirect('login.php?next=' . urlencode($next));
    }
    return $u;
}

function safe_next(?string $n): string {
    return ($n && preg_match('/^[a-z_]+\.php(\?[A-Za-z0-9_=&%.\-]*)?$/', $n)) ? $n : 'shop.php';
}

function json_out(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function api_user(): array {
    $u = current_user();
    if (!$u) json_out(['ok' => false, 'error' => 'Connexion requise.'], 401);
    return $u;
}

function price_fmt($n): string { return number_format((int)$n, 0, ',', ' ') . ' ' . CURRENCY; }

function stars_html($avg, $n = null): string {
    $avg = round((float)$avg, 1);
    $h = '<span class="stars" style="--rating:' . $avg . '" title="' . $avg . '/5">★★★★★</span>';
    if ($n !== null) $h .= ' <small class="muted">(' . (int)$n . ')</small>';
    return $h;
}

function cart_count(int $uid): int {
    $s = db()->prepare('SELECT COUNT(*) FROM cart_items WHERE user_id = ?');
    $s->execute([$uid]);
    return (int)$s->fetchColumn();
}

function unread_count(int $uid): int {
    $s = db()->prepare('SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0');
    $s->execute([$uid]);
    return (int)$s->fetchColumn();
}

/** Enregistre une image envoyée. Retourne [nom_fichier|null, erreur|null]. */
function save_image(array $f): array {
    if (!isset($f['error']) || $f['error'] === UPLOAD_ERR_NO_FILE) return [null, null];
    if ($f['error'] !== UPLOAD_ERR_OK) return [null, "Échec du téléversement de l'image."];
    if ($f['size'] > MAX_IMG_SIZE) return [null, 'Image trop lourde (3 Mo maximum).'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext || !@getimagesize($f['tmp_name'])) return [null, 'Format invalide (JPG, PNG ou WebP uniquement).'];
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    $name = bin2hex(random_bytes(12)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], UPLOAD_DIR . $name)) return [null, "Impossible d'enregistrer l'image."];
    return [$name, null];
}

/** Vérifie qu'une conversation (produit + interlocuteur) est autorisée. */
function chat_context(int $pid, int $me, int $other): ?array {
    if ($me === $other || $pid <= 0 || $other <= 0) return null;
    $s = db()->prepare('SELECT id, seller_id, title FROM products WHERE id = ?');
    $s->execute([$pid]);
    $p = $s->fetch();
    if (!$p) return null;
    $sid = (int)$p['seller_id'];
    if ($sid !== $me && $sid !== $other) return null;
    $s = db()->prepare('SELECT id FROM users WHERE id = ?');
    $s->execute([$other]);
    if (!$s->fetch()) return null;
    if ($sid === $me) { // le vendeur ne peut répondre qu'à quelqu'un qui lui a écrit
        $s = db()->prepare('SELECT 1 FROM messages WHERE product_id = ? AND sender_id = ? AND receiver_id = ? LIMIT 1');
        $s->execute([$pid, $other, $me]);
        if (!$s->fetch()) return null;
    }
    return $p;
}

function render_card(array $p): string {
    $img = $p['image']
        ? '<img src="uploads/' . e($p['image']) . '" alt="' . e($p['title']) . '" loading="lazy">'
        : '<div class="noimg">📦</div>';
    $sold = $p['status'] === 'sold' ? '<span class="tag tag-sold">Vendu</span>' : '';
    return '<a class="card" href="product.php?id=' . (int)$p['id'] . '">'
        . '<div class="card-img">' . $img . $sold . '</div>'
        . '<div class="card-body"><h3>' . e($p['title']) . '</h3>'
        . '<p class="price">' . price_fmt($p['price']) . '</p>'
        . '<p>' . stars_html($p['avg_stars'], $p['n_ratings']) . '</p>'
        . '<p class="muted small">' . e($p['item_condition']) . ' · ' . e($p['seller_name']) . '</p></div></a>';
}

const PRODUCT_SELECT = "SELECT p.*, u.name AS seller_name, COALESCE(AVG(r.stars),0) AS avg_stars, COUNT(r.id) AS n_ratings
    FROM products p JOIN users u ON u.id = p.seller_id LEFT JOIN ratings r ON r.product_id = p.id";
