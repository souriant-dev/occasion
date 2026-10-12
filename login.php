<?php
require_once 'includes/functions.php';
if (current_user()) redirect('shop.php');

$error = ''; $email = '';
$next = safe_next($_GET['next'] ?? $_POST['next'] ?? '');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $s = db()->prepare('SELECT id, password_hash FROM users WHERE email = ?');
    $s->execute([$email]);
    $u = $s->fetch();
    // Comparaison faite même si l'utilisateur n'existe pas (message d'erreur identique)
    $hash = $u['password_hash'] ?? '$2y$10$abcdefghijklmnopqrstuuMZ6vH2n7y1Qh5m3p0S0qL5kq9YkY8Xe';
    if (password_verify($_POST['password'] ?? '', $hash) && $u) {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$u['id'];
        redirect($next);
    }
    $error = 'E-mail ou mot de passe incorrect.';
}
$pageTitle = 'Connexion';
include 'includes/header.php';
?>
<section class="auth-box">
  <h1>Connexion</h1>
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <form method="post" class="form">
    <?= csrf_field() ?>
    <input type="hidden" name="next" value="<?= e($next) ?>">
    <label>E-mail<input type="email" name="email" required value="<?= e($email) ?>"></label>
    <label>Mot de passe<input type="password" name="password" required></label>
    <button class="btn btn-primary btn-block" type="submit">Se connecter</button>
  </form>
  <p class="center">Pas encore de compte ? <a href="register.php">Créer un compte</a></p>
</section>
<?php include 'includes/footer.php'; ?>
