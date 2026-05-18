<?php
/**
 * pages/reservations/billet_qr.php
 * Affiche le QR code du billet du participant connecté.
 */
declare(strict_types=1);

include('../../config/init.php');
include('../../includes/auth_check.php');
include('../../includes/functions.php');

if (function_exists('require_login')) {
    require_login();
}

$pageTitle = 'QR Code billet';

function oh(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function absolute_url(string $path): string
{
    $base = rtrim((string)(getenv('APP_BASE_URL') ?: ''), '/');
    if ($base === '') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string)($_SERVER['HTTP_HOST'] ?? '');
        $base = $host !== '' ? ($scheme . '://' . $host) : '';
    }

    return $base . $path;
}

$eventId = (int)($_GET['event_id'] ?? 0);
$userId = (int)($currentUser['id'] ?? 0);

if ($eventId <= 0) {
    http_response_code(400);
    include('../../includes/header.php');
    echo '<section class="container"><p>event_id manquant.</p></section>';
    include('../../includes/footer.php');
    exit;
}

$stmt = $bdd->prepare(
    "SELECT r.id AS reservation_id,
            r.event_id,
            r.participant_id,
            e.titre,
            e.date_event,
            e.lieu
     FROM reservations r
     JOIN events e ON e.id = r.event_id
     WHERE r.event_id = :event_id
       AND r.participant_id = :participant_id
     LIMIT 1"
);
$stmt->execute([
    'event_id' => $eventId,
    'participant_id' => $userId,
]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();

include('../../includes/header.php');

if (!$booking) {
    echo '<section class="container">';
    echo '<h1 style="margin-top:0;">Aucun billet</h1>';
    echo '<p>Vous n’avez pas encore de réservation pour cet événement.</p>';
    echo '<a class="btn btn-secondary" href="/pages/events/detail.php?id=' . (int)$eventId . '">Retour</a>';
    echo '</section>';
    include('../../includes/footer.php');
    exit;
}

$verificationPath = '/pages/reservations/billet_verify.php?reservation_id=' . (int)$booking['reservation_id'];
$verificationUrl = absolute_url($verificationPath);
$qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($verificationUrl);
?>
<section class="container">
    <h1 style="margin-top:0;">QR Code billet</h1>

    <div style="margin-top:16px; padding:16px; background:rgba(255,255,255,.03); border:1px solid var(--border); border-radius:14px; max-width:860px;">
        <div style="display:grid; grid-template-columns:280px 1fr; gap:16px; align-items:start;">
            <div style="background:#fff; border-radius:14px; padding:12px;">
                <canvas id="qrCanvas" width="250" height="250" style="width:100%; height:auto; display:block;"></canvas>
                <img id="qrFallbackImg" src="<?= oh($qrApiUrl) ?>" alt="QR code billet" style="width:100%; height:auto; display:none;" />
            </div>

            <div>
                <p><strong>Réservation :</strong> #<?= (int)$booking['reservation_id'] ?></p>
                <p><strong>Événement :</strong> <?= oh((string)$booking['titre']) ?></p>
                <p><strong>Date :</strong> <?= oh(date('d/m/Y H:i', strtotime((string)$booking['date_event']))) ?></p>
                <p><strong>Lieu :</strong> <?= oh((string)$booking['lieu']) ?></p>

                <div style="margin-top:10px; font-size:13px; opacity:.8;">Lien de vérification contenu dans le QR</div>
                <div style="margin-top:6px; padding:10px; border-radius:12px; border:1px solid var(--border); word-break:break-all;">
                    <?= oh($verificationUrl) ?>
                </div>
            </div>
        </div>

        <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
            <a class="btn btn-secondary" href="/pages/events/detail.php?id=<?= (int)$eventId ?>">Retour détail</a>
            <a class="btn btn-secondary" href="/pages/reservations/mes_billets.php">Mes billets</a>
        </div>
    </div>
</section>

<script src="/assets/js/qrcodegen.min.js"></script>
<script src="/assets/js/qrcode.min.js"></script>
<script src="/assets/js/qr-local.js"></script>
<script>
(function () {
    const payload = <?= json_encode($verificationUrl, JSON_UNESCAPED_SLASHES) ?>;
    const fallback = document.getElementById('qrFallbackImg');

    try {
        if (typeof window.renderLocalQr === 'function') {
            window.renderLocalQr('qrCanvas', payload);
            return;
        }
    } catch (error) {
        console.error(error);
    }

    const canvas = document.getElementById('qrCanvas');
    if (canvas) canvas.style.display = 'none';
    if (fallback) fallback.style.display = 'block';
})();
</script>
<?php include('../../includes/footer.php'); ?>
