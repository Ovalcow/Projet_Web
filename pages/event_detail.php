<?php declare(strict_types=1);
require_once __DIR__ . '/../includes/db.php';

// Phase 0 : détail + affichage, sans inscription réelle.
$currentUser = null; // placeholder
$pageTitle = 'Détail événement';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  $message = 'ID événement invalide.';
  require_once __DIR__ . '/../includes/header.php';
  echo '<section class="container"><p>' . e($message) . '</p></section>';
  require_once __DIR__ . '/../includes/footer.php';
  exit;
}

$event = db_single(
  "SELECT e.id, e.titre, e.description, e.date_event, e.lieu, e.jauge_max,
          (SELECT COUNT(*) FROM reservations r WHERE r.event_id = e.id) AS nb_reservations,
          e.affiche_path,
          a.nom AS association_nom,
          c.nom AS category_nom
   FROM events e
   LEFT JOIN associations a ON a.id = e.association_id
   LEFT JOIN categories c ON c.id = e.category_id
   WHERE e.id = :id",
  [':id' => $id]
);

require_once __DIR__ . '/../includes/header.php';

if (!$event) {
  echo '<section class="container"><p>Événement introuvable.</p></section>';
  require_once __DIR__ . '/../includes/footer.php';
  exit;
}

$nb = (int)$event['nb_reservations'];
$max = (int)$event['jauge_max'];
$placesRestantes = max(0, $max - $nb);

?>

<section class="container">
  <h1 style="margin-top:0;"><?= e($event['titre']) ?></h1>

  <div class="event-detail" style="margin-top:16px; display:grid; gap:14px;">
    <div style="background: rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.10); border-radius:14px; padding:14px;">
      <div style="display:flex; gap:14px; align-items:flex-start; flex-wrap:wrap;">
        <div>
          <?php if (!empty($event['affiche_path'])): ?>
            <img src="<?= e('/uploads/' . $event['affiche_path']) ?>" alt="Affiche" style="width:180px; height:180px; object-fit:cover; border-radius:14px;" />
          <?php else: ?>
            <img src="/assets/img/default_event.jpg" alt="Affiche par défaut" style="width:180px; height:180px; object-fit:cover; border-radius:14px;" />
          <?php endif; ?>
        </div>

        <div style="flex:1; min-width:240px;">
          <p style="margin:0 0 8px; color: var(--muted);">📅 <?= e((new DateTime($event['date_event']))->format('d/m/Y H:i')) ?></p>
          <p style="margin:0 0 8px; color: var(--muted);">📍 <?= e($event['lieu']) ?></p>
          <p style="margin:0 0 8px; color: var(--muted);">🏷️ Catégorie : <?= e($event['category_nom'] ?? '') ?></p>
          <p style="margin:0 0 14px; color: var(--muted);">🏛️ Association : <?= e($event['association_nom'] ?? '') ?></p>

          <p style="margin:0 0 14px; color: var(--muted);">Places restantes : <strong><?= $placesRestantes ?></strong> / <?= $max ?></p>

          <a class="btn btn-secondary" href="/pages/events.php" style="margin-right:10px;">Retour</a>

          <!-- Placeholder MVP inscription (Phase A/C) -->
          <button class="btn" type="button" disabled title="Inscription gérée en Phase C">Inscription bientôt</button>
        </div>
      </div>

      <div style="margin-top:14px;">
        <h2 style="font-size:16px; margin:0 0 8px;">Description</h2>
        <p style="margin:0; white-space:pre-wrap; color: var(--text);"><?= nl2br(e($event['description'])) ?></p>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

