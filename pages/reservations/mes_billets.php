<?php
declare(strict_types=1);

include('../../config/init.php');
include('../../includes/auth_check.php');
include('../../includes/functions.php');

$pageTitle = 'Mes billets';

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$userId = (int)($currentUser['id'] ?? 0);

$stmt = $bdd->prepare(
    "SELECT r.id AS reservation_id, r.event_id, r.created_at,
            e.titre, e.date_event, e.lieu, e.affiche_path,
            c.nom AS category_nom,
            a.nom AS association_nom
     FROM reservations r
     JOIN events e ON e.id = r.event_id
     LEFT JOIN categories c ON c.id = e.category_id
     LEFT JOIN associations a ON a.id = e.association_id
     WHERE r.participant_id = :participant_id
     ORDER BY e.date_event ASC"
);
$stmt->execute(['participant_id' => $userId]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

include('../../includes/header.php');
?>

<section class="container">
    <h1>Mes billets</h1>

    <?php if (empty($tickets)): ?>
        <p>Vous n’avez pas encore de billet.</p>
        <a class="btn btn-secondary" href="/pages/events/liste.php">Voir les événements</a>
    <?php else: ?>
        <div class="event-grid">
            <?php foreach ($tickets as $ticket): ?>
                <?php
                    $affiche = !empty($ticket['affiche_path'])
                        ? '/uploads/' . ltrim((string)$ticket['affiche_path'], '/')
                        : '/assets/img/default_event.jpg';
                ?>
                <article class="event-card">
                    <img class="event-affiche" src="<?= h($affiche) ?>" alt="Affiche de <?= h((string)$ticket['titre']) ?>" />
                    <div class="event-meta">
                        <h2 class="event-title"><?= h((string)$ticket['titre']) ?></h2>
                        <p>📅 <?= h((new DateTime((string)$ticket['date_event']))->format('d/m/Y H:i')) ?></p>
                        <p>📍 <?= h((string)$ticket['lieu']) ?></p>
                        <?php if (!empty($ticket['category_nom'])): ?>
                            <p>🏷️ <?= h((string)$ticket['category_nom']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($ticket['association_nom'])): ?>
                            <p>👥 <?= h((string)$ticket['association_nom']) ?></p>
                        <?php endif; ?>
                        <p>Réservation #<?= (int)$ticket['reservation_id'] ?></p>

                        <div style="display:flex; gap:10px; flex-wrap:wrap;">
                            <a class="btn btn-secondary" href="/pages/events/detail.php?id=<?= (int)$ticket['event_id'] ?>">Détail</a>
                            <a class="btn" href="/pages/reservations/billet_qr.php?event_id=<?= (int)$ticket['event_id'] ?>">QR billet</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php include('../../includes/footer.php'); ?>
