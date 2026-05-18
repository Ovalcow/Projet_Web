<?php declare(strict_types=1);
require_once __DIR__ . '/../includes/init.php';

$pageTitle = 'Accueil';

$reponse = $bdd->query(
    "SELECT e.id, e.titre, e.description, e.date_event, e.lieu, e.jauge_max,
            (SELECT COUNT(*) FROM reservations r WHERE r.event_id = e.id) AS nb_reservations,
            e.affiche_path
     FROM events e
     WHERE e.date_event >= NOW()
     ORDER BY e.date_event ASC
     LIMIT 10"
);
$events = $reponse->fetchAll();
$reponse->closeCursor();

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
        <h1>Événements à venir</h1>
        <p>Recherchez, inscrivez-vous et gérez vos billets.</p>
    </div>
</section>

<section class="container">

    <!-- Formulaire GET (TP8) pour la recherche -->
    <form class="search-form" action="/pages/events.php" method="GET">
        <label>
            <span>Recherche</span>
            <input type="text" name="q" placeholder="Titre, lieu…" />
        </label>
        <label>
            <span>Date</span>
            <input type="date" name="date" />
        </label>
        <label>
            <span>Catégorie</span>
            <select name="category">
                <option value="">Toutes</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo (int)$cat['id']; ?>"><?php echo htmlspecialchars($cat['nom']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Association</span>
            <select name="association">
                <option value="">Toutes</option>
                <?php foreach ($associations as $a): ?>
                    <option value="<?php echo (int)$a['id']; ?>"><?php echo htmlspecialchars($a['nom']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <button class="btn" type="submit">Rechercher</button>
    </form>

    <div class="events-grid">
        <?php if (empty($events)): ?>
            <p>Aucun événement à venir pour le moment.</p>
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
                </div>

                <a class="btn btn-secondary" href="/pages/event_detail.php?id=<?php echo (int)$ev['id']; ?>">Voir</a>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php include('../includes/footer.php'); ?>