<?php declare(strict_types=1);
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$pageTitle = 'Vérification billet';

$reservationId = (int)($_GET['reservation_id'] ?? 0);
if ($reservationId <= 0) {
  http_response_code(400);
  require_once __DIR__ . '/../includes/header.php';
  echo '<section class="container"><p>reservation_id manquant.</p></section>';
  require_once __DIR__ . '/../includes/footer.php';
  exit;
}

$payloadOk = false;
$booking = db_single(
  "SELECT r.id AS reservation_id,
          e.titre,
          e.date_event,
          u.nom AS participant_nom
   FROM reservations r
   JOIN events e ON e.id = r.event_id
   JOIN users u ON u.id = r.participant_id
   WHERE r.id = :rid AND r.participant_id = :participant_id",
  [':rid' => $reservationId, ':participant_id' => (int)$currentUser['id']]
);

if ($booking) {
  $payloadOk = true;
}

require_once __DIR__ . '/../includes/header.php';

if (!$payloadOk) {
  echo '<section class="container"><h1 style="margin-top:0;">Billet invalide</h1><p>Réservation introuvable.</p></section>';
  require_once __DIR__ . '/../includes/footer.php';
  exit;
}

?>
<section class="container">
  <h1 style="margin-top:0;">Billet vérifié ✅</h1>

  <div style="margin-top:16px; padding:16px; background: rgba(255,255,255,.03); border:1px solid var(--border); border-radius:14px; max-width:820px;">
    <div style="color: var(--muted); margin-bottom:8px;">Détails</div>
    <div style="display:grid; gap:6px;">
      <div><strong>Réservation :</strong> #<?= (int)$booking['reservation_id'] ?></div>
      <div><strong>Événement :</strong> <?= e((string)$booking['titre']) ?></div>
      <div><strong>Date :</strong> <?= e((new DateTime((string)$booking['date_event']))->format('d/m/Y H:i')) ?></div>
      <div><strong>Participant :</strong> <?= e((string)$booking['participant_nom']) ?></div>
    </div>
  </div>

  <div style="margin-top:14px;">
    <a class="btn btn-secondary" href="/pages/index.php">Retour accueil</a>
  </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

