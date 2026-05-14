<?php
include('../includes/auth.php');

$pageTitle = 'Événements';

$q = '';
$date = '';
$category = '';
$association = '';

if (isset($_GET['q'])) {
    $q = htmlspecialchars(trim($_GET['q']));
}
if (isset($_GET['date'])) {
    $date = htmlspecialchars($_GET['date']);
}
if (isset($_GET['category'])) {
    $category = htmlspecialchars($_GET['category']);
}
if (isset($_GET['association'])) {
    $association = htmlspecialchars($_GET['association']);
}

// Construction de la requête SQL avec des paramètres (requête préparée - cours slide 84)
$sql = "SELECT e.id, e.titre, e.description, e.date_event, e.lieu, e.jauge_max,
               (SELECT COUNT(*) FROM reservations r WHERE r.event_id = e.id) AS nb_reservations,
               e.affiche_path
        FROM events e
        WHERE 1=1 ";
$params = array();

if ($q !== '') {
    $sql .= " AND (e.titre LIKE :q OR e.lieu LIKE :q OR e.description LIKE :q) ";
    $params['q'] = '%' . $q . '%';
}
if ($date !== '') {
    $sql .= " AND e.date_event >= :d0 AND e.date_event < :d1 ";
    $params['d0'] = $date . ' 00:00:00';
    $params['d1'] = $date . ' 23:59:59';
}
if ($category !== '') {
    $sql .= " AND e.category_id = :cat ";
    $params['cat'] = (int)$category;
}
if ($association !== '') {
    $sql .= " AND e.association_id = :assoc ";
    $params['assoc'] = (int)$association;
}

$sql .= " ORDER BY e.date_event ASC LIMIT 100";

$requete = $bdd->prepare($sql);
$requete->execute($params);
$events = $requete->fetchAll();
$requete->closeCursor();

// Charger les catégories et associations pour les filtres
$repCategories = $bdd->query('SELECT id, nom FROM categories ORDER BY nom');
$categories = $repCategories->fetchAll();
$repCategories->closeCursor();

$repAssociations = $bdd->query('SELECT id, nom FROM associations ORDER BY nom');
$associations = $repAssociations->fetchAll();
$repAssociations->closeCursor();

include('../includes/header.php');
?>

<section class="hero">
    <div class="container">
        <h1>Catalogue événements</h1>
        <p>Filtrez par date, catégorie ou association.</p>
    </div>
</section>

<section class="container">

    <!-- Formulaire GET (TP8) pour les filtres -->
    <form class="search-form" action="/pages/events.php" method="GET">
        <label>
            <span>Recherche</span>
            <input type="text" name="q" placeholder="Titre, lieu…" value="<?php echo $q; ?>" />
        </label>

        <label>
            <span>Date</span>
            <input type="date" name="date" value="<?php echo $date; ?>" />
        </label>

        <label>
            <span>Catégorie</span>
            <select name="category">
                <option value="">Toutes</option>
                <?php foreach ($categories as $cat):
                    $id  = (int)$cat['id'];
                    $sel = ((string)$id === $category) ? 'selected' : '';
                ?>
                    <option value="<?php echo $id; ?>" <?php echo $sel; ?>><?php echo htmlspecialchars($cat['nom']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            <span>Association</span>
            <select name="association">
                <option value="">Toutes</option>
                <?php foreach ($associations as $a):
                    $id  = (int)$a['id'];
                    $sel = ((string)$id === $association) ? 'selected' : '';
                ?>
                    <option value="<?php echo $id; ?>" <?php echo $sel; ?>><?php echo htmlspecialchars($a['nom']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <button class="btn" type="submit">Rechercher</button>
    </form>

    <div class="events-grid">
        <?php if (empty($events)): ?>
            <p>Aucun résultat.</p>
        <?php endif; ?>

        <?php foreach ($events as $ev):
            $nb  = (int)$ev['nb_reservations'];
            $max = (int)$ev['jauge_max'];
            $placesRestantes = $max - $nb;
            if ($placesRestantes < 0) { $placesRestantes = 0; }
        ?>
            <article class="event-card">
                <?php if (!empty($ev['affiche_path'])): ?>
                    <img class="event-affiche"
                         src="<?php echo htmlspecialchars('/uploads/' . $ev['affiche_path']); ?>"
                         alt="Affiche de <?php echo htmlspecialchars($ev['titre']); ?>" />
                <?php else: ?>
                    <img class="event-affiche" src="/assets/img/default_event.jpg" alt="Affiche par défaut" />
                <?php endif; ?>

                <div class="event-meta">
                    <h2 class="event-title"><?php echo htmlspecialchars($ev['titre']); ?></h2>
                    <p class="event-date"><?php echo date('d/m/Y H:i', strtotime($ev['date_event'])); ?></p>
                    <p class="event-lieu"><?php echo htmlspecialchars($ev['lieu']); ?></p>
                    <p class="event-capacity">Places restantes : <strong><?php echo $placesRestantes; ?></strong></p>
                    <a class="btn btn-secondary" href="/pages/event_detail.php?id=<?php echo (int)$ev['id']; ?>">Voir</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php include('../includes/footer.php'); ?>