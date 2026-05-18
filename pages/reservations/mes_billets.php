<?php
/**
 * pages/reservations/mes_billets.php
 * Liste les réservations/billets du participant connecté.
 */
declare(strict_types=1);

include('../../config/init.php');
include('../../includes/auth_check.php');
include('../../includes/functions.php');

if (function_exists('require_login')) {
    require_login();
}

$pageTitle = 'Mes billets';

function oh(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function has_column(PDO $bdd, string $table, string $column): bool
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

$userId = (int)($currentUser['id'] ?? 0);
$hasPresenceStatus = has_column($bdd, 'reservations', 'presence_status');
$presenceSelect = $hasPresenceStatus ? ', r.presence_status' : '';

$stmt = $bdd->prepare(
    "SELECT r.id AS reservation_id,
            r.created_at,
            e.id AS event_id,
            e.titre,
            e.date_event,
            e.lieu,
            e.affiche_path
            $presenceSelect
     FROM reservations r
     JOIN events e ON e.id = r.event_id
     WHERE r.participant_id = :participant_id
     ORDER BY e.date_event ASC"
);
$stmt->execute(['participant_id' => $userId]);
$billets = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

include('../../includes/header.php');
?>
<section class="container">
    <h1>Mes billets</h1>

    <?php if (empty($billets)): ?>
        <p>Vous n’avez aucun billet pour le moment.</p>
        <a class="btn" href="/pages/events/liste.php">Voir les événements</a>
    <?php else: ?>
        <div class="events-grid">
            <?php foreach ($billets as $billet): ?>
                <article class="event-card">
                    <?php
                    $affiche = trim((string)($billet['affiche_path'] ?? ''));
                    if ($affiche === '') {
                        $afficheUrl = '/assets/img/default_event.jpg';
                    } elseif (preg_match('#^(https?://|/)#', $affiche)) {
                        $afficheUrl = $affiche;
                    } elseif (str_starts_with($affiche, 'events/')) {
                        $afficheUrl = '/uploads/' . $affiche;
                    } else {
                        $afficheUrl = '/uploads/events/' . $affiche;
                    }
                    ?>
                    <img class="event-affiche" src="<?= oh($afficheUrl) ?>" alt="Affiche <?= oh((string)$billet['titre']) ?>">
                    <div class="event-meta">
                        <h2 class="event-title"><?= oh((string)$billet['titre']) ?></h2>
                        <p><?= oh(date('d/m/Y H:i', strtotime((string)$billet['date_event']))) ?></p>
                        <p> <?= oh((string)$billet['lieu']) ?></p>
                        <p> Réservation #<?= (int)$billet['reservation_id'] ?></p>
                        <?php if ($hasPresenceStatus): ?>
                            <p>Statut : <?= oh((string)($billet['presence_status'] ?? 'pending')) ?></p>
                        <?php endif; ?>

                        <div style="display:flex; gap:8px; flex-wrap:wrap;">
                            <a class="btn btn-secondary" href="/pages/events/detail.php?id=<?= (int)$billet['event_id'] ?>">Détail</a>
                            <a class="btn" href="/pages/reservations/billet_qr.php?event_id=<?= (int)$billet['event_id'] ?>">QR billet</a>
                            <form method="POST" action="/pages/reservations/annuler.php" onsubmit="return confirm('Annuler cette réservation ?');">
                                <?php if (function_exists('csrf_token')): ?>
                                    <input type="hidden" name="csrf_token" value="<?= oh((string)csrf_token()) ?>">
                                <?php endif; ?>
                                <input type="hidden" name="event_id" value="<?= (int)$billet['event_id'] ?>">
                                <button class="btn btn-secondary" type="submit">Annuler</button>
                            </form>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php include('../../includes/footer.php'); ?>
