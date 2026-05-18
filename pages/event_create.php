<?php declare(strict_types=1);
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/role_check.php';

$pageTitle = 'Créer un événement';

// Accès: organisateur validé (ou admin)
if (empty($currentUser) || !in_array($currentUser['role'], ['organisateur','admin'], true)) {
  $_SESSION['flash'] = $_SESSION['flash'] ?? [];
  $_SESSION['flash']['error'] = 'Accès interdit : vous devez être organisateur.';
  header('Location: /pages/login.php');
  exit;
}

// Pour un rôle organisateur, exiger la validation
if (!empty($currentUser) && $currentUser['role'] === 'organisateur' && empty($currentUser['is_organisateur_validated'])) {
  $_SESSION['flash'] = $_SESSION['flash'] ?? [];
  $_SESSION['flash']['error'] = 'Accès interdit : compte organisateur non validé.';
  header('Location: /pages/index.php');
  exit;
}

$flash = flash_get();
$errors = [];

$categories = db_query('SELECT id, nom FROM categories ORDER BY nom');
$associations = db_query('SELECT id, nom FROM associations ORDER BY nom');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_verify()) {
    $errors[] = 'Requête invalide (CSRF).';
  } else {
    $titre = trim((string)($_POST['titre'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $date_event = (string)($_POST['date_event'] ?? '');
    $lieu = trim((string)($_POST['lieu'] ?? ''));
    $jauge_max = (int)($_POST['jauge_max'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $association_id = ($_POST['association_id'] ?? '');
    $association_id = ($association_id === '' ? null : (int)$association_id);

    if ($titre === '' || mb_strlen($titre) > 200) {
      $errors[] = 'Titre invalide.';
    }
    if ($description === '' || mb_strlen($description) > 20000) {
      $errors[] = 'Description invalide.';
    }
    if ($lieu === '' || mb_strlen($lieu) > 200) {
      $errors[] = 'Lieu invalide.';
    }
    if ($jauge_max <= 0 || $jauge_max > 1000000) {
      $errors[] = 'Jauge maximale invalide.';
    }
    if ($category_id <= 0) {
      $errors[] = 'Catégorie invalide.';
    }
    if ($association_id !== null && $association_id <= 0) {
      $errors[] = 'Association invalide.';
    }

    // Validation date (format input[type=date] + time optional). Le champ du formulaire sera datetime-local.
    $dt = null;
    if ($date_event !== '') {
      try {
        $dt = new DateTime($date_event);
      } catch (Throwable $e) {
        $dt = null;
      }
    }
    if (!$dt) {
      $errors[] = 'Date/heure invalide.';
    }

    $affiche_path = null;
    if (!empty($_FILES['affiche']['name']) && !empty($_FILES['affiche']['tmp_name'])) {
      $tmp = $_FILES['affiche']['tmp_name'];
      $name = (string)$_FILES['affiche']['name'];
      $size = (int)($_FILES['affiche']['size'] ?? 0);
      $err = (int)($_FILES['affiche']['error'] ?? UPLOAD_ERR_NO_FILE);

      if ($err !== UPLOAD_ERR_OK) {
        $errors[] = 'Erreur lors de l\'upload de l\'affiche.';
      } elseif ($size > 5 * 1024 * 1024) {
        $errors[] = 'L\'affiche est trop volumineuse (max 5Mo).';
      } else {
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? (string)finfo_file($finfo, $tmp) : '';
        if ($finfo) finfo_close($finfo);

        if (!isset($allowed[$mime])) {
          $errors[] = 'Format d\'image invalide (JPG/PNG/WebP).';
        } else {
          $ext = $allowed[$mime];
          $safeBase = preg_replace('/[^a-zA-Z0-9_-]+/', '-', pathinfo($name, PATHINFO_FILENAME));
          $safeBase = trim((string)$safeBase);
          if ($safeBase === '') $safeBase = 'event';

          $uploadsDir = __DIR__ . '/../uploads';
          if (!is_dir($uploadsDir)) {
            @mkdir($uploadsDir, 0775, true);
          }

          $fileName = sprintf('%s_%s.%s', $safeBase, bin2hex(random_bytes(8)), $ext);
          $dest = $uploadsDir . '/' . $fileName;

          if (!@move_uploaded_file($tmp, $dest)) {
            $errors[] = 'Impossible d\'enregistrer l\'affiche.';
          } else {
            $affiche_path = $fileName;
          }
        }
      }
    }

    if (!$errors) {
      $sql = "INSERT INTO events (organizer_id, association_id, category_id, titre, description, date_event, lieu, jauge_max, affiche_path)
              VALUES (:organizer_id, :association_id, :category_id, :titre, :description, :date_event, :lieu, :jauge_max, :affiche_path)";
      $params = [
        ':organizer_id' => (int)$currentUser['id'],
        ':association_id' => $association_id === null ? null : (int)$association_id,
        ':category_id' => (int)$category_id,
        ':titre' => $titre,
        ':description' => $description,
        ':date_event' => $dt->format('Y-m-d H:i:s'),
        ':lieu' => $lieu,
        ':jauge_max' => $jauge_max,
        ':affiche_path' => $affiche_path,
      ];

      // On récupère l\'ID inséré avec un SELECT LAST_INSERT_ID() (MVP)
      db_execute($sql, $params);
      $newIdRow = db_single('SELECT LAST_INSERT_ID() AS id');
      $newId = $newIdRow ? (int)$newIdRow['id'] : 0;

      if ($newId > 0) {
        header('Location: /pages/event_detail.php?id=' . $newId);
        exit;
      }
      $errors[] = 'Erreur lors de la création de l\'événement.';
    }
  }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="container">
  <h1 style="margin-top:0;">Créer un événement</h1>

  <?php if (!empty($errors)): ?>
    <div style="margin-top:16px; padding:12px; border:1px solid rgba(255,0,0,.25); border-radius:14px; background: rgba(255,0,0,.08); max-width:900px;">
      <?php foreach ($errors as $err): ?>
        <div style="margin-bottom:8px;"><?= e($err) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div style="margin-top:16px; padding:16px; background: rgba(255,255,255,.03); border:1px solid var(--border); border-radius:14px; max-width:900px;">
    <form method="POST" enctype="multipart/form-data" style="display:grid; gap:12px;">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />

      <label style="display:grid; gap:6px;">
        <span style="color: var(--muted); font-size:12px;">Titre</span>
        <input name="titre" required maxlength="200" style="padding:10px 12px; border-radius:10px; border:1px solid var(--border); background: rgba(255,255,255,.03); color: var(--text);" />
      </label>

      <label style="display:grid; gap:6px;">
        <span style="color: var(--muted); font-size:12px;">Description</span>
        <textarea name="description" required maxlength="20000" rows="6" style="padding:10px 12px; border-radius:10px; border:1px solid var(--border); background: rgba(255,255,255,.03); color: var(--text);"></textarea>
      </label>

      <label style="display:grid; gap:6px;">
        <span style="color: var(--muted); font-size:12px;">Date & heure</span>
        <input type="datetime-local" name="date_event" required style="padding:10px 12px; border-radius:10px; border:1px solid var(--border); background: rgba(255,255,255,.03); color: var(--text);" />
      </label>

      <label style="display:grid; gap:6px;">
        <span style="color: var(--muted); font-size:12px;">Lieu</span>
        <input name="lieu" required maxlength="200" style="padding:10px 12px; border-radius:10px; border:1px solid var(--border); background: rgba(255,255,255,.03); color: var(--text);" />
      </label>

      <label style="display:grid; gap:6px;">
        <span style="color: var(--muted); font-size:12px;">Jauge max</span>
        <input type="number" name="jauge_max" required min="1" step="1" style="padding:10px 12px; border-radius:10px; border:1px solid var(--border); background: rgba(255,255,255,.03); color: var(--text);" />
      </label>

      <label style="display:grid; gap:6px;">
        <span style="color: var(--muted); font-size:12px;">Catégorie</span>
        <select name="category_id" required style="padding:10px 12px; border-radius:10px; border:1px solid var(--border); background: rgba(255,255,255,.03); color: var(--text);">
          <option value="">-- Choisir --</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= (int)$cat['id'] ?>"><?= e($cat['nom']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <label style="display:grid; gap:6px;">
        <span style="color: var(--muted); font-size:12px;">Association (optionnel)</span>
        <select name="association_id" style="padding:10px 12px; border-radius:10px; border:1px solid var(--border); background: rgba(255,255,255,.03); color: var(--text);">
          <option value="">-- Aucune --</option>
          <?php foreach ($associations as $a): ?>
            <option value="<?= (int)$a['id'] ?>"><?= e($a['nom']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <label style="display:grid; gap:6px;">
        <span style="color: var(--muted); font-size:12px;">Affiche (optionnel, max 5Mo)</span>
        <input type="file" name="affiche" accept="image/*" />
      </label>

      <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
        <button class="btn btn-secondary" type="submit">Créer</button>
        <a class="btn" href="/pages/events.php">Annuler</a>
      </div>
    </form>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

