<?php declare(strict_types=1);
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$pageTitle = 'QR Code billet';

$eventId = (int)($_GET['event_id'] ?? 0);
if ($eventId <= 0) {
  http_response_code(400);
  require_once __DIR__ . '/../includes/header.php';
  echo '<section class="container"><p>event_id manquant.</p></section>';
  require_once __DIR__ . '/../includes/footer.php';
  exit;
}

$booking = db_single(
  "SELECT r.id AS reservation_id, e.id AS event_id, e.titre, e.date_event
   FROM reservations r
   JOIN events e ON e.id = r.event_id
   WHERE r.event_id = :event_id AND r.participant_id = :participant_id",
  [':event_id' => $eventId, ':participant_id' => (int)$currentUser['id']]
);

require_once __DIR__ . '/../includes/header.php';

if (!$booking) {
  echo '<section class="container"><h1 style="margin-top:0;">Aucun billet</h1><p>Tu n\'as pas encore de réservation pour cet événement.</p><a class="btn btn-secondary" href="/pages/event_detail.php?id=' . (int)$eventId . '">Retour</a></section>';
  require_once __DIR__ . '/../includes/footer.php';
  exit;
}

// QR payload : on encode l’URL de vérification.
$payload = '/pages/billet_verify.php?reservation_id=' . (int)$booking['reservation_id'];
require_once __DIR__ . '/../includes/qr.php';
$qrDataUri = qr_generate_png_data_uri($payload, 250);

// Debug minimal : utile si la génération locale échoue.
if (!empty($_GET['debug_qr']) && $_GET['debug_qr'] === '1') {
  echo '<!-- DEBUG payload=' . e($payload) . ' qrDataUri=' . (empty($qrDataUri) ? 'EMPTY' : 'OK') . ' -->';
}


?>
<section class="container">
  <h1 style="margin-top:0;">QR Code billet</h1>

  <div style="margin-top:16px; padding:16px; background: rgba(255,255,255,.03); border:1px solid var(--border); border-radius:14px; max-width:820px;">
    <div style="display:grid; grid-template-columns: 280px 1fr; gap:16px; align-items:start;">
      <div style="background: rgba(0,0,0,.15); border:1px solid var(--border); border-radius:14px; padding:12px;">
        <?php if (!empty($qrDataUri)): ?>
          <img src="<?= e($qrDataUri) ?>" alt="QR code billet" style="width:100%; height:auto;" />
        <?php else: ?>
          <div style="padding:12px;border:1px solid var(--border);border-radius:12px;color:var(--muted);font-size:13px;">
            QR indisponible côté serveur (génération locale introuvable).
          </div>
        <?php endif; ?>
      </div>

      <div>
        <p style="margin:0 0 6px; color: var(--muted);"><strong>Événement :</strong> <?= e((string)$booking['titre']) ?></p>
        <p style="margin:0 0 10px; color: var(--muted);"><strong>Date :</strong> <?= e((new DateTime((string)$booking['date_event']))->format('d/m/Y H:i')) ?></p>
        <p style="margin:0 0 10px; color: var(--muted);"><strong>Réservation :</strong> #<?= (int)$booking['reservation_id'] ?></p>

        <div style="margin-top:10px; color: var(--muted); font-size:13px;">Lien vérification (contenu QR)</div>
        <div style="margin-top:6px; padding:10px; border-radius:12px; border:1px solid var(--border); background: rgba(255,255,255,.02); color: var(--text); word-break:break-all;">
          <?= e($payload) ?>
        </div>
      </div>
    </div>

    <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
      <a class="btn btn-secondary" href="/pages/event_detail.php?id=<?= (int)$eventId ?>">Retour détail</a>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

