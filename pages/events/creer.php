<?php
include('../../config/init.php');
include('../../includes/auth_check.php');
include('../../includes/role_check.php');
include('../../includes/functions.php');

// Seuls les organisateurs peuvent créer
check_roles(array('organisateur', 'admin'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['titre'], $_POST['description'], $_POST['date_event'], $_POST['lieu'], $_POST['jauge_max'], $_POST['category_id'])) {

        $titre       = $_POST['titre'];
        $description = $_POST['description'];
        $date_event  = $_POST['date_event'];
        $lieu        = $_POST['lieu'];
        $jauge_max   = (int)$_POST['jauge_max'];
        $category_id = (int)$_POST['category_id'];
        $association_id = !empty($_POST['association_id']) ? (int)$_POST['association_id'] : null;

        $requete = $bdd->prepare(
            'INSERT INTO events (organizer_id, category_id, association_id, titre, description, date_event, lieu, jauge_max)
             VALUES (:org, :cat, :assoc, :titre, :desc, :date, :lieu, :jauge)'
        );
        $requete->execute(array(
            'org'   => $currentUser['id'],
            'cat'   => $category_id,
            'assoc' => $association_id,
            'titre' => $titre,
            'desc'  => $description,
            'date'  => $date_event,
            'lieu'  => $lieu,
            'jauge' => $jauge_max,
        ));

        flash('success', 'Événement créé avec succès !');
        header('Location: /pages/events/liste.php');
        exit();
    }
}

$repCategories = $bdd->query('SELECT id, nom FROM categories ORDER BY nom');
$categories = $repCategories->fetchAll();
$repCategories->closeCursor();

$repAssociations = $bdd->query('SELECT id, nom FROM associations ORDER BY nom');
$associations = $repAssociations->fetchAll();
$repAssociations->closeCursor();

$pageTitle = 'Créer un événement';
include('../../includes/header.php');
?>

<h1>Créer un événement</h1>

<form method="post" action="/pages/events/creer.php" style="max-width:600px;">
    <label for="titre">Titre</label>
    <input type="text" name="titre" id="titre" required />

    <label for="description">Description</label>
    <textarea name="description" id="description" rows="5" required style="width:100%; padding:10px; border:1px solid var(--border); border-radius:6px; font-size:14px;"></textarea>

    <label for="date_event">Date et heure</label>
    <input type="datetime-local" name="date_event" id="date_event" required style="width:100%; padding:10px; border:1px solid var(--border); border-radius:6px;" />

    <label for="lieu">Lieu</label>
    <input type="text" name="lieu" id="lieu" required />

    <label for="jauge_max">Nombre de places</label>
    <input type="number" name="jauge_max" id="jauge_max" min="1" required style="width:100%; padding:10px; border:1px solid var(--border); border-radius:6px;" />

    <label for="category_id">Catégorie</label>
    <select name="category_id" id="category_id" required style="width:100%; padding:10px; border:1px solid var(--border); border-radius:6px;">
        <?php foreach ($categories as $cat): ?>
            <option value="<?php echo (int)$cat['id']; ?>"><?php echo htmlspecialchars($cat['nom']); ?></option>
        <?php endforeach; ?>
    </select>

    <label for="association_id">Association (optionnel)</label>
    <select name="association_id" id="association_id" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:6px;">
        <option value="">Aucune</option>
        <?php foreach ($associations as $a): ?>
            <option value="<?php echo (int)$a['id']; ?>"><?php echo htmlspecialchars($a['nom']); ?></option>
        <?php endforeach; ?>
    </select>

    <button type="submit" class="btn">Créer l'événement</button>
</form>

<?php include('../../includes/footer.php'); ?>