<?php
require_once __DIR__ . '/../includes/auth.php';

if (!$currentUser) {
    header('Location: /pages/login.php');
    exit();
}

$events = db_query('
    SELECT e.*, c.nom AS categorie, a.nom AS association, u.nom AS organisateur
    FROM events e
    JOIN categories c ON e.category_id = c.id
    LEFT JOIN associations a ON e.association_id = a.id
    JOIN users u ON e.organizer_id = u.id
    WHERE e.date_event >= NOW()
    ORDER BY e.date_event ASC
');

$pageTitle = 'Événements';
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Événements à venir</h1>

<?php if (empty($events)): ?>
  <p>Aucun événement à venir pour le moment.</p>
<?php else: ?>
  <div class="events-list">
    <?php foreach ($events as $ev): ?>
      <div class="event-card">
        <h2><a href="/pages/event_detail.php?id=<?= $ev['id'] ?>"><?= e($ev['titre']) ?></a></h2>
        <p class="event-meta">
          <?= date('d/m/Y à H:i', strtotime($ev['date_event'])) ?>
          —  <?= e($ev['lieu']) ?>
        </p>
        <p class="event-meta">
          🏷️ <?= e($ev['categorie']) ?>
          <?php if ($ev['association']): ?>
            — 🏛️ <?= e($ev['association']) ?>
          <?php endif; ?>
        </p>
        <p><?= e(mb_substr($ev['description'], 0, 150)) ?>…</p>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>