<?php
declare(strict_types=1);

include('../../config/init.php');

$pageTitle = 'Événements';

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

$q           = trim((string)($_GET['q'] ?? ''));
$date        = trim((string)($_GET['date'] ?? ''));
$category    = trim((string)($_GET['category'] ?? ''));
$association = trim((string)($_GET['association'] ?? ''));

$sql = "SELECT e.id, e.titre, e.description, e.date_event, e.lieu, e.jauge_max,
               e.affiche_path,
               a.nom AS association_nom,
               c.nom AS category_nom,
               (SELECT COUNT(*) FROM reservations r WHERE r.event_id = e.id) AS nb_reservations
        FROM events e
        LEFT JOIN associations a ON a.id = e.association_id
        LEFT JOIN categories c ON c.id = e.category_id
        WHERE 1=1";

$params = [];

if ($q !== '') {
    $sql .= " AND (e.titre LIKE :q OR e.lieu LIKE :q OR e.description LIKE :q)";
    $params['q'] = '%' . $q . '%';
}

if ($date !== '') {
    $sql .= " AND e.date_event >= :d0 AND e.date_event < :d1";
    $params['d0'] = $date . ' 00:00:00';
    $params['d1'] = date('Y-m-d 00:00:00', strtotime($date . ' +1 day'));
}

if ($category !== '') {
    $sql .= " AND e.category_id = :category_id";
    $params['category_id'] = (int)$category;
}

if ($association !== '') {
    $sql .= " AND e.association_id = :association_id";
    $params['association_id'] = (int)$association;
}

$sql .= " ORDER BY e.date_event ASC LIMIT 100";

$requete = $bdd->prepare($sql);
$requete->execute($params);
$events = $requete->fetchAll(PDO::FETCH_ASSOC);
$requete->closeCursor();

$repCategories = $bdd->query('SELECT id, nom FROM categories ORDER BY nom');
$categories = $repCategories->fetchAll(PDO::FETCH_ASSOC);
$repCategories->closeCursor();

$repAssociations = $bdd->query('SELECT id, nom FROM associations ORDER BY nom');
$associations = $repAssociations->fetchAll(PDO::FETCH_ASSOC);
$repAssociations->closeCursor();

include('../../includes/header.php');
?>

<section class="hero">
    <div class="container">
        <h1>Catalogue événements</h1>
        <p>Recherchez et filtrez les événements par date, catégorie ou association.</p>
    </div>
</section>

<section class="container">
    <form class="search-form" action="/pages/events/liste.php" method="GET">
        <label>
            <span>Recherche</span>
            <input type="text" name="q" placeholder="Titre, lieu, description…" value="<?php echo h($q); ?>" />
        </label>

        <label>
            <span>Date</span>
            <input type="date" name="date" value="<?php echo h($date); ?>" />
        </label>

        <label>
            <span>Catégorie</span>
            <select name="category">
                <option value="">Toutes</option>
                <?php foreach ($categories as $cat): ?>
                    <?php $catId = (int)$cat['id']; ?>
                    <option value="<?php echo $catId; ?>" <?php echo ((string)$catId === $category) ? 'selected' : ''; ?>>
                        <?php echo h($cat['nom']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            <span>Association</span>
            <select name="association">
                <option value="">Toutes</option>
                <?php foreach ($associations as $asso): ?>
                    <?php $assoId = (int)$asso['id']; ?>
                    <option value="<?php echo $assoId; ?>" <?php echo ((string)$assoId === $association) ? 'selected' : ''; ?>>
                        <?php echo h($asso['nom']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <button class="btn" type="submit">Rechercher</button>
        <a class="btn btn-secondary" href="/pages/events/liste.php">Réinitialiser</a>
    </form>

    <?php if (!empty($currentUser) && in_array($currentUser['role'] ?? '', ['organisateur', 'admin'], true)): ?>
        <div style="margin: 8px 0 16px;">
            <a class="btn btn-secondary" href="/pages/events/creer.php">Créer un événement</a>
        </div>
    <?php endif; ?>

    <div class="events-grid">
        <?php if (empty($events)): ?>
            <p>Aucun événement trouvé.</p>
        <?php endif; ?>

        <?php foreach ($events as $ev): ?>
            <?php
                $nb = (int)$ev['nb_reservations'];
                $max = max(0, (int)$ev['jauge_max']);
                $placesRestantes = max(0, $max - $nb);
            ?>
            <article class="event-card">
                <img
                    class="event-affiche"
                    src="<?php echo h(event_affiche_url($ev['affiche_path'] ?? null)); ?>"
                    alt="Affiche de <?php echo h($ev['titre']); ?>"
                />

                <div class="event-meta">
                    <h2 class="event-title"><?php echo h($ev['titre']); ?></h2>
                    <p class="event-date"> <?php echo h(date('d/m/Y H:i', strtotime((string)$ev['date_event']))); ?></p>
                    <p class="event-lieu"> <?php echo h($ev['lieu']); ?></p>
                    <p> <?php echo h($ev['category_nom'] ?? 'Sans catégorie'); ?></p>
                    <p> <?php echo h($ev['association_nom'] ?? 'Sans association'); ?></p>
                    <p class="event-capacity">
                        Places restantes :
                        <strong><?php echo $placesRestantes; ?></strong>
                        / <?php echo $max; ?>
                    </p>

                    <a class="btn btn-secondary" href="/pages/events/detail.php?id=<?php echo (int)$ev['id']; ?>">Voir</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php include('../../includes/footer.php'); ?>
