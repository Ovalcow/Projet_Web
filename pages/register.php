<?php declare(strict_types=1);
require_once __DIR__ . '/../includes/db.php';

// Phase A : inscription à implémenter.
$currentUser = null;
$pageTitle = 'Inscription';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $errors[] = "Inscription non implémentée (Phase A).";
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

