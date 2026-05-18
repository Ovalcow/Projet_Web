<?php declare(strict_types=1);

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_login();

require_role(['admin']);


$pageTitle = 'Gérer utilisateurs';

$users = db_query(
  'SELECT id, nom, email, role, is_organisateur_validated FROM users ORDER BY id DESC'
);



require_once __DIR__ . '/../../includes/header.php';
?>

<h1>Gestion utilisateurs (admin)</h1>

<table class="table" style="width:100%; border-collapse:collapse; margin-top:12px;">
  <thead>
    <tr>
      <th>ID</th>
      <th>Nom</th>
      <th>Email</th>
      <th>Rôle</th>
      <th>Validé orga</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><?= (int)$u['id'] ?></td>
        <td><?= e((string)$u['nom']) ?></td>
        <td><?= e((string)$u['email']) ?></td>
        <td><?= e((string)$u['role']) ?></td>
        <td><?= !empty($u['is_organisateur_validated']) ? 'Oui' : 'Non' ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<p style="color:var(--muted); margin-top:12px;">(Aucune action de suppression/modération n’est ajoutée ici : à étendre selon votre besoin.)</p>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

