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

require_once __DIR__ . '/../includes/header.php';
?>

<section class="container">
  <h1 style="margin-top:0;">Inscription</h1>

  <form method="POST" style="margin-top:16px; display:grid; gap:12px; max-width:520px;">
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

    <label style="display:grid; gap:6px;">
      <span style="color: var(--muted); font-size:12px;">Mot de passe</span>
      <input type="password" name="password" required style="padding:10px 12px; border-radius:10px; border:1px solid var(--border); background: rgba(255,255,255,.03); color: var(--text);" />
    </label>

    <label style="display:grid; gap:6px;">
      <span style="color: var(--muted); font-size:12px;">Rôle</span>
      <select name="role" required style="padding:10px 12px; border-radius:10px; border:1px solid var(--border); background: rgba(255,255,255,.03); color: var(--text);">
        <option value="participant">Participant</option>
        <option value="organisateur">Organisateur</option>
      </select>
    </label>

    <button class="btn btn-secondary" type="submit">Créer le compte</button>
  </form>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

