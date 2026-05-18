<?php
require_once __DIR__ . '/config/init.php';

$pageTitle = 'Accueil';

function h_index($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function table_exists_index(PDO $bdd, string $table): bool {
    try {
        $stmt = $bdd->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function fetch_filter_options_index(PDO $bdd, string $table): array {
    if (!table_exists_index($bdd, $table)) {
        return [];
    }

    try {
        $stmt = $bdd->query("SELECT id, nom FROM {$table} ORDER BY nom ASC");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (Throwable $e) {
        return [];
    }
}

$events = [];
$carouselEvents = [];
$categories = fetch_filter_options_index($bdd, 'categories');
$associations = fetch_filter_options_index($bdd, 'associations');

try {
    $sql = "
        SELECT
            e.id,
            e.titre,
            e.description,
            e.date_event,
            e.lieu,
            e.jauge_max,
            e.affiche_path,
            COALESCE(c.nom, '') AS categorie_nom,
            COALESCE(a.nom, '') AS association_nom,
            COUNT(r.id) AS nb_reservations
        FROM events e
        LEFT JOIN reservations r ON r.event_id = e.id
        LEFT JOIN categories c ON c.id = e.category_id
        LEFT JOIN associations a ON a.id = e.association_id
        WHERE e.date_event >= NOW()
        GROUP BY e.id, e.titre, e.description, e.date_event, e.lieu, e.jauge_max, e.affiche_path, c.nom, a.nom
        ORDER BY e.date_event ASC
        LIMIT 10
    ";

    $stmt = $bdd->query($sql);
    $events = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    $carouselEvents = array_slice($events, 0, 5);
} catch (Throwable $e) {
    // Fallback si les colonnes category_id / association_id n'existent pas encore.
    $sql = "
        SELECT
            e.id,
            e.titre,
            e.description,
            e.date_event,
            e.lieu,
            e.jauge_max,
            e.affiche_path,
            '' AS categorie_nom,
            '' AS association_nom,
            COUNT(r.id) AS nb_reservations
        FROM events e
        LEFT JOIN reservations r ON r.event_id = e.id
        WHERE e.date_event >= NOW()
        GROUP BY e.id, e.titre, e.description, e.date_event, e.lieu, e.jauge_max, e.affiche_path
        ORDER BY e.date_event ASC
        LIMIT 10
    ";

    $stmt = $bdd->query($sql);
    $events = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    $carouselEvents = array_slice($events, 0, 5);
}

include __DIR__ . '/includes/header.php';
?>

<section class="hero home-hero">
    <div class="container">
        <p class="eyebrow">OmnesEvent</p>
        <h1>Découvre les prochains événements étudiants</h1>
        <p>Recherche un événement, consulte les détails, réserve ta place et retrouve tes billets depuis ton espace.</p>
        <div class="hero-actions">
            <a class="btn" href="/pages/events/liste.php">Voir tous les événements</a>
            <?php if (!empty($_SESSION['user']) && in_array($_SESSION['user']['role'] ?? '', ['organisateur', 'admin'], true)): ?>
                <a class="btn btn-secondary" href="/pages/events/creer.php">Créer un événement</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="container home-search-section">
    <form class="search-form" action="/pages/events/liste.php" method="GET">
        <label>
            <span>Recherche</span>
            <input type="text" name="q" placeholder="Titre, lieu, association…" autocomplete="off">
        </label>

        <label>
            <span>Date</span>
            <input type="date" name="date">
        </label>

        <?php if (!empty($categories)): ?>
            <label>
                <span>Catégorie</span>
                <select name="category">
                    <option value="">Toutes</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>"><?= h_index($cat['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>

        <?php if (!empty($associations)): ?>
            <label>
                <span>Association</span>
                <select name="association">
                    <option value="">Toutes</option>
                    <?php foreach ($associations as $association): ?>
                        <option value="<?= (int)$association['id'] ?>"><?= h_index($association['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>

        <button class="btn" type="submit">Rechercher</button>
    </form>
</section>

<?php if (!empty($carouselEvents)): ?>
    <section class="container featured-events" aria-label="Événements mis en avant">
        <div class="section-heading">
            <h2>À ne pas manquer</h2>
            <a href="/pages/events/liste.php">Catalogue complet</a>
        </div>

        <div class="carrousel-wrapper">
            <button type="button" class="carrousel-btn" id="prev" aria-label="Événement précédent">‹</button>

            <div class="carrousel-events">
                <?php foreach ($carouselEvents as $index => $event):
                    $reservations = (int)$event['nb_reservations'];
                    $capacity = max(0, (int)$event['jauge_max']);
                    $remaining = max(0, $capacity - $reservations);
                    $image = !empty($event['affiche_path'])
                        ? '/uploads/events/' . rawurlencode($event['affiche_path'])
                        : '/assets/img/default_event.jpg';
                ?>
                    <article class="event-slide <?= $index === 0 ? 'active' : '' ?>" aria-hidden="<?= $index === 0 ? 'false' : 'true' ?>">
                        <img src="<?= h_index($image) ?>" alt="Affiche de <?= h_index($event['titre']) ?>">
                        <div class="event-slide-content">
                            <?php if (!empty($event['categorie_nom'])): ?>
                                <span class="badge"><?= h_index($event['categorie_nom']) ?></span>
                            <?php endif; ?>
                            <h3><?= h_index($event['titre']) ?></h3>
                            <p><?= h_index(mb_strimwidth(strip_tags((string)$event['description']), 0, 150, '…', 'UTF-8')) ?></p>
                            <ul class="event-slide-meta">
                                <li><?= date('d/m/Y H:i', strtotime($event['date_event'])) ?></li>
                                <li><?= h_index($event['lieu']) ?></li>
                                <li><?= $remaining ?> place<?= $remaining > 1 ? 's' : '' ?> restante<?= $remaining > 1 ? 's' : '' ?></li>
                            </ul>
                            <a class="btn btn-secondary" href="/pages/events/detail.php?id=<?= (int)$event['id'] ?>">Voir le détail</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <button type="button" class="carrousel-btn" id="suiv" aria-label="Événement suivant">›</button>
        </div>

        <div class="carrousel-points" aria-label="Navigation du carrousel">
            <?php foreach ($carouselEvents as $index => $_): ?>
                <button type="button" class="point <?= $index === 0 ? 'active' : '' ?>" aria-label="Afficher l’événement <?= $index + 1 ?>" aria-current="<?= $index === 0 ? 'true' : 'false' ?>"></button>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<section class="container upcoming-events">
    <div class="section-heading">
        <h2>Prochains événements</h2>
        <a href="/pages/events/liste.php">Tout voir</a>
    </div>

    <?php if (empty($events)): ?>
        <p class="empty-state">Aucun événement à venir pour le moment.</p>
    <?php else: ?>
        <div class="events-grid">
            <?php foreach ($events as $event):
                $reservations = (int)$event['nb_reservations'];
                $capacity = max(0, (int)$event['jauge_max']);
                $remaining = max(0, $capacity - $reservations);
                $image = !empty($event['affiche_path'])
                    ? '/uploads/events/' . rawurlencode($event['affiche_path'])
                    : '/assets/img/default_event.jpg';
            ?>
                <article class="event-card">
                    <img class="event-affiche" src="<?= h_index($image) ?>" alt="Affiche de <?= h_index($event['titre']) ?>">
                    <div class="event-meta">
                        <?php if (!empty($event['association_nom'])): ?>
                            <p class="event-association"><?= h_index($event['association_nom']) ?></p>
                        <?php endif; ?>
                        <h3 class="event-title"><?= h_index($event['titre']) ?></h3>
                        <p class="event-date"><?= date('d/m/Y H:i', strtotime($event['date_event'])) ?></p>
                        <p class="event-lieu"><?= h_index($event['lieu']) ?></p>
                        <p class="event-capacity">Places restantes : <strong><?= $remaining ?></strong></p>
                    </div>
                    <a class="btn btn-secondary" href="/pages/events/detail.php?id=<?= (int)$event['id'] ?>">Voir</a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<script src="/assets/js/jquery.js"></script>
<script src="/assets/js/carrousel_events.js"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>
