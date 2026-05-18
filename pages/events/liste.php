<?php
declare(strict_types=1);

include('../../config/init.php');
include('../../includes/functions.php');

$pageTitle = 'Catalogue événements';

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$q = trim((string)($_GET['q'] ?? ''));
$dateMin = trim((string)($_GET['date_min'] ?? ''));
$dateMax = trim((string)($_GET['date_max'] ?? ''));
$categoryId = (int)($_GET['category_id'] ?? 0);
$associationId = (int)($_GET['association_id'] ?? 0);

$where = ['e.date_event >= NOW()'];
$params = [];

if ($q !== '') {
    $where[] = '(e.titre LIKE :q OR e.description LIKE :q OR e.lieu LIKE :q)';
    $params['q'] = '%' . $q . '%';
}
if ($dateMin !== '') {
    $where[] = 'e.date_event >= :date_min';
    $params['date_min'] = $dateMin . (strlen($dateMin) === 10 ? ' 00:00:00' : '');
}
if ($dateMax !== '') {
    $where[] = 'e.date_event <= :date_max';
    $params['date_max'] = $dateMax . (strlen($dateMax) === 10 ? ' 23:59:59' : '');
}
if ($categoryId > 0) {
    $where[] = 'e.category_id = :category_id';
    $params['category_id'] = $categoryId;
}
if ($associationId > 0) {
    $where[] = 'e.association_id = :association_id';
    $params['association_id'] = $associationId;
}

$sql = "SELECT e.id, e.titre, e.description, e.date_event, e.lieu, e.jauge_max, e.affiche_path,
               c.nom AS category_nom,
               a.nom AS association_nom,
               COUNT(r.id) AS nb_reservations
        FROM events e
        LEFT JOIN categories c ON c.id = e.category_id
        LEFT JOIN associations a ON a.id = e.association_id
        LEFT JOIN reservations r ON r.event_id = e.id
        WHERE " . implode(' AND ', $where) . "
        GROUP BY e.id
        ORDER BY e.date_event ASC";

$stmt = $bdd->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

$categories = $bdd->query('SELECT id, nom FROM categories ORDER BY nom')->fetchAll(PDO::FETCH_ASSOC);
$associations = $bdd->query('SELECT id, nom FROM associations ORDER BY nom')->fetchAll(PDO::FETCH_ASSOC);

include('../../includes/header.php');
?>

<section class="hero">
    <div class="container">
        <h1>Catalogue événements</h1>
        <p>Recherchez et filtrez les événements à venir.</p>
    </div>
</section>

<section class="container">
    <form class="search-form" action="/pages/events/liste.php" method="get" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; margin-bottom:24px;">
        <input type="search" name="q" value="<?= h($q) ?>" placeholder="Rechercher..." />
        <input type="date" name="date_min" value="<?= h($dateMin) ?>" />
        <input type="date" name="date_max" value="<?= h($dateMax) ?>" />

        <select name="category_id">
            <option value="0">Toutes les catégories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= (int)$cat['id'] ?>" <?= $categoryId === (int)$cat['id'] ? 'selected' : '' ?>>
                    <?= h((string)$cat['nom']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="association_id">
            <option value="0">Toutes les associations</option>
            <?php foreach ($associations as $assoc): ?>
                <option value="<?= (int)$assoc['id'] ?>" <?= $associationId === (int)$assoc['id'] ? 'selected' : '' ?>>
                    <?= h((string)$assoc['nom']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button class="btn" type="submit">Filtrer</button>
        <a class="btn btn-secondary" href="/pages/events/liste.php">Réinitialiser</a>
    </form>

    <?php if (empty($events)): ?>
        <p>Aucun événement ne correspond à vos critères.</p>
    <?php else: ?>
        <div class="event-grid">
            <?php foreach ($events as $ev): ?>
                <?php
                    $placesRestantes = max(0, (int)$ev['jauge_max'] - (int)$ev['nb_reservations']);
                    $affiche = !empty($ev['affiche_path'])
                        ? '/uploads/' . ltrim((string)$ev['affiche_path'], '/')
                        : '/assets/img/default_event.jpg';
                ?>
                <article class="event-card">
                    <img class="event-affiche" src="<?= h($affiche) ?>" alt="Affiche de <?= h((string)$ev['titre']) ?>" />
                    <div class="event-meta">
                        <h2 class="event-title"><?= h((string)$ev['titre']) ?></h2>
                        <p class="event-date"> <?= h((new DateTime((string)$ev['date_event']))->format('d/m/Y H:i')) ?></p>
                        <p class="event-lieu"> <?= h((string)$ev['lieu']) ?></p>
                        <?php if (!empty($ev['category_nom'])): ?>
                            <p>🏷️ <?= h((string)$ev['category_nom']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($ev['association_nom'])): ?>
                            <p>👥 <?= h((string)$ev['association_nom']) ?></p>
                        <?php endif; ?>
                        <p class="event-capacity">Places restantes : <strong><?= $placesRestantes ?></strong></p>
                        <a class="btn btn-secondary" href="/pages/events/detail.php?id=<?= (int)$ev['id'] ?>">Voir</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php include('../../includes/footer.php'); ?>
