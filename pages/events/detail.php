<?php
declare(strict_types=1);

include('../../config/init.php');
include('../../includes/functions.php');

$pageTitle = 'Détail événement';

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function event_affiche_url(?string $path): string {
    $path = trim((string)$path);
    if ($path === '') {
        return '/assets/img/default_event.jpg';
    }
    if (preg_match('#^(https?://|/)#', $path)) {
        return $path;
    }
    if (str_starts_with($path, 'events/')) {
        return '/uploads/' . $path;
    }
    return '/uploads/events/' . $path;
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    include('../../includes/header.php');
    echo '<section class="container"><p>ID événement invalide.</p></section>';
    include('../../includes/footer.php');
    exit;
}

$params = ['id' => $id];

$sql = "SELECT e.id, e.titre, e.description, e.date_event, e.lieu, e.jauge_max,
               e.affiche_path,
               a.nom AS association_nom,
               c.nom AS category_nom,
               (SELECT COUNT(*) FROM reservations r WHERE r.event_id = e.id) AS nb_reservations";

if (!empty($currentUser)) {
    $sql .= ", EXISTS (
                SELECT 1
                FROM reservations r2
                WHERE r2.event_id = e.id
                  AND r2.participant_id = :participant_id
              ) AS has_reservation";
    $params['participant_id'] = (int)$currentUser['id'];
} else {
    $sql .= ", 0 AS has_reservation";
}

$sql .= " FROM events e
          LEFT JOIN associations a ON a.id = e.association_id
          LEFT JOIN categories c ON c.id = e.category_id
          WHERE e.id = :id";

$requete = $bdd->prepare($sql);
$requete->execute($params);
$event = $requete->fetch(PDO::FETCH_ASSOC);
$requete->closeCursor();

include('../../includes/header.php');

if (!$event) {
    echo '<section class="container"><p>Événement introuvable.</p></section>';
    include('../../includes/footer.php');
    exit;
}

$nb = (int)$event['nb_reservations'];
$max = max(0, (int)$event['jauge_max']);
$placesRestantes = max(0, $max - $nb);
$hasReservation = !empty($event['has_reservation']);

$googleMapsKey = '';
if (defined('GOOGLE_MAPS_API_KEY')) {
    $googleMapsKey = (string)GOOGLE_MAPS_API_KEY;
} elseif (getenv('GOOGLE_MAPS_API_KEY')) {
    $googleMapsKey = (string)getenv('GOOGLE_MAPS_API_KEY');
}
?>

<section class="container">
    <h1><?php echo h($event['titre']); ?></h1>

    <div class="event-detail">
        <div style="display:flex; gap:14px; align-items:flex-start; flex-wrap:wrap;">
            <div>
                <img
                    src="<?php echo h(event_affiche_url($event['affiche_path'] ?? null)); ?>"
                    alt="Affiche de <?php echo h($event['titre']); ?>"
                    style="width:180px; height:180px; object-fit:cover; border-radius:14px;"
                />
            </div>

            <div style="flex:1; min-width:260px;">
                <p>📅 <?php echo h(date('d/m/Y H:i', strtotime((string)$event['date_event']))); ?></p>
                <p>📍 <?php echo h($event['lieu']); ?></p>
                <p>🏷️ Catégorie : <?php echo h($event['category_nom'] ?? 'Sans catégorie'); ?></p>
                <p>🏛️ Association : <?php echo h($event['association_nom'] ?? 'Sans association'); ?></p>
                <p>Places restantes : <strong><?php echo $placesRestantes; ?></strong> / <?php echo $max; ?></p>

                <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                    <a class="btn btn-secondary" href="/pages/events/liste.php">Retour</a>

                    <?php if (empty($currentUser)): ?>
                        <a class="btn" href="/pages/auth/login.php">Se connecter pour s’inscrire</a>
                    <?php elseif ($hasReservation): ?>
                        <span class="msg msg-success" style="margin:0;">Vous êtes inscrit</span>
                        <a class="btn btn-secondary" href="/pages/reservations/billet_qr.php?event_id=<?php echo (int)$event['id']; ?>">Voir QR billet</a>
                        <a class="btn" href="/pages/reservations/annuler.php?event_id=<?php echo (int)$event['id']; ?>">Annuler</a>
                    <?php elseif ($placesRestantes > 0): ?>
                        <form method="POST" action="/pages/reservations/reserver.php" style="display:inline;">
                            <?php if (function_exists('csrf_token')): ?>
                                <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>" />
                            <?php endif; ?>
                            <input type="hidden" name="event_id" value="<?php echo (int)$event['id']; ?>" />
                            <button class="btn" type="submit">S’inscrire</button>
                        </form>
                    <?php else: ?>
                        <span class="msg msg-warning" style="margin:0;">Complet</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div style="margin-top:18px;">
            <h2>Description</h2>
            <p style="white-space:pre-wrap;"><?php echo nl2br(h($event['description'])); ?></p>
        </div>

        <div style="margin-top:18px;">
            <h2>Carte</h2>
            <div
                id="event-map"
                data-lieu="<?php echo h($event['lieu']); ?>"
                style="width:100%; height:280px; border-radius:14px; border:1px solid var(--border); overflow:hidden;"
            ></div>
            <p id="event-map-status" style="font-size:0.9rem; opacity:.8;"></p>
        </div>
    </div>
</section>

<script>
(function () {
    const key = <?php echo json_encode($googleMapsKey, JSON_UNESCAPED_SLASHES); ?>;
    const mapEl = document.getElementById('event-map');
    const statusEl = document.getElementById('event-map-status');

    function status(message) {
        if (statusEl) statusEl.textContent = message;
    }

    if (!mapEl) return;

    if (!key) {
        status('Google Maps non configuré : définis GOOGLE_MAPS_API_KEY dans config/init.php ou dans les variables d’environnement.');
        return;
    }

    const lieu = (mapEl.dataset.lieu || '').trim();
    if (!lieu) {
        status('Adresse indisponible.');
        return;
    }

    status('Chargement de la carte…');

    window.initEventMap = function () {
        const geocoder = new google.maps.Geocoder();

        geocoder.geocode({ address: lieu }, function (results, geocodeStatus) {
            if (geocodeStatus !== 'OK' || !results || !results[0]) {
                status('Lieu introuvable sur Google Maps.');
                return;
            }

            const position = results[0].geometry.location;
            const map = new google.maps.Map(mapEl, {
                zoom: 16,
                center: position,
                mapTypeControl: false,
                streetViewControl: false
            });

            new google.maps.Marker({
                map: map,
                position: position,
                title: lieu
            });

            status('');
        });
    };

    const script = document.createElement('script');
    script.async = true;
    script.defer = true;
    script.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(key) + '&callback=initEventMap';
    script.onerror = function () {
        status('Impossible de charger Google Maps.');
    };
    document.head.appendChild(script);
})();
</script>

<?php include('../../includes/footer.php'); ?>
