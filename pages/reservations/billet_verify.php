<?php
/**
 * pages/reservations/billet_verify.php
 * Page publique de vérification d'un QR billet.
 * Si un organisateur/admin connecté scanne le billet, il peut marquer la présence.
 */
declare(strict_types=1);

include('../../config/init.php');
include('../../includes/functions.php');

$pageTitle = 'Vérification billet';

function oh(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function verify_has_column(PDO $bdd, string $table, string $column): bool
{
    static $cache = [];
    $key = $table . '.' . $column;

    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    try {
        $stmt = $bdd->prepare(
            "SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table_name
               AND COLUMN_NAME = :column_name"
        );
        $stmt->execute(['table_name' => $table, 'column_name' => $column]);
        $cache[$key] = ((int)$stmt->fetchColumn() > 0);
    } catch (Throwable $e) {
        $cache[$key] = false;
    }

    return $cache[$key];
}

$reservationId = (int)($_GET['reservation_id'] ?? $_POST['reservation_id'] ?? 0);
$hasPresenceStatus = isset($bdd) && $bdd instanceof PDO && verify_has_column($bdd, 'reservations', 'presence_status');

if ($reservationId <= 0) {
    http_response_code(400);
    include('../../includes/header.php');
    echo '<section class="container"><p>reservation_id manquant.</p></section>';
    include('../../includes/footer.php');
    exit;
}

$currentRole = (string)($currentUser['role'] ?? '');
$canMarkPresence = in_array($currentRole, ['organisateur', 'admin'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canMarkPresence && $hasPresenceStatus) {
    if (!function_exists('csrf_verify') || csrf_verify()) {
        $update = $bdd->prepare("UPDATE reservations SET presence_status = 'present' WHERE id = :id");
        $update->execute(['id' => $reservationId]);
    }

    header('Location: /pages/reservations/billet_verify.php?reservation_id=' . $reservationId);
    exit;
}

$presenceSelect = $hasPresenceStatus ? ', r.presence_status' : '';
$stmt = $bdd->prepare(
    "SELECT r.id AS reservation_id,
            r.created_at,
            e.id AS event_id,
            e.titre,
            e.date_event,
            e.lieu,
            u.nom AS participant_nom,
            u.email AS participant_email
            $presenceSelect
     FROM reservations r
     JOIN events e ON e.id = r.event_id
     JOIN users u ON u.id = r.participant_id
     WHERE r.id = :reservation_id
     LIMIT 1"
);
$stmt->execute(['reservation_id' => $reservationId]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();

include('../../includes/header.php');

if (!$booking) {
    echo '<section class="container">';
    echo '<h1 style="margin-top:0;">Billet invalide</h1>';
    echo '<p>Réservation introuvable.</p>';
    echo '</section>';
    include('../../includes/footer.php');
    exit;
}
?>
<section class="container">
    <h1 style="margin-top:0;">Billet vérifié ✅</h1>

    <div style="margin-top:16px; padding:16px; background:rgba(255,255,255,.03); border:1px solid var(--border); border-radius:14px; max-width:860px;">
        <div style="display:grid; gap:8px;">
            <div><strong>Réservation :</strong> #<?= (int)$booking['reservation_id'] ?></div>
            <div><strong>Événement :</strong> <?= oh((string)$booking['titre']) ?></div>
            <div><strong>Date :</strong> <?= oh(date('d/m/Y H:i', strtotime((string)$booking['date_event']))) ?></div>
            <div><strong>Lieu :</strong> <?= oh((string)$booking['lieu']) ?></div>
            <div><strong>Participant :</strong> <?= oh((string)$booking['participant_nom']) ?></div>
            <?php if (!empty($booking['participant_email'])): ?>
                <div><strong>Email :</strong> <?= oh((string)$booking['participant_email']) ?></div>
            <?php endif; ?>
            <?php if ($hasPresenceStatus): ?>
                <div><strong>Statut présence :</strong> <?= oh((string)($booking['presence_status'] ?? 'pending')) ?></div>
            <?php endif; ?>
        </div>

        <?php if ($canMarkPresence && $hasPresenceStatus && ($booking['presence_status'] ?? '') !== 'present'): ?>
            <form method="POST" action="/pages/reservations/billet_verify.php" style="margin-top:16px;">
                <?php if (function_exists('csrf_token')): ?>
                    <input type="hidden" name="csrf_token" value="<?= oh((string)csrf_token()) ?>">
                <?php endif; ?>
                <input type="hidden" name="reservation_id" value="<?= (int)$booking['reservation_id'] ?>">
                <button class="btn" type="submit">Marquer présent</button>
            </form>
        <?php endif; ?>
    </div>

    <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
        <a class="btn btn-secondary" href="/pages/events/detail.php?id=<?= (int)$booking['event_id'] ?>">Voir l’événement</a>
        <a class="btn btn-secondary" href="/index.php">Retour accueil</a>
    </div>
</section>
<?php include('../../includes/footer.php'); ?>
