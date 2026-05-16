<?php declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$pageTitle = 'Mon profil';

$flash = flash_get();
$errors = [];
if (!empty($flash['error'])) {
  $errors[] = (string)$flash['error'];
}
$message = $flash['success'] ?? null;

$userId = (int)$currentUser['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_verify()) {
    $errors[] = 'Requête invalide (CSRF).';
  } else {
    // MVP: mise à jour nom/email + changement mot de passe
    $nom = trim((string)($_POST['nom'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $newPassword = (string)($_POST['new_password'] ?? '');

    if ($nom === '' || mb_strlen($nom) > 100) {
      $errors[] = 'Nom invalide.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errors[] = 'Email invalide.';
    }

    if (!$errors) {
      // update email uniqueness if changed
      $existing = db_single(
        'SELECT id FROM users WHERE email = :email AND id != :id',
        [':email' => $email, ':id' => $userId]
      );

      if ($existing) {
        $errors[] = 'Un autre compte utilise déjà cet email.';
      } else {
        $params = [':nom' => $nom, ':email' => $email, ':id' => $userId];
        $sql = 'UPDATE users SET nom = :nom, email = :email';

        if ($newPassword !== '') {
          if (mb_strlen($newPassword) < 8) {
            $errors[] = 'Mot de passe trop court (min 8 caractères).';
          } else {
            $hash = password_hash($newPassword, PASSWORD_BCRYPT);
            $sql .= ', password_hash = :pw';
            $params[':pw'] = $hash;
          }
        }

        if (!$errors) {
          $sql .= ' WHERE id = :id';
          db_execute($sql, $params);
          flash_set('success', 'Profil mis à jour avec succès.');
          header('Location: /pages/profile.php');
          exit;
        }
      }
    }
  }
}


require_once __DIR__ . '/../includes/header.php';
?>





<section class="container">
  <h1 style="margin-top:0;">Mon profil</h1>

  <?php if (!empty($errors)): ?>
    <div style="margin-top:16px; padding:12px; border:1px solid rgba(255,0,0,.25); border-radius:14px; background: rgba(255,0,0,.08); max-width:720px;">
      <?php foreach ($errors as $err): ?>
        <div style="margin-bottom:8px;"><?= e($err) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($message)): ?>
    <div style="margin-top:16px; padding:12px; border:1px solid rgba(56,189,248,.35); border-radius:14px; background: rgba(56,189,248,.12); max-width:720px;">
      <?= e((string)$message) ?>
    </div>
  <?php endif; ?>

  <div style="margin-top:16px; padding:16px; background: rgba(255,255,255,.03); border:1px solid var(--border); border-radius:14px; max-width:720px;">
  <form method="POST" style="display:grid; gap:12px;">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />

      <label style="display:grid; gap:6px;">
        <span style="color: var(--muted); font-size:12px;">Nom</span>
        <input name="nom" required value="<?= e((string)$currentUser['nom']) ?>" style="padding:10px 12px; border-radius:10px; border:1px solid var(--border); background: rgba(255,255,255,.03); color: var(--text);" />
      </label>

      <label style="display:grid; gap:6px;">
        <span style="color: var(--muted); font-size:12px;">Email</span>
        <input type="email" name="email" required value="<?= e((string)$currentUser['email']) ?>" style="padding:10px 12px; border-radius:10px; border:1px solid var(--border); background: rgba(255,255,255,.03); color: var(--text);" />
      </label>

      <label style="display:grid; gap:6px;">
        <span style="color: var(--muted); font-size:12px;">Nouveau mot de passe (optionnel)</span>
        <input type="password" name="new_password" style="padding:10px 12px; border-radius:10px; border:1px solid var(--border); background: rgba(255,255,255,.03); color: var(--text);" />
      </label>

      <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
        <button class="btn btn-secondary" type="submit">Enregistrer</button>
        <div style="color: var(--muted); font-size:12px;">
          Rôle : <strong><?= e((string)$currentUser['role']) ?></strong>
        </div>
      </div>
    </form>
  </div>
</section>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>

