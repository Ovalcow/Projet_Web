<?php declare(strict_types=1);
require_once __DIR__ . '/../includes/db.php';

$currentUser = null; // placeholder pour phase 0
$pageTitle = 'Accueil';

$events = db_query(
  "SELECT e.id, e.titre, e.description, e.date_event, e.lieu, e.jauge_max,
          (SELECT COUNT(*) FROM reservations r WHERE r.event_id = e.id) AS nb_reservations,
          e.affiche_path
   FROM events e
   WHERE e.date_event >= NOW()
   ORDER BY e.date_event ASC
   LIMIT 10"
);

require_once __DIR__ . '/../includes/header.php';
?>

<section class="hero">
  <div class="container">
    <h1>Événements à venir</h1>
    <p>Recherchez, inscrivez-vous et gérez vos billets.</p>
  </div>
</section>

<section class="container">
  <form class="search-form" action="/pages/events.php" method="GET">
    <label>
      <span>Recherche</span>
      <input type="text" name="q" placeholder="Titre, lieu…" />
    </label>
    <label>
      <span>Date</span>
      <input type="date" name="date" />
    </label>
    <label>
      <span>Catégorie</span>
      <select name="category">
        <option value="">Toutes</option>
        <?php foreach (db_query("SELECT id, nom FROM categories ORDER BY nom") as $cat): ?>
          <option value="<?= (int)$cat['id'] ?>"><?= e($cat['nom']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>
      <span>Association</span>
      <select name="association">
        <option value="">Toutes</option>
        <?php foreach (db_query("SELECT id, nom FROM associations ORDER BY nom") as $a): ?>
          <option value="<?= (int)$a['id'] ?>"><?= e($a['nom']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <button class="btn" type="submit">Rechercher</button>
  </form>

  <div class="events-grid">
    <?php if (!$events): ?>
      <p>Aucun événement à venir pour le moment.</p>
    <?php endif; ?>

    <?php foreach ($events as $ev):
      $nb = (int)$ev['nb_reservations'];
      $max = (int)$ev['jauge_max'];
      $placesRestantes = max(0, $max - $nb);
    ?>
      <article class="event-card">
        <?php if (!empty($ev['affiche_path'])): ?>
          <img class="event-affiche" src="<?= e('/uploads/' . $ev['affiche_path']) ?>" alt="Affiche de <?= e($ev['titre']) ?>" />
        <?php endif; ?>

        <div class="event-meta">
          <h2 class="event-title"><?= e($ev['titre']) ?></h2>
          <p class="event-date">📅 <?= e((new DateTime($ev['date_event']))->format('d/m/Y H:i')) ?></p>
          <p class="event-lieu">📍 <?= e($ev['lieu']) ?></p>
          <p class="event-capacity">Places restantes : <strong><?= $placesRestantes ?></strong></p>
        </div>

        <a class="btn btn-secondary" href="/pages/event_detail.php?id=<?= (int)$ev['id'] ?>">Voir</a>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

