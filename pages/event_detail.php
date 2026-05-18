<?php declare(strict_types=1);
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/functions.php';


// Détail événement.
$pageTitle = 'Détail événement';

if (!isset($_GET['id'])) {
    include('../includes/header.php');
    echo '<section class="container"><p>ID événement manquant.</p></section>';
    include('../includes/footer.php');
    exit;
}

$id = (int)$_GET['id'];

if ($id <= 0) {
    include('../includes/header.php');
    echo '<section class="container"><p>ID événement invalide.</p></section>';
    include('../includes/footer.php');
    exit;
}

$params = [':id' => $id];

if (!empty($currentUser)) {
  $params[':participant_id'] = (int)$currentUser['id'];
}

if (!empty($currentUser)) {
  $event = db_single(
    "SELECT e.id, e.titre, e.description, e.date_event, e.lieu, e.jauge_max,
            (SELECT COUNT(*) FROM reservations r WHERE r.event_id = e.id) AS nb_reservations,
            EXISTS (
              SELECT 1 FROM reservations r2
              WHERE r2.event_id = e.id AND r2.participant_id = :participant_id
            ) AS has_reservation,
            e.affiche_path,
            a.nom AS association_nom,
            c.nom AS category_nom
     FROM events e
     LEFT JOIN associations a ON a.id = e.association_id
     LEFT JOIN categories c ON c.id = e.category_id
     WHERE e.id = :id",
    $params
  );
} else {
  $event = db_single(
    "SELECT e.id, e.titre, e.description, e.date_event, e.lieu, e.jauge_max,
            (SELECT COUNT(*) FROM reservations r WHERE r.event_id = e.id) AS nb_reservations,
            0 AS has_reservation,
            e.affiche_path,
            a.nom AS association_nom,
            c.nom AS category_nom
     FROM events e
     LEFT JOIN associations a ON a.id = e.association_id
     LEFT JOIN categories c ON c.id = e.category_id
     WHERE e.id = :id",
    $params
  );
}



include('../includes/header.php');

if (!$event) {
    echo '<section class="container"><p>Événement introuvable.</p></section>';
    include('../includes/footer.php');
    exit;
}

$nb  = (int)$event['nb_reservations'];
$max = (int)$event['jauge_max'];
$placesRestantes = $max - $nb;
if ($placesRestantes < 0) { $placesRestantes = 0; }
?>

<section class="container">
  <h1 style="margin-top:0;"><?= e($event['titre']) ?></h1>

  <script>
    window.GOOGLE_MAPS_API_KEY = window.GOOGLE_MAPS_API_KEY || 'AIzaSyBhnFnOUq45lkf9MgBw7CqP6okijyM_V54';
  </script>


  <script>
    window.addEventListener('DOMContentLoaded', function () {
      var key = window.GOOGLE_MAPS_API_KEY || '';
      var statusEl = document.getElementById('event-map-status');
      function setStatus(msg) {
        if (statusEl) statusEl.textContent = msg;
      }

      if (!key) {
        setStatus('Google Maps: clé API manquante.');
        return;
      }

      var container = document.getElementById('event-map');
      if (!container) return;

      var lieu = container.getAttribute('data-lieu') || '';
      lieu = String(lieu).trim();
      if (!lieu) {
        setStatus('Adresse du lieu indisponible.');
        return;
      }

      setStatus('Chargement de la carte…');

      var script = document.createElement('script');
      script.async = true;
      script.defer = true;
      script.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(key);
      document.head.appendChild(script);

      script.onload = function () {
        if (!window.google || !window.google.maps) return;

        var geocoder = new window.google.maps.Geocoder();
        geocoder.geocode({ address: lieu }, function (results, status) {
          if (status === 'OK' && results && results[0]) {
            var result = results[0];
            var map = new window.google.maps.Map(container, {
              zoom: 16,
              center: result.geometry.location,
              mapTypeControl: false,
              streetViewControl: false,
            });

            new window.google.maps.Marker({
              map: map,
              position: result.geometry.location,
              title: lieu,
            });

            setStatus('');
            return;
          }

          setStatus('Lieu introuvable sur Google Maps.');

          // fallback centre France
          new window.google.maps.Map(container, {
            zoom: 10,
            center: { lat: 48.8566, lng: 2.3522 },
            mapTypeControl: false,
            streetViewControl: false,
          });
        });
      };

      script.onerror = function () {
        setStatus('Impossible de charger Google Maps (clé/API/billing).');
      };
    });
  </script>





  <div class="event-detail" style="margin-top:16px; display:grid; gap:14px;">

    <div style="background: rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.10); border-radius:14px; padding:14px;">
      <div style="display:flex; gap:14px; align-items:flex-start; flex-wrap:wrap;">
        <div>

          <?php if (!empty($event['affiche_path'])): ?>
            <img src="<?= e('/uploads/' . $event['affiche_path']) ?>" alt="Affiche" style="width:180px; height:180px; object-fit:cover; border-radius:14px;" />
          <?php else: ?>
            <img src="/assets/img/default_event.jpg" alt="Affiche par défaut" style="width:180px; height:180px; object-fit:cover; border-radius:14px;" />
          <?php endif; ?>
        </div>

        <div style="flex:1; min-width:240px;">
          <p style="margin:0 0 8px; color: var(--muted);">📅 <?= e((new DateTime($event['date_event']))->format('d/m/Y H:i')) ?></p>
          <p style="margin:0 0 8px; color: var(--muted);">📍 <?= e($event['lieu']) ?></p>
          <p style="margin:0 0 8px; color: var(--muted);">🏷️ Catégorie : <?= e($event['category_nom'] ?? '') ?></p>
          <p style="margin:0 0 14px; color: var(--muted);">🏛️ Association : <?= e($event['association_nom'] ?? '') ?></p>

          <div style="display:flex; gap:14px; align-items:flex-start; flex-wrap:wrap;">
            <div style="flex:1; min-width:240px;">
              <p style="margin:0 0 14px; color: var(--muted);">Places restantes : <strong><?= $placesRestantes ?></strong> / <?= $max ?></p>

              <a class="btn btn-secondary" href="/pages/events.php" style="margin-right:10px;">Retour</a>

              <!-- QR code billet (si réservation existante) -->
              <?php if (!empty($currentUser)): ?>
                <?php $hasReservation = !empty($event['has_reservation']); ?>

                <?php if ($hasReservation): ?>
                  <a class="btn btn-secondary" href="/pages/billet_qr.php?event_id=<?= (int)$event['id'] ?>" style="margin-right:10px;">Voir QR billet</a>
                <?php else: ?>
                  <form method="POST" action="/pages/event_join.php" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />
                    <input type="hidden" name="event_id" value="<?= (int)$event['id'] ?>" />
                    <button class="btn" type="submit" <?= ($placesRestantes <= 0 ? 'disabled title="Plus de places disponibles"' : '') ?>>S’inscrire</button>
                  </form>
                <?php endif; ?>
              <?php else: ?>
                <button class="btn" type="button" disabled title="Connecte-toi pour générer le QR">Connecte-toi</button>
              <?php endif; ?>
            </div>

            <div style="flex:1; min-width:280px;">
              <h2 style="font-size:16px; margin:0 0 8px;">Carte</h2>
              <div id="event-map" data-lieu="<?= e((string)$event['lieu']) ?>" style="width:100%; height:260px; border-radius:14px; overflow:hidden; border:1px solid var(--border); background: rgba(255,255,255,.02);"></div>
              <div id="event-map-status" style="margin-top:8px; color: var(--muted); font-size:12px;"></div>
            </div>
          </div>
        </div>
      </div>

        <div style="margin-top:14px;">
            <h2>Description</h2>
            <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
        </div>
    </div>
</section>

<?php include('../includes/footer.php'); ?>