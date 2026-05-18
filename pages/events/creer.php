<?php
declare(strict_types=1);

include('../../config/init.php');
include('../../includes/auth_check.php');
include('../../includes/role_check.php');
include('../../includes/functions.php');

check_roles(['organisateur', 'admin']);

$pageTitle = 'Créer un événement';

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function local_flash(string $type, string $message): void {
    if (function_exists('flash')) {
        flash($type, $message);
    } else {
        $_SESSION['flash'][$type] = $message;
    }
}

function local_redirect(string $url): void {
    if (function_exists('redirect')) {
        redirect($url);
    }
    header('Location: ' . $url);
    exit;
}

function upload_event_poster(array $file): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Erreur pendant l’upload de l’affiche.');
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('Affiche trop lourde. Taille maximale : 5 Mo.');
    }

    $tmp = (string)$file['tmp_name'];
    $info = @getimagesize($tmp);
    if ($info === false) {
        throw new RuntimeException('Le fichier envoyé n’est pas une image valide.');
    }

    $allowed = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_WEBP => 'webp',
        IMAGETYPE_GIF  => 'gif',
    ];

    $type = (int)$info[2];
    if (!isset($allowed[$type])) {
        throw new RuntimeException('Format d’image non autorisé. Utilisez JPG, PNG, WEBP ou GIF.');
    }

    $dir = __DIR__ . '/../../uploads/events';
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        throw new RuntimeException('Impossible de créer le dossier uploads/events.');
    }

    $name = 'event_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $allowed[$type];
    $dest = $dir . '/' . $name;

    if (!move_uploaded_file($tmp, $dest)) {
        throw new RuntimeException('Impossible d’enregistrer l’affiche.');
    }

    return 'events/' . $name;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (function_exists('csrf_verify') && !csrf_verify()) {
        local_flash('error', 'Requête invalide. Merci de réessayer.');
        local_redirect('/pages/events/creer.php');
    }

    $titre = trim((string)($_POST['titre'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $dateEvent = trim((string)($_POST['date_event'] ?? ''));
    $lieu = trim((string)($_POST['lieu'] ?? ''));
    $jaugeMax = (int)($_POST['jauge_max'] ?? 0);
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $associationId = !empty($_POST['association_id']) ? (int)$_POST['association_id'] : null;

    try {
        if ($titre === '' || $description === '' || $dateEvent === '' || $lieu === '' || $jaugeMax <= 0 || $categoryId <= 0) {
            throw new RuntimeException('Merci de remplir tous les champs obligatoires.');
        }

        $affichePath = isset($_FILES['affiche']) ? upload_event_poster($_FILES['affiche']) : null;

        $stmt = $bdd->prepare(
            'INSERT INTO events (organizer_id, category_id, association_id, titre, description, date_event, lieu, jauge_max, affiche_path)
             VALUES (:organizer_id, :category_id, :association_id, :titre, :description, :date_event, :lieu, :jauge_max, :affiche_path)'
        );

        $stmt->execute([
            'organizer_id' => (int)$currentUser['id'],
            'category_id' => $categoryId,
            'association_id' => $associationId,
            'titre' => $titre,
            'description' => $description,
            'date_event' => $dateEvent,
            'lieu' => $lieu,
            'jauge_max' => $jaugeMax,
            'affiche_path' => $affichePath,
        ]);

        local_flash('success', 'Événement créé avec succès.');
        local_redirect('/pages/events/detail.php?id=' . (int)$bdd->lastInsertId());
    } catch (Throwable $e) {
        local_flash('error', $e->getMessage());
    }
}

$categories = $bdd->query('SELECT id, nom FROM categories ORDER BY nom')->fetchAll(PDO::FETCH_ASSOC);
$associations = $bdd->query('SELECT id, nom FROM associations ORDER BY nom')->fetchAll(PDO::FETCH_ASSOC);

include('../../includes/header.php');
?>

<section class="container">
    <h1>Créer un événement</h1>

    <form method="post" action="/pages/events/creer.php" enctype="multipart/form-data" style="max-width:760px; display:grid; gap:14px;">
        <?php if (function_exists('csrf_input')) { echo csrf_input(); } ?>

        <label>
            Titre
            <input type="text" name="titre" required maxlength="255" />
        </label>

        <label>
            Description
            <textarea name="description" rows="7" required></textarea>
        </label>

        <label>
            Date et heure
            <input type="datetime-local" name="date_event" required />
        </label>

        <label>
            Lieu
            <input type="text" name="lieu" required maxlength="255" />
        </label>

        <label>
            Jauge maximale
            <input type="number" name="jauge_max" required min="1" />
        </label>

        <label>
            Catégorie
            <select name="category_id" required>
                <option value="">Choisir une catégorie</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int)$cat['id'] ?>"><?= h((string)$cat['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Association optionnelle
            <select name="association_id">
                <option value="">Aucune</option>
                <?php foreach ($associations as $assoc): ?>
                    <option value="<?= (int)$assoc['id'] ?>"><?= h((string)$assoc['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Affiche optionnelle, maximum 5 Mo
            <input type="file" name="affiche" accept="image/jpeg,image/png,image/webp,image/gif" />
        </label>

        <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <button class="btn" type="submit">Créer l’événement</button>
            <a class="btn btn-secondary" href="/pages/events/liste.php">Annuler</a>
        </div>
    </form>
</section>

<?php include('../../includes/footer.php'); ?>
