<?php
declare(strict_types=1);

include('../../config/init.php');
include('../../includes/functions.php');

$pageTitle = 'Créer un événement';

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (empty($currentUser) || !in_array($currentUser['role'] ?? '', ['organisateur', 'admin'], true)) {
    if (function_exists('flash')) {
        flash('error', 'Accès interdit : vous devez être organisateur.');
    }
    header('Location: /pages/auth/login.php');
    exit;
}

$errors = [];

$repCategories = $bdd->query('SELECT id, nom FROM categories ORDER BY nom');
$categories = $repCategories->fetchAll(PDO::FETCH_ASSOC);
$repCategories->closeCursor();

$repAssociations = $bdd->query('SELECT id, nom FROM associations ORDER BY nom');
$associations = $repAssociations->fetchAll(PDO::FETCH_ASSOC);
$repAssociations->closeCursor();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (function_exists('csrf_verify') && !csrf_verify()) {
        $errors[] = 'Requête invalide. Merci de réessayer.';
    }

    $titre          = trim((string)($_POST['titre'] ?? ''));
    $description    = trim((string)($_POST['description'] ?? ''));
    $dateEventInput = trim((string)($_POST['date_event'] ?? ''));
    $lieu           = trim((string)($_POST['lieu'] ?? ''));
    $jaugeMax       = (int)($_POST['jauge_max'] ?? 0);
    $categoryId     = (int)($_POST['category_id'] ?? 0);
    $associationRaw = $_POST['association_id'] ?? '';
    $associationId  = ($associationRaw === '') ? null : (int)$associationRaw;

    if ($titre === '' || mb_strlen($titre) > 200) {
        $errors[] = 'Titre invalide.';
    }

    if ($description === '' || mb_strlen($description) > 20000) {
        $errors[] = 'Description invalide.';
    }

    if ($lieu === '' || mb_strlen($lieu) > 200) {
        $errors[] = 'Lieu invalide.';
    }

    if ($jaugeMax <= 0 || $jaugeMax > 1000000) {
        $errors[] = 'Jauge maximale invalide.';
    }

    if ($categoryId <= 0) {
        $errors[] = 'Catégorie invalide.';
    }

    if ($associationId !== null && $associationId <= 0) {
        $errors[] = 'Association invalide.';
    }

    $dateEvent = null;
    if ($dateEventInput !== '') {
        try {
            $dateEvent = new DateTime($dateEventInput);
        } catch (Throwable $e) {
            $dateEvent = null;
        }
    }

    if (!$dateEvent) {
        $errors[] = 'Date/heure invalide.';
    }

    $affichePath = null;

    if (!empty($_FILES['affiche']['name']) && !empty($_FILES['affiche']['tmp_name'])) {
        $tmp  = (string)$_FILES['affiche']['tmp_name'];
        $name = (string)$_FILES['affiche']['name'];
        $size = (int)($_FILES['affiche']['size'] ?? 0);
        $err  = (int)($_FILES['affiche']['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($err !== UPLOAD_ERR_OK) {
            $errors[] = 'Erreur lors de l’upload de l’affiche.';
        } elseif ($size > 5 * 1024 * 1024) {
            $errors[] = 'L’affiche est trop volumineuse : maximum 5 Mo.';
        } else {
            $allowed = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
            ];

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? (string)finfo_file($finfo, $tmp) : '';
            if ($finfo) {
                finfo_close($finfo);
            }

            if (!isset($allowed[$mime])) {
                $errors[] = 'Format d’image invalide. Formats acceptés : JPG, PNG, WebP.';
            } else {
                $ext = $allowed[$mime];
                $baseName = preg_replace('/[^a-zA-Z0-9_-]+/', '-', pathinfo($name, PATHINFO_FILENAME));
                $baseName = trim((string)$baseName, '-_');
                if ($baseName === '') {
                    $baseName = 'event';
                }

                $uploadsDir = __DIR__ . '/../../uploads/events';
                if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0775, true) && !is_dir($uploadsDir)) {
                    $errors[] = 'Impossible de créer le dossier uploads/events.';
                } else {
                    $fileName = $baseName . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    $destination = $uploadsDir . '/' . $fileName;

                    if (!move_uploaded_file($tmp, $destination)) {
                        $errors[] = 'Impossible d’enregistrer l’affiche.';
                    } else {
                        $affichePath = $fileName;
                    }
                }
            }
        }
    }

    if (empty($errors)) {
        $requete = $bdd->prepare(
            'INSERT INTO events
                (organizer_id, association_id, category_id, titre, description, date_event, lieu, jauge_max, affiche_path)
             VALUES
                (:organizer_id, :association_id, :category_id, :titre, :description, :date_event, :lieu, :jauge_max, :affiche_path)'
        );

        $requete->execute([
            'organizer_id'    => (int)$currentUser['id'],
            'association_id'  => $associationId,
            'category_id'     => $categoryId,
            'titre'           => $titre,
            'description'     => $description,
            'date_event'      => $dateEvent->format('Y-m-d H:i:s'),
            'lieu'            => $lieu,
            'jauge_max'       => $jaugeMax,
            'affiche_path'    => $affichePath,
        ]);

        $newId = (int)$bdd->lastInsertId();

        if (function_exists('flash')) {
            flash('success', 'Événement créé avec succès.');
        }

        header('Location: /pages/events/detail.php?id=' . $newId);
        exit;
    }
}

include('../../includes/header.php');
?>

<section class="container">
    <h1>Créer un événement</h1>

    <?php if (!empty($errors)): ?>
        <div class="msg msg-error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo h($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/pages/events/creer.php" enctype="multipart/form-data" class="form-card">
        <?php if (function_exists('csrf_token')): ?>
            <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>" />
        <?php endif; ?>

        <label for="titre">Titre</label>
        <input id="titre" name="titre" maxlength="200" required value="<?php echo h($_POST['titre'] ?? ''); ?>" />

        <label for="description">Description</label>
        <textarea id="description" name="description" rows="6" maxlength="20000" required><?php echo h($_POST['description'] ?? ''); ?></textarea>

        <label for="date_event">Date et heure</label>
        <input id="date_event" type="datetime-local" name="date_event" required value="<?php echo h($_POST['date_event'] ?? ''); ?>" />

        <label for="lieu">Lieu</label>
        <input id="lieu" name="lieu" maxlength="200" required value="<?php echo h($_POST['lieu'] ?? ''); ?>" />

        <label for="jauge_max">Jauge maximale</label>
        <input id="jauge_max" type="number" name="jauge_max" min="1" step="1" required value="<?php echo h($_POST['jauge_max'] ?? ''); ?>" />

        <label for="category_id">Catégorie</label>
        <select id="category_id" name="category_id" required>
            <option value="">-- Choisir --</option>
            <?php foreach ($categories as $cat): ?>
                <?php $selected = ((string)$cat['id'] === (string)($_POST['category_id'] ?? '')) ? 'selected' : ''; ?>
                <option value="<?php echo (int)$cat['id']; ?>" <?php echo $selected; ?>>
                    <?php echo h($cat['nom']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="association_id">Association optionnelle</label>
        <select id="association_id" name="association_id">
            <option value="">Aucune</option>
            <?php foreach ($associations as $asso): ?>
                <?php $selected = ((string)$asso['id'] === (string)($_POST['association_id'] ?? '')) ? 'selected' : ''; ?>
                <option value="<?php echo (int)$asso['id']; ?>" <?php echo $selected; ?>>
                    <?php echo h($asso['nom']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="affiche">Affiche optionnelle, max 5 Mo</label>
        <input id="affiche" type="file" name="affiche" accept="image/jpeg,image/png,image/webp" />

        <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:14px;">
            <button class="btn" type="submit">Créer l’événement</button>
            <a class="btn btn-secondary" href="/pages/events/liste.php">Annuler</a>
        </div>
    </form>
</section>

<?php include('../../includes/footer.php'); ?>
