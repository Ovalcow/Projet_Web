<?php declare(strict_types=1);
require_once __DIR__ . '/../includes/init.php';

$pageTitle = 'Accueil';

$events = db_query(
  "SELECT e.id, e.titre, e.description, e.date_event, e.lieu, e.jauge_max,
          (SELECT COUNT(*) FROM reservations r WHERE r.event_id = e.id) AS nb_reservations,
          e.affiche_path
   FROM events e
   WHERE e.date_event >= NOW()
   ORDER BY e.date_event ASC
   LIMIT 5"
);

require_once __DIR__ . '/../includes/header.php';
?>

<section class="hero">
  <div class="container">
    <h1>Bienvenue sur Omnes Event</h1>
    <p>Découvrez les événements à venir de votre campus! </p>
  </div>
</section>

<div class="carrousel">
  <div class="carrousel-events">
    <?php foreach ($events as $ev): ?>
      <article class="event-card event-slide">
        <?php if (!empty($ev['affiche_path'])): ?>
          <img class="event-affiche" src="<?= e('/uploads/' . $ev['affiche_path']) ?>"
            alt="Affiche de <?= e($ev['titre']) ?>" />
        <?php else: ?>
          <img class="event-affiche" src="<?= e('/assets/images/logo_omneseducation.webp') ?>" alt="Affiche par défaut" />
          <!--si pas d'image on en affiche une par défaut-->
        <?php endif; ?>


        <div class="event-meta">
          <h2 class="event-title">
            <?= e($ev['titre']) ?>
          </h2>
          <p>📅
            <?= e((new DateTime($ev['date_event']))->format('d/m/Y H:i')) ?>
          </p>
          <p>📍
            <?= e($ev['lieu']) ?>
          </p>
          <a class="btn btn-secondary" href="/pages/event_detail.php?id=<?= (int) $ev['id'] ?>">Voir</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <div class="carrousel-controls">
    <button id="prev" type="button">←</button>
    <button id="suiv" type="button">→</button>
  </div>

  <div class="carrousel-points">
    <?php foreach ($events as $i => $ev): ?>
      <button class="point" type="button"></button>
    <?php endforeach; ?>
  </div>
</div>
<script src="../assets/scripts/jquery.js"></script>
<script src="../assets/scripts/carrousel_events.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>