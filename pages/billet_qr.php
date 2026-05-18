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
  echo '<section class="container"><h1 style="margin-top:0;">Aucun billet</h1><p>Tu n\'as pas encore de r\u00e9servation pour cet \u00e9v\u00e9nement.</p><a class="btn btn-secondary" href="/pages/event_detail.php?id=' . (int)$eventId . '">Retour</a></section>';
  require_once __DIR__ . '/../includes/footer.php';
  exit;
}

// QR payload : URL de v\u00e9rification.
$payload = '/pages/billet_verify.php?reservation_id=' . (int)$booking['reservation_id'];

?>
<section class="container">
  <h1 style="margin-top:0;">QR Code billet</h1>

  <div style="margin-top:16px; padding:16px; background: rgba(255,255,255,.03); border:1px solid var(--border); border-radius:14px; max-width:820px;">
    <div style="display:grid; grid-template-columns: 280px 1fr; gap:16px; align-items:start;">
      <div style="background: rgba(0,0,0,.15); border:1px solid var(--border); border-radius:14px; padding:12px;">
        <canvas id="qrCanvas" style="width:100%; height:auto; display:block; background:#fff;" width="250" height="250"></canvas>
        <div id="qrCanvas_fallback" style="margin-top:10px; padding:12px;border:1px solid var(--border);border-radius:12px;color:var(--muted);font-size:13px; display:none;"></div>
      </div>

      <div>
        <p style="margin:0 0 6px; color: var(--muted);"><strong>\u00c9v\u00e9nement :</strong> <?= e((string)$booking['titre']) ?></p>
        <p style="margin:0 0 10px; color: var(--muted);"><strong>Date :</strong> <?= e((new DateTime((string)$booking['date_event']))->format('d/m/Y H:i')) ?></p>
        <p style="margin:0 0 10px; color: var(--muted);"><strong>\u00c9reservation :</strong> #<?= (int)$booking['reservation_id'] ?></p>

        <div style="margin-top:10px; color: var(--muted); font-size:13px;">Lien v\u00e9rification (contenu QR)</div>
        <div style="margin-top:6px; padding:10px; border-radius:12px; border:1px solid var(--border); background: rgba(255,255,255,.02); color: var(--text); word-break:break-all;">
          <?= e($payload) ?>
        </div>
      </div>
    </div>

    <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
      <a class="btn btn-secondary" href="/pages/event_detail.php?id=<?= (int)$eventId ?>">Retour d\u00e9tail</a>
    </div>
  </div>
</section>

<script src="/assets/js/qrcodegen.min.js"></script>
<script src="/assets/js/qrcode.min.js"></script>
<script src="/assets/js/qr-local.js"></script>
<script>
  (function(){
    const payload = <?= json_encode($payload, JSON_UNESCAPED_SLASHES) ?>;
    try {
      if (typeof window.renderLocalQr !== 'function') {
        const fb = document.getElementById('qrCanvas_fallback');
        if (fb) { fb.style.display = 'block'; fb.textContent = 'QR indisponible : fonctions JS manquantes.'; }
        return;
      }
      window.renderLocalQr('qrCanvas', payload);
    } catch(e) {
      const fb = document.getElementById('qrCanvas_fallback');
      if (fb) { fb.style.display = 'block'; fb.textContent = 'QR indisponible (erreur JS).'; }
      console.error(e);
    }
  })();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

