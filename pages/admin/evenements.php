<?php declare(strict_types=1);

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../includes/auth_check.php';

require_login();
require_role(['admin']);

$pageTitle = 'Gérer événements';

echo '';

// Table `events` (schéma actuel) :
// id, organizer_id, association_id, category_id, titre, description,
// date_event, lieu, jauge_max, affiche_path, created_at, updated_at
//
// Pour un affichage admin minimal, on mappe vers les colonnes existantes du tableau.
$events = db_query(
  'SELECT id,
          titre AS nom,
          date_event AS date_debut,
          lieu,
          jauge_max AS nb_places,
          affiche_path
   FROM events
   ORDER BY date_event DESC, id DESC'
);

require_once __DIR__ . '/../../includes/header.php';
?>

<h1>Gestion événements (admin)</h1>

<table class="table" style="width:100%; border-collapse:collapse; margin-top:12px;">
  <thead>
    <tr>
      <th>ID</th>
      <th>Nom</th>
      <th>Date début</th>
      <th>Date fin</th>
      <th>Lieu</th>
      <th>Places</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($events as $ev): ?>
      <tr>
        <td><?= (int)$ev['id'] ?></td>
        <td><?= e((string)$ev['nom']) ?></td>
        <td><?= e((string)($ev['date_debut'] ?? '')) ?></td>
        <td></td>
        <td><?= e((string)$ev['lieu']) ?></td>
        <td><?= e((string)$ev['nb_places']) ?></td>
        <td><?= e((string)($ev['affiche_path'] ?? '')) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<p style="color:var(--muted); margin-top:12px;">(Aucune action de suppression/modération n’est ajoutée ici : à étendre selon votre besoin.)</p>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

