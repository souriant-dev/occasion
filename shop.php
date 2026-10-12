<?php
require_once 'includes/functions.php';
$user = require_login();

$q    = trim($_GET['q'] ?? '');
$cat  = $_GET['cat'] ?? '';
$sort = $_GET['sort'] ?? 'recent';
$min  = ($_GET['min'] ?? '') !== '' ? max(0, (int)$_GET['min']) : null;
$max  = ($_GET['max'] ?? '') !== '' ? max(0, (int)$_GET['max']) : null;
$page = max(1, (int)($_GET['page'] ?? 1));
$per  = 12;

$where = ["p.status = 'available'"]; $params = [];
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = '(p.title LIKE ? OR p.description LIKE ?)';
    array_push($params, $like, $like);
}
if (in_array($cat, CATEGORIES, true)) { $where[] = 'p.category = ?'; $params[] = $cat; } else { $cat = ''; }
if ($min !== null) { $where[] = 'p.price >= ?'; $params[] = $min; }
if ($max !== null) { $where[] = 'p.price <= ?'; $params[] = $max; }
$w = implode(' AND ', $where);

$orders = [
    'recent'     => 'p.created_at DESC',
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'rating'     => 'avg_stars DESC, n_ratings DESC',
];
if (!isset($orders[$sort])) $sort = 'recent';

$s = db()->prepare("SELECT COUNT(*) FROM products p WHERE $w");
$s->execute($params);
$total = (int)$s->fetchColumn();
$pages = max(1, (int)ceil($total / $per));
$page  = min($page, $pages);
$offset = ($page - 1) * $per;

$s = db()->prepare(PRODUCT_SELECT . " WHERE $w GROUP BY p.id, u.name ORDER BY {$orders[$sort]} LIMIT $per OFFSET $offset");
$s->execute($params);
$products = $s->fetchAll();

$qs = function (array $over = []) {
    return '?' . http_build_query(array_filter(array_merge($_GET, $over), fn($v) => $v !== '' && $v !== null));
};

$pageTitle = 'Boutique';
include 'includes/header.php';
?>
<h1>Boutique</h1>
<form class="filters" method="get">
  <input type="hidden" name="q" value="<?= e($q) ?>">
  <select name="cat">
    <option value="">Toutes les catégories</option>
    <?php foreach (CATEGORIES as $c): ?><option <?= $c === $cat ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
  </select>
  <input type="number" name="min" min="0" placeholder="Prix min" value="<?= $min ?>">
  <input type="number" name="max" min="0" placeholder="Prix max" value="<?= $max ?>">
  <select name="sort">
    <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>>Plus récents</option>
    <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Prix croissant</option>
    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Prix décroissant</option>
    <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Mieux notés</option>
  </select>
  <button class="btn btn-primary" type="submit">Filtrer</button>
  <a class="btn btn-ghost" href="shop.php">Réinitialiser</a>
</form>

<p class="muted"><?= $total ?> résultat<?= $total > 1 ? 's' : '' ?><?= $q !== '' ? ' pour « ' . e($q) . ' »' : '' ?></p>

<?php if (!$products): ?>
  <div class="empty"><p>Aucun article ne correspond à votre recherche.</p><a class="btn btn-primary" href="sell.php">Publier une annonce</a></div>
<?php else: ?>
  <div class="grid"><?php foreach ($products as $p) echo render_card($p); ?></div>
  <?php if ($pages > 1): ?>
  <nav class="pagination">
    <?php if ($page > 1): ?><a href="<?= e($qs(['page' => $page - 1])) ?>">← Précédent</a><?php endif; ?>
    <span>Page <?= $page ?> / <?= $pages ?></span>
    <?php if ($page < $pages): ?><a href="<?= e($qs(['page' => $page + 1])) ?>">Suivant →</a><?php endif; ?>
  </nav>
  <?php endif; ?>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>
