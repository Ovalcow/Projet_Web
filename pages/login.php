<?php declare(strict_types=1);
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Connexion';

$errors = [];

$flash = flash_get();
if (!empty($flash['error'])) {
  $errors[] = (string)$flash['error'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_verify()) {
    $errors[] = 'Requête invalide (CSRF).';
  } else {
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errors[] = 'Email invalide.';
    } else {
      $user = db_single(
        "SELECT id, role, association_id, password_hash, nom, email, photo_path, is_organisateur_validated
         FROM users WHERE email = :email",
        [':email' => $email]
      );

      if (!$user || empty($user['password_hash']) || !password_verify($password, (string)$user['password_hash'])) {
        // Anti-bruteforce basique (TP9) : compteur + sleep après 3 tentatives
        if (!isset($_SESSION['tentatives']) || !is_int($_SESSION['tentatives'])) {
          $_SESSION['tentatives'] = 0;
        }
        $_SESSION['tentatives']++;

        if ($_SESSION['tentatives'] >= 3) {
          sleep(5);
          $_SESSION['tentatives'] = 0;
        }

        $errors[] = 'Identifiants invalides.';
      } else {
        // Login OK
        // On régénère l'ID de session pour limiter le session fixation
        if (session_status() !== PHP_SESSION_ACTIVE) {
          @session_start();
        }
        @session_regenerate_id(true);

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['role'] = (string)$user['role'];
        $_SESSION['association_id'] = $user['association_id'] !== null ? (int)$user['association_id'] : null;

        // Redirection simple : profil
        header('Location: /pages/profile.php');
        exit;
      }
    }
  }
}

require_once __DIR__ . '/../includes/header.php';
?>


<section class="container">

  <h1>Connexion</h1>
  <form  class="login-form" method="POST" style="margin-top:16px; display:grid; gap:12px; max-width:420px;">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />
    <?php foreach ($errors as $err): ?>
      <div class="message_erreur" >
        <?= e($err) ?>
      </div>
    <?php endforeach; ?>

    <label class="email_login">
      <span style="color: var(--muted); font-size:12px;">Email</span>
      <input type="email" name="email"/>
    </label>

    <label class="password_login">
      <span style="color: var(--muted); font-size:12px;">Mot de passe</span>
      <input type="password" name="password"/>
    </label>

    <button class="btn btn-secondary" type="submit">Se connecter</button>

    <p style="margin: 10px 0 0; color: var(--muted); font-size: 13px;">
      Pas encore de compte ?
      <a href="/pages/register.php" style="color: var(--primary2); text-decoration: none; font-weight: 700;">Créer un compte</a>
    </p>
  </form>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

