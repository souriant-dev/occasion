<?php
require_once __DIR__ . '/functions.php';
$user = current_user();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($pageTitle ?? 'Accueil') . ' – ' . SITE_NAME) ?></title>
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<link rel="stylesheet" href="assets/css/style.css?v=3">
</head>
<body>
<header class="topbar">
  <div class="container nav">
    <a class="brand" href="<?= $user ? 'shop.php' : 'index.php' ?>">♻️ <?= e(SITE_NAME) ?></a>
    <?php if ($user): ?>
    <form class="search" action="shop.php" method="get">
      <input type="search" name="q" placeholder="Rechercher un objet…" value="<?= e($_GET['q'] ?? '') ?>">
      <button type="submit" aria-label="Rechercher">🔍</button>
    </form>
    <?php endif; ?>
    <button class="burger" id="burger" aria-label="Menu">☰</button>
    <nav id="menu">
      <?php if ($user):
        $cc = cart_count($user['id']);
        $uc = unread_count($user['id']);
        $nameParts = preg_split('/\s+/u', trim($user['name']), -1, PREG_SPLIT_NO_EMPTY);
        $initials = mb_strtoupper(
            mb_substr($nameParts[0], 0, 1, 'UTF-8') .
            (count($nameParts) > 1
                ? mb_substr($nameParts[count($nameParts) - 1], 0, 1, 'UTF-8')
                : mb_substr($nameParts[0], 1, 1, 'UTF-8')),
            'UTF-8'
        );
      ?>
        <a href="shop.php">Boutique</a>
        <a href="sell.php">Vendre</a>
        <a href="my_products.php">Mes annonces</a>
        <a href="chat.php">Messages <span class="badge" id="badge-msg" <?= $uc ? '' : 'hidden' ?>><?= $uc ?></span></a>
        <a href="cart.php">Panier <span class="badge" id="badge-cart" <?= $cc ? '' : 'hidden' ?>><?= $cc ?></span></a>
        <a href="orders.php">Commandes</a>
        <form method="post" action="logout.php" class="inline">
          <?= csrf_field() ?>
          <button class="btn user-avatar" type="submit" aria-label="Déconnexion de <?= e($user['name']) ?>" title="Déconnexion (<?= e($user['name']) ?>)"><?= e($initials) ?></button>
        </form>
      <?php else: ?>
        <a href="login.php">Connexion</a>
        <a class="btn btn-primary btn-sm" href="register.php">Créer un compte</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="container">
<?php foreach (flash() as $flashMsg): ?>
  <div class="alert alert-<?= e($flashMsg['t']) ?>"><?= e($flashMsg['m']) ?></div>
<?php endforeach; ?>
