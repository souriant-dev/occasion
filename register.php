<?php
require_once 'includes/functions.php';
if (current_user()) redirect('shop.php');

$errors = []; $name = ''; $email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name  = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pw    = $_POST['password'] ?? '';
    $pw2   = $_POST['password2'] ?? '';

    if (mb_strlen($name) < 2 || mb_strlen($name) > 60) $errors[] = 'Le nom doit contenir entre 2 et 60 caractères.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) $errors[] = 'Adresse e-mail invalide.';
    if (strlen($pw) < 8) $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
    if ($pw !== $pw2) $errors[] = 'Les mots de passe ne correspondent pas.';

    if (!$errors) {
        $s = db()->prepare('SELECT id FROM users WHERE email = ?');
        $s->execute([$email]);
        if ($s->fetch()) {
            $errors[] = 'Un compte existe déjà avec cet e-mail.';
        } else {
            $s = db()->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
            $s->execute([$name, $email, password_hash($pw, PASSWORD_DEFAULT)]);
            session_regenerate_id(true);
            $_SESSION['uid'] = (int)db()->lastInsertId();
            flash('Bienvenue sur ' . SITE_NAME . ' !', 'success');
            redirect('shop.php');
        }
    }
}
$pageTitle = 'Créer un compte';
include 'includes/header.php';
?>
<section class="auth-box">
  <h1>Créer un compte</h1>
  <p class="muted">Un compte est nécessaire pour accéder à la plateforme.</p>
  <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post" class="form" novalidate>
    <?= csrf_field() ?>
    <label>Nom complet<input name="name" required maxlength="60" value="<?= e($name) ?>"></label>
    <label>E-mail<input type="email" name="email" required value="<?= e($email) ?>"></label>
    <label>Mot de passe (8 caractères min.)<input type="password" name="password" required minlength="8"></label>
    <label>Confirmer le mot de passe<input type="password" name="password2" required></label>
    <button class="btn btn-primary btn-block" type="submit">S'inscrire</button>
  </form>
  <p class="center">Déjà inscrit ? <a href="login.php">Se connecter</a></p>
</section>
<?php include 'includes/footer.php'; ?>
