<?php
/**
 * pages/reservations/inscrits.php
 * Liste les inscrits à un événement pour l'organisateur propriétaire ou l'admin.
 */
declare(strict_types=1);

include('../../config/init.php');
include('../../includes/auth_check.php');
include('../../includes/functions.php');

$pageTitle = 'Inscrits événement';

function oh(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function can_manage_event(array $event, array $user): bool
{
    $role = (string)($user['role'] ?? '');
    if ($role === 'admin') {
        return true;
    }

    return $role === 'organisateur' && (int)($event['organizer_id'] ?? 0) === (int)($user['id'] ?? 0);
}

function table_has_column(PDO $bdd, string $table, string $column): bool
{
    try {
        $stmt = $bdd->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table_name
               AND COLUMN_NAME = :column_name"
        );
        $stmt->execute(['table_name' => $table, 'column_name' => $column]);
        return ((int)$stmt->fetchColumn() > 0);
    } catch (Throwable $e) {
        return false;
    }
}

$eventId = (int)($_GET['event_id'] ?? 0);
if ($eventId <= 0) {
    header('Location: /pages/events/liste.php');
    exit;
}

$stmt = $bdd->prepare('SELECT id, titre, date_event, organizer_id FROM events WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $eventId]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();

if (!$event) {
    http_response_code(404);
    include('../../includes/header.php');
    echo '<section class="container"><p>Événement introuvable.</p></section>';
    include('../../includes/footer.php');
    exit;
}

if (!can_manage_event($event, $currentUser ?? [])) {
    http_response_code(403);
    include('../../includes/header.php');
    echo '<section class="container"><p>Accès interdit.</p></section>';
    include('../../includes/footer.php');
    exit;
}

$hasPresenceStatus = table_has_column($bdd, 'reservations', 'presence_status');
$presenceSelect = $hasPresenceStatus ? ', r.presence_status' : '';

$stmt = $bdd->prepare(
    "SELECT r.id AS reservation_id,
            r.created_at,
            u.id AS user_id,
            u.nom,
            u.email
            $presenceSelect
     FROM reservations r
     JOIN users u ON u.id = r.participant_id
     WHERE r.event_id = :event_id
     ORDER BY r.created_at ASC, u.nom ASC"
);
$stmt->execute(['event_id' => $eventId]);
$inscrits = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

include('../../includes/header.php');
?>
<section class="container">
    <h1>Inscrits — <?= oh((string)$event['titre']) ?></h1>
    <p>📅 <?= oh(date('d/m/Y H:i', strtotime((string)$event['date_event']))) ?></p>
    <p><strong><?= count($inscrits) ?></strong> inscrit(s)</p>

    <?php if (empty($inscrits)): ?>
        <p>Aucun inscrit pour le moment.</p>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">#</th>
                        <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">Participant</th>
                        <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">Email</th>
                        <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">Réservation</th>
                        <?php if ($hasPresenceStatus): ?>
                            <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">Présence</th>
                        <?php endif; ?>
                        <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">QR</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inscrits as $index => $row): ?>
                        <tr>
                            <td style="padding:10px; border-bottom:1px solid var(--border);"><?= $index + 1 ?></td>
                            <td style="padding:10px; border-bottom:1px solid var(--border);"><?= oh((string)$row['nom']) ?></td>
                            <td style="padding:10px; border-bottom:1px solid var(--border);"><?= oh((string)$row['email']) ?></td>
                            <td style="padding:10px; border-bottom:1px solid var(--border);">#<?= (int)$row['reservation_id'] ?></td>
                            <?php if ($hasPresenceStatus): ?>
                                <td style="padding:10px; border-bottom:1px solid var(--border);"><?= oh((string)($row['presence_status'] ?? 'pending')) ?></td>
                            <?php endif; ?>
                            <td style="padding:10px; border-bottom:1px solid var(--border);">
                                <a class="btn btn-secondary" href="/pages/reservations/billet_verify.php?reservation_id=<?= (int)$row['reservation_id'] ?>">Vérifier</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div style="margin-top:16px;">
        <a class="btn btn-secondary" href="/pages/events/detail.php?id=<?= (int)$eventId ?>">Retour événement</a>
    </div>
</section>
<?php include('../../includes/footer.php'); ?>
