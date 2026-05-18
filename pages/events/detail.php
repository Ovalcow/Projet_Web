<?php
declare(strict_types=1);

include('../../config/init.php');
include('../../includes/auth_check.php');
include('../../includes/functions.php');

$pageTitle = 'Détail événement';

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function can_manage_event_detail(array $event, array $user): bool {
    $role = (string)($user['role'] ?? '');
    return $role === 'admin' || ($role === 'organisateur' && (int)$event['organizer_id'] === (int)($user['id'] ?? 0));
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    include('../../includes/header.php');
    echo '<section class="container"><p>ID événement invalide.</p></section>';
    include('../../includes/footer.php');
    exit;
}

$stmt = $bdd->prepare(
    "SELECT e.id, e.organizer_id, e.titre, e.description, e.date_event, e.lieu, e.jauge_max, e.affiche_path,
            c.nom AS category_nom,
            a.nom AS association_nom,
            COUNT(r.id) AS nb_reservations
     FROM events e
     LEFT JOIN categories c ON c.id = e.category_id
     LEFT JOIN associations a ON a.id = e.association_id
     LEFT JOIN reservations r ON r.event_id = e.id
     WHERE e.id = :id
     GROUP BY e.id
     LIMIT 1"
);
$stmt->execute(['id' => $id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();

include('../../includes/header.php');

if (!$event) {
    http_response_code(404);
    echo '<section class="container"><p>Événement introuvable.</p></section>';
    include('../../includes/footer.php');
    exit;
}

$userId = (int)($currentUser['id'] ?? 0);
$stmt = $bdd->prepare('SELECT id FROM reservations WHERE event_id = :event_id AND participant_id = :participant_id LIMIT 1');
$stmt->execute(['event_id' => $id, 'participant_id' => $userId]);
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();

$nb = (int)$event['nb_reservations'];
$max = (int)$event['jauge_max'];
$placesRestantes = max(0, $max - $nb);
$affiche = !empty($event['affiche_path'])
    ? '/uploads/' . ltrim((string)$event['affiche_path'], '/')
    : '/assets/img/default_event.jpg';
?>

<section class="container">
    <a class="btn btn-secondary" href="/pages/events/liste.php">← Retour au catalogue</a>

    <div class="event-detail" style="margin-top:18px;">
        <div style="display:grid; grid-template-columns:minmax(240px,360px) 1fr; gap:22px; align-items:start;">
            <img class="event-affiche" src="<?= h($affiche) ?>" alt="Affiche de <?= h((string)$event['titre']) ?>" style="width:100%; border-radius:14px;" />

            <div>
                <h1><?= h((string)$event['titre']) ?></h1>

                <p>📅 <strong>Date :</strong> <?= h((new DateTime((string)$event['date_event']))->format('d/m/Y H:i')) ?></p>
                <p>📍 <strong>Lieu :</strong> <?= h((string)$event['lieu']) ?></p>
                <?php if (!empty($event['category_nom'])): ?>
                    <p>🏷️ <strong>Catégorie :</strong> <?= h((string)$event['category_nom']) ?></p>
                <?php endif; ?>
                <?php if (!empty($event['association_nom'])): ?>
                    <p>👥 <strong>Association :</strong> <?= h((string)$event['association_nom']) ?></p>
                <?php endif; ?>
                <p>🎟️ <strong>Places :</strong> <?= $placesRestantes ?> restantes sur <?= $max ?></p>

                <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:16px;">
                    <?php if ($reservation): ?>
                        <span class="msg msg-success" style="margin:0;">Vous êtes inscrit</span>
                        <a class="btn btn-secondary" href="/pages/reservations/billet_qr.php?event_id=<?= $id ?>">Voir mon QR billet</a>

                        <form method="post" action="/pages/reservations/annuler.php" onsubmit="return confirm('Annuler votre réservation ?');">
                            <?php if (function_exists('csrf_input')) { echo csrf_input(); } ?>
                            <input type="hidden" name="event_id" value="<?= $id ?>" />
                            <button class="btn" type="submit">Annuler</button>
                        </form>
                    <?php elseif ($placesRestantes > 0): ?>
                        <form method="post" action="/pages/reservations/reserver.php">
                            <?php if (function_exists('csrf_input')) { echo csrf_input(); } ?>
                            <input type="hidden" name="event_id" value="<?= $id ?>" />
                            <button class="btn" type="submit">S’inscrire</button>
                        </form>
                    <?php else: ?>
                        <span class="msg msg-warning" style="margin:0;">Complet</span>
                    <?php endif; ?>

                    <?php if (can_manage_event_detail($event, $currentUser ?? [])): ?>
                        <a class="btn btn-secondary" href="/pages/events/modifier.php?id=<?= $id ?>">Modifier</a>
                        <a class="btn btn-secondary" href="/pages/reservations/inscrits.php?event_id=<?= $id ?>">Voir les inscrits</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div style="margin-top:24px;">
            <h2>Description</h2>
            <p style="white-space:pre-wrap;"><?= nl2br(h((string)$event['description'])) ?></p>
        </div>

        <div style="margin-top:24px;">
            <h2>Carte</h2>
            <div id="event-map" data-lieu="<?= h((string)$event['lieu']) ?>" style="width:100%; height:320px; border-radius:14px; border:1px solid var(--border); overflow:hidden;"></div>
            <p id="event-map-status" style="font-size:0.9rem; opacity:.8;"></p>
        </div>
    </div>
</section>

<?php if (defined('GOOGLE_MAPS_API_KEY') && GOOGLE_MAPS_API_KEY !== ''): ?>
<script>window.GOOGLE_MAPS_API_KEY = <?= json_encode(GOOGLE_MAPS_API_KEY) ?>;</script>
<?php endif; ?>
<script src="/assets/js/map-event.js"></script>

<?php include('../../includes/footer.php'); ?>
