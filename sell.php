<?php
require_once 'includes/functions.php';
$user = require_login();

$errors = [];
$f = ['title' => '', 'description' => '', 'price' => '', 'category' => '', 'item_condition' => '', 'seller_stars' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($f as $k => $_) $f[$k] = trim($_POST[$k] ?? '');

    if (mb_strlen($f['title']) < 3 || mb_strlen($f['title']) > 120) $errors[] = 'Le titre doit contenir entre 3 et 120 caractères.';
    if (mb_strlen($f['description']) < 10 || mb_strlen($f['description']) > 3000) $errors[] = 'La description doit contenir entre 10 et 3000 caractères.';
    if (!ctype_digit($f['price']) || (int)$f['price'] < 1 || (int)$f['price'] > 100000000) $errors[] = 'Prix invalide (nombre entier positif).';
    if (!in_array($f['category'], CATEGORIES, true)) $errors[] = 'Catégorie invalide.';
    if (!in_array($f['item_condition'], CONDITIONS, true)) $errors[] = 'État invalide.';
    if (!ctype_digit($f['seller_stars']) || (int)$f['seller_stars'] < 1 || (int)$f['seller_stars'] > 5) $errors[] = 'Choisissez une note de 1 à 5 étoiles.';

    [$img, $imgErr] = save_image($_FILES['image'] ?? []);
    if ($imgErr) $errors[] = $imgErr;

    if (!$errors) {
        $s = db()->prepare('INSERT INTO products (seller_id, title, description, price, category, item_condition, image, seller_stars) VALUES (?,?,?,?,?,?,?,?)');
        $s->execute([$user['id'], $f['title'], $f['description'], (int)$f['price'], $f['category'], $f['item_condition'], $img, (int)$f['seller_stars']]);
        flash('Votre annonce est en ligne !', 'success');
        redirect('product.php?id=' . db()->lastInsertId());
    } elseif (!empty($img)) {
        @unlink(UPLOAD_DIR . $img); // on retire l'image si le formulaire est invalide
    }
}
$pageTitle = 'Publier une annonce';
include 'includes/header.php';
?>
<h1>Publier une annonce</h1>
<?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
<form method="post" enctype="multipart/form-data" class="form form-wide">
  <?= csrf_field() ?>
  <label>Titre<input name="title" required maxlength="120" value="<?= e($f['title']) ?>"></label>
  <label>Description<textarea name="description" rows="6" required maxlength="3000"><?= e($f['description']) ?></textarea></label>
  <div class="row">
    <label>Prix (<?= e(CURRENCY) ?>)<input type="number" name="price" min="1" step="1" required value="<?= e($f['price']) ?>"></label>
    <label>Catégorie
      <select name="category" required>
        <option value="">— Choisir —</option>
        <?php foreach (CATEGORIES as $c): ?><option <?= $c === $f['category'] ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
      </select></label>
    <label>État
      <select name="item_condition" required>
        <option value="">— Choisir —</option>
        <?php foreach (CONDITIONS as $c): ?><option <?= $c === $f['item_condition'] ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
      </select></label>
  </div>
  <fieldset class="seller-rating">
    <legend>Notez votre article (obligatoire)</legend>
    <div class="seller-rating-options">
      <?php for ($i = 1; $i <= 5; $i++): ?>
        <label class="seller-rating-option" title="<?= $i ?> étoile<?= $i > 1 ? 's' : '' ?>">
          <input type="radio" name="seller_stars" value="<?= $i ?>" aria-label="<?= $i ?> étoile<?= $i > 1 ? 's' : '' ?>" required <?= $f['seller_stars'] === (string)$i ? 'checked' : '' ?>>
          <span aria-hidden="true">★</span>
        </label>
      <?php endfor; ?>
    </div>
  </fieldset>
  <label>Photo (JPG, PNG ou WebP, 3 Mo max)
    <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/webp"></label>
  <img id="preview" class="preview" alt="" hidden>
  <button class="btn btn-primary" type="submit">Publier l'annonce</button>
</form>
<?php include 'includes/footer.php'; ?>
