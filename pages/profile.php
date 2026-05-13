<?php declare(strict_types=1);
require_once __DIR__ . '/../includes/db.php';

// Phase A : auth/CRUD profil à implémenter.
$currentUser = null;
$pageTitle = 'Mon profil';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="container">
  <h1 style="margin-top:0;">Mon profil</h1>
  <div style="margin-top:16px; padding:16px; background: rgba(255,255,255,.03); border:1px solid var(--border); border-radius:14px; max-width:720px;">
    <p style="color: var(--muted); margin:0 0 12px;">
      Connexion & modification du profil à implémenter en Phase A.
    </p>
    <p style="color: var(--muted); margin:0; font-size:13px;">
      MVP requis ensuite : affichage/édition infos personnelles + changement de mot de passe.
    </p>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

