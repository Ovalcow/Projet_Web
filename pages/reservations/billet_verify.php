<?php
declare(strict_types=1);

include('../../config/init.php');
include('../../includes/functions.php');

$pageTitle = 'Vérification billet';

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function verify_has_column(PDO $bdd, string $table, string $column): bool {
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
        return $cache[$key] = ((int)$stmt->fetchColumn() > 0);
    } catch (Throwable $e) {
        return $cache[$key] = false;
    }
}

function can_mark_presence(array $booking, array $user): bool {
    $role = (string)($user['role'] ?? '');
    if ($role === 'admin') {
        return true;
    }
    return $role === 'organisateur' && (int)($booking['organizer_id'] ?? 0) === (int)($user['id'] ?? 0);
}

$reservationId = (int)($_GET['reservation_id'] ?? $_POST['reservation_id'] ?? 0);
$hasPresenceStatus = verify_has_column($bdd, 'reservations', 'presence_status');

if ($reservationId <= 0) {
    http_response_code(400);
    include('../../includes/header.php');
    echo '<section class="container"><p>reservation_id manquant.</p></section>';
    include('../../includes/footer.php');
    exit;
}

$stmt = $bdd->prepare(
    "SELECT r.id AS reservation_id,
            r.event_id,
            r.participant_id,
            " . ($hasPresenceStatus ? "r.presence_status," : "'unknown' AS presence_status,") . "
            e.titre,
            e.date_event,
            e.lieu,
            e.organizer_id,
            u.nom AS participant_nom,
            u.email AS participant_email
     FROM reservations r
     JOIN events e ON e.id = r.event_id
     JOIN users u ON u.id = r.participant_id
     WHERE r.id = :reservation_id
     LIMIT 1"
);
$stmt->execute(['reservation_id' => $reservationId]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();

if (!$booking) {
    http_response_code(404);
    include('../../includes/header.php');
    echo '<section class="container"><h1>Billet invalide ❌</h1><p>Aucune réservation ne correspond à ce QR code.</p></section>';
    include('../../includes/footer.php');
    exit;
}

$current = $currentUser ?? [];
$canMark = $hasPresenceStatus && can_mark_presence($booking, $current);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canMark) {
    if (!function_exists('csrf_verify') || csrf_verify()) {
        $update = $bdd->prepare("UPDATE reservations SET presence_status = 'present' WHERE id = :id");
        $update->execute(['id' => $reservationId]);
        $booking['presence_status'] = 'present';
    }
}

include('../../includes/header.php');
?>

<section class="container">
    <h1>Billet vérifié ✅</h1>

    <div style="padding:16px; border:1px solid var(--border); border-radius:14px; max-width:820px;">
        <p><strong>Réservation :</strong> #<?= (int)$booking['reservation_id'] ?></p>
        <p><strong>Événement :</strong> <?= h((string)$booking['titre']) ?></p>
        <p><strong>Date :</strong> <?= h((new DateTime((string)$booking['date_event']))->format('d/m/Y H:i')) ?></p>
        <p><strong>Lieu :</strong> <?= h((string)$booking['lieu']) ?></p>
        <p><strong>Participant :</strong> <?= h((string)$booking['participant_nom']) ?> — <?= h((string)$booking['participant_email']) ?></p>
        <?php if ($hasPresenceStatus): ?>
            <p><strong>Présence :</strong> <?= h((string)$booking['presence_status']) ?></p>
        <?php endif; ?>
    </div>

    <?php if ($canMark): ?>
        <form method="post" style="margin-top:16px;">
            <?php if (function_exists('csrf_input')) { echo csrf_input(); } ?>
            <input type="hidden" name="reservation_id" value="<?= (int)$booking['reservation_id'] ?>" />
            <button class="btn" type="submit">Marquer présent</button>
        </form>
    <?php elseif (!$hasPresenceStatus): ?>
        <p style="margin-top:12px;">La colonne <code>presence_status</code> n’existe pas encore : la vérification fonctionne, mais le marquage de présence est désactivé.</p>
    <?php endif; ?>

    <div style="margin-top:16px;">
        <a class="btn btn-secondary" href="/index.php">Retour accueil</a>
    </div>
</section>

<?php include('../../includes/footer.php'); ?>
