<?php
declare(strict_types=1);

include('../../config/init.php');
include('../../includes/auth_check.php');
include('../../includes/functions.php');

$pageTitle = 'QR Code billet';

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_absolute_url(string $path): string {
    $base = rtrim((string)(getenv('APP_BASE_URL') ?: ''), '/');
    if ($base === '') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string)($_SERVER['HTTP_HOST'] ?? '');
        $base = $host !== '' ? $scheme . '://' . $host : '';
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
            e.id AS event_id,
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
    echo '<h1>Aucun billet</h1>';
    echo '<p>Vous n’avez pas encore de réservation pour cet événement.</p>';
    echo '<a class="btn btn-secondary" href="/pages/events/detail.php?id=' . (int)$eventId . '">Retour</a>';
    echo '</section>';
    include('../../includes/footer.php');
    exit;
}

$verifyUrl = app_absolute_url('/pages/reservations/billet_verify.php?reservation_id=' . (int)$booking['reservation_id']);
?>

<section class="container">
    <h1>QR billet</h1>

    <div style="display:grid; gap:16px; max-width:760px;">
        <div style="padding:16px; border:1px solid var(--border); border-radius:14px;">
            <p><strong>Événement :</strong> <?= h((string)$booking['titre']) ?></p>
            <p><strong>Date :</strong> <?= h((new DateTime((string)$booking['date_event']))->format('d/m/Y H:i')) ?></p>
            <p><strong>Lieu :</strong> <?= h((string)$booking['lieu']) ?></p>
            <p><strong>Réservation :</strong> #<?= (int)$booking['reservation_id'] ?></p>
        </div>

        <div style="padding:16px; border:1px solid var(--border); border-radius:14px; width:max-content;">
            <canvas id="qrCanvas" width="250" height="250" style="width:250px;height:250px;background:#fff;"></canvas>
            <p id="qrCanvas_fallback" style="display:none;"></p>
        </div>

        <p style="word-break:break-all;">
            Lien de vérification :
            <a href="<?= h($verifyUrl) ?>"><?= h($verifyUrl) ?></a>
        </p>

        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a class="btn btn-secondary" href="/pages/events/detail.php?id=<?= (int)$eventId ?>">Retour détail</a>
            <a class="btn btn-secondary" href="/pages/reservations/mes_billets.php">Mes billets</a>
        </div>
    </div>
</section>

<script src="/assets/js/qrcode.min.js"></script>
<script src="/assets/js/qr-local.js"></script>
<script>
(function () {
    const payload = <?= json_encode($verifyUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    if (typeof window.renderLocalQr === 'function') {
        window.renderLocalQr('qrCanvas', payload);
    } else {
        const fb = document.getElementById('qrCanvas_fallback');
        if (fb) {
            fb.style.display = 'block';
            fb.textContent = 'QR indisponible : script local manquant.';
        }
    }
})();
</script>

<?php include('../../includes/footer.php'); ?>
