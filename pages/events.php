<?php declare(strict_types=1);
require_once __DIR__ . '/../includes/db.php';

// Phase 0 : affichage catalogue + filtres (pas encore d’auth, pas encore de CRUD réservation).

$currentUser = null; // placeholder phase 0
$pageTitle = 'Événements';

// Filtres MVP
$q = trim((string)($_GET['q'] ?? ''));
$date = (string)($_GET['date'] ?? '');
$category = (string)($_GET['category'] ?? '');
$association = (string)($_GET['association'] ?? '');

$sql = "SELECT e.id, e.titre, e.description, e.date_event, e.lieu, e.jauge_max,
               (SELECT COUNT(*) FROM reservations r WHERE r.event_id = e.id) AS nb_reservations,
               e.affiche_path
        FROM events e
        WHERE 1=1 ";
$params = [];

if ($q !== '') {
  $sql .= " AND (e.titre LIKE :q_titre OR e.lieu LIKE :q_lieu OR e.description LIKE :q_description) ";
  
  $params[':q_titre'] = '%' . $q . '%';
  $params[':q_lieu'] = '%' . $q . '%';
  $params[':q_description'] = '%' . $q . '%';
}
if ($date !== '') {
  $sql .= " AND e.date_event >= :d0 AND e.date_event < :d1 ";
  $params[':d0'] = $date . ' 00:00:00';
  $params[':d1'] = $date . ' 23:59:59';
}
if ($category !== '') {
  $sql .= " AND e.category_id = :cat ";
  $params[':cat'] = (int)$category;
}
if ($association !== '') {
  // On affiche strictement l’association sélectionnée (plus tard : gestion des événements sans association)
  $sql .= " AND e.association_id = :assoc ";
  $params[':assoc'] = (int)$association;
}

$sql .= " ORDER BY e.date_event ASC LIMIT 100";

$events = db_query($sql, $params);

require_once __DIR__ . '/../includes/header.php';
?>

<section class="hero">
  <div class="container">
    <h1>Catalogue événements</h1>
    <p>Filtrez par date, catégorie ou association.</p>
  </div>
</section>

<section class="container">
  <form class="search-form" action="/pages/events.php" method="GET">
    <label>
      <span>Recherche</span>
      <input type="text" name="q" placeholder="Titre, lieu…" value="<?= e($q) ?>" />
    </label>

    <label>
      <span>Date</span>
      <input type="date" name="date" value="<?= e($date) ?>" />
    </label>

    <label>
      <span>Catégorie</span>
      <select name="category">
        <option value="">Toutes</option>
        <?php foreach (db_query("SELECT id, nom FROM categories ORDER BY nom") as $cat):
          $id = (int)$cat['id'];
          $sel = ((string)$id === $category) ? 'selected' : '';
        ?>
          <option value="<?= $id ?>" <?= $sel ?>><?= e($cat['nom']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>
      <span>Association</span>
      <select name="association">
        <option value="">Toutes</option>
        <?php foreach (db_query("SELECT id, nom FROM associations ORDER BY nom") as $a):
          $id = (int)$a['id'];
          $sel = ((string)$id === $association) ? 'selected' : '';
        ?>
          <option value="<?= $id ?>" <?= $sel ?>><?= e($a['nom']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <button class="btn" type="submit">Rechercher</button>
  </form>

  <div class="events-grid">
    <?php if (!$events): ?>
      <p>Aucun résultat.</p>
    <?php endif; ?>

    <?php foreach ($events as $ev):
      $nb = (int)$ev['nb_reservations'];
      $max = (int)$ev['jauge_max'];
      $placesRestantes = max(0, $max - $nb);
    ?>
      <article class="event-card">
        <?php if (!empty($ev['affiche_path'])): ?>
          <img class="event-affiche" src="<?= e('/uploads/' . $ev['affiche_path']) ?>" alt="Affiche de <?= e($ev['titre']) ?>" />
        <?php else: ?>
          <img class="event-affiche" src="/assets/img/default_event.jpg" alt="Affiche par défaut" />
        <?php endif; ?>

        <div class="event-meta">
          <h2 class="event-title"><?= e($ev['titre']) ?></h2>
          <p class="event-date">📅 <?= e((new DateTime($ev['date_event']))->format('d/m/Y H:i')) ?></p>
          <p class="event-lieu">📍 <?= e($ev['lieu']) ?></p>
          <p class="event-capacity">Places restantes : <strong><?= $placesRestantes ?></strong></p>
          <a class="btn btn-secondary" style="margin-top:10px;" href="/pages/event_detail.php?id=<?= (int)$ev['id'] ?>">Voir</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
