<?php declare(strict_types=1);
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Inscription';

$errors = [];

$flash = flash_get();
if (!empty($flash['error'])) {
  $errors[] = (string)$flash['error'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_verify()) {
    $errors[] = 'Requête invalide (CSRF).';
  } else {
    $nom = trim((string)($_POST['nom'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));

    $password = (string)($_POST['password'] ?? '');
    $role = (string)($_POST['role'] ?? '');


  if ($nom === '' || mb_strlen($nom) > 100) {
    $errors[] = 'Nom invalide.';
  }
  if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email invalide.';
  }
  if (mb_strlen($password) < 8) {
    $errors[] = 'Mot de passe trop court (min 8 caractères).';
  }
  if (!in_array($role, ['participant', 'organisateur'], true)) {
    $errors[] = 'Rôle invalide.';
  }

  if (!$errors) {
    // Vérifie unicité email
    $exists = db_single('SELECT id FROM users WHERE email = :email', [':email' => $email]);
    if ($exists) {
      $errors[] = 'Un compte existe déjà pour cet email.';
    } else {
      $hash = password_hash($password, PASSWORD_BCRYPT);
      $associationId = null; // TODO Phase B si vous liez une association
      $validated = 0;

      db_execute(
        "INSERT INTO users (role, association_id, nom, email, password_hash, photo_path, is_organisateur_validated)
         VALUES (:role, :association_id, :nom, :email, :password_hash, NULL, :validated)",
        [
          ':role' => $role,
          ':association_id' => $associationId,
          ':nom' => $nom,
          ':email' => $email,
          ':password_hash' => $hash,
          ':validated' => $validated,
        ]
      );

      // Redirection vers connexion
      header('Location: /pages/login.php');
      exit;
    }
  }
}
}


require_once __DIR__ . '/../includes/header.php';
?>


<section class="container">
  <h1 style="margin-top:0;">Inscription</h1>

  <form method="POST" style="margin-top:16px; display:grid; gap:12px; max-width:520px;">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />

    <?php foreach ($errors as $err): ?>
      <div style="padding:10px; border:1px solid rgba(255,255,255,.12); border-radius:12px; background: rgba(255,0,0,.08);">
        <?= e($err) ?>
      </div>
    <?php endforeach; ?>

    <label style="display:grid; gap:6px;">
      <span style="color: var(--muted); font-size:12px;">Nom</span>
      <input name="nom" required style="padding:10px 12px; border-radius:10px; border:1px solid var(--border); background: rgba(255,255,255,.03); color: var(--text);" />
    </label>

    <label style="display:grid; gap:6px;">
      <span style="color: var(--muted); font-size:12px;">Email</span>
      <input type="email" name="email" required style="padding:10px 12px; border-radius:10px; border:1px solid var(--border); background: rgba(255,255,255,.03); color: var(--text);" />
    </label>

    <div class="login-logo">OE</div>

    <h1>Inscription</h1>
    <p class="login-subtitle">Créez votre compte OmnesEvent</p>

    <?php if (isset($_GET['erreur'])): ?>
        <?php
        if ($_GET['erreur'] === 'vide') {
            $msg = 'Tous les champs sont obligatoires.';
        } elseif ($_GET['erreur'] === 'existe') {
            $msg = 'Cet email est déjà utilisé.';
        } else {
            $msg = 'Erreur inconnue.';
        }
        ?>
        <p class="msg msg-error"><?php echo $msg; ?></p>
    <?php endif; ?>

    <?php if (isset($_GET['succes'])): ?>
        <p class="msg msg-success">Compte créé ! Vous pouvez vous connecter.</p>
    <?php endif; ?>

    <form method="post" action="/pages/register.php">
        <label for="nom">Nom</label>
        <input type="text" name="nom" id="nom" placeholder="Votre nom" required />

        <label for="email">Email</label>
        <input type="email" name="email" id="email" placeholder="votre@email.com" required />

        <label for="mot_de_passe">Mot de passe</label>
        <input type="password" name="mot_de_passe" id="mot_de_passe" placeholder="Choisissez un mot de passe" required />

        <button type="submit" class="btn">Créer un compte</button>
    </form>

    <p class="form-footer">Déjà inscrit ? <a href="/pages/login.php">Se connecter</a></p>
</div>

<?php include('../includes/footer.php'); ?>