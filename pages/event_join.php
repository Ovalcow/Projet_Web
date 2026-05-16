<?php declare(strict_types=1);
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$pageTitle = 'Inscription événement';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  require_once __DIR__ . '/../includes/header.php';
  echo '<section class="container"><p>Méthode non autorisée.</p></section>';
  require_once __DIR__ . '/../includes/footer.php';
  exit;
}

if (!csrf_verify()) {
  $errors[] = 'Requête invalide (CSRF).';
}

$eventId = (int)($_POST['event_id'] ?? 0);
if ($eventId <= 0) {
  $errors[] = 'event_id invalide.';
}

$event = null;
if (!$errors) {
  $event = db_single(
    "SELECT id, titre, date_event, jauge_max,
            (SELECT COUNT(*) FROM reservations r WHERE r.event_id = e.id) AS nb_reservations
     FROM events e WHERE e.id = :id",
    [':id' => $eventId]
  );

  if (!$event) {
    $errors[] = 'Événement introuvable.';
  } else {
    $nb = (int)$event['nb_reservations'];
    $max = (int)$event['jauge_max'];

    if ($max > 0 && $nb >= $max) {
      $errors[] = 'Plus de places disponibles.';
    }

    $already = db_single(
      "SELECT id FROM reservations WHERE event_id = :event_id AND participant_id = :participant_id",
      [':event_id' => $eventId, ':participant_id' => (int)$currentUser['id']]
    );

    if ($already) {
      $errors[] = 'Vous êtes déjà inscrit à cet événement.';
    }
  }
}

if ($errors) {
  $_SESSION['flash'] = $_SESSION['flash'] ?? [];
  $_SESSION['flash']['error'] = (string)($errors[0] ?? 'Erreur lors de l’inscription.');
  redirect('/pages/event_detail.php?id=' . (int)$eventId);
}

// Transaction pour limiter le risque de sur-occupation (race condition)
$reservationId = 0;
try {
  global $pdo;
  if (!isset($pdo) || !$pdo instanceof PDO) {
    throw new RuntimeException('PDO non disponible');
  }

  $pdo->beginTransaction();

  // Verrouille l’événement pendant la transaction
  $evRow = db_single(
    'SELECT id FROM events WHERE id = :id FOR UPDATE',
    [':id' => $eventId]
  );

  if (!$evRow) {
    throw new RuntimeException('Événement introuvable');
  }

  // Re-vérifie la capacité au moment de l’insertion
  $capRow = db_single(
    "SELECT (SELECT COUNT(*) FROM reservations r WHERE r.event_id = e.id) AS nb_reservations,
            e.jauge_max AS jauge_max
     FROM events e WHERE e.id = :id",
    [':id' => $eventId]
  );

  $nb2 = isset($capRow['nb_reservations']) ? (int)$capRow['nb_reservations'] : 0;
  $max2 = isset($capRow['jauge_max']) ? (int)$capRow['jauge_max'] : 0;

  if ($max2 > 0 && $nb2 >= $max2) {
    throw new RuntimeException('Plus de places disponibles');
  }

  db_execute(
    "INSERT INTO reservations (event_id, participant_id, presence_status)
     VALUES (:event_id, :participant_id, 'pending')",
    [':event_id' => $eventId, ':participant_id' => (int)$currentUser['id']]
  );

  $newResRow = db_single('SELECT LAST_INSERT_ID() AS id');
  $reservationId = $newResRow ? (int)$newResRow['id'] : 0;

  $pdo->commit();
} catch (Throwable $e) {
  if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
    $pdo->rollBack();
  }

  $_SESSION['flash'] = $_SESSION['flash'] ?? [];
  $_SESSION['flash']['error'] = 'Inscription impossible (conflit, déjà inscrit, plus de places, ou contrainte).';
  redirect('/pages/event_detail.php?id=' . (int)$eventId);
}

if ($reservationId <= 0) {
  $_SESSION['flash'] = $_SESSION['flash'] ?? [];
  $_SESSION['flash']['error'] = 'Erreur lors de la création de la réservation.';
  redirect('/pages/event_detail.php?id=' . (int)$eventId);
}

$payload = '/pages/billet_verify.php?reservation_id=' . $reservationId;

$appBaseUrl = rtrim((string)(getenv('APP_BASE_URL') ?: ''), '/');
if ($appBaseUrl === '') {
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = (string)($_SERVER['HTTP_HOST'] ?? '');
  $appBaseUrl = $scheme . '://' . $host;
}

$verificationUrl = $appBaseUrl . $payload;

$encoded = urlencode($verificationUrl);
$qrUrl = 'https://chart.googleapis.com/chart?chs=250x250&cht=qr&chl=' . $encoded;

$to = (string)$currentUser['email'];
$nom = (string)($currentUser['nom'] ?? 'Participant');
$eventTitre = (string)($event['titre'] ?? 'Événement');
$eventDate = !empty($event['date_event']) ? (new DateTime((string)$event['date_event']))->format('d/m/Y H:i') : '';

$fromName = (string)(getenv('MAIL_FROM_NAME') ?: 'OmnesEvent');
$fromEmail = (string)(getenv('MAIL_FROM') ?: ($to ?: 'no-reply@example.com'));
$subject = sprintf('Votre billet QR – %s', $eventTitre);

$html = '';
$html .= '<div style="font-family:Arial,Helvetica,sans-serif;line-height:1.4">';
$html .= '<h2 style="margin:0 0 12px;">Bonjour ' . htmlspecialchars($nom, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</h2>';
$html .= '<p>Vous êtes inscrit(e) à <strong>' . htmlspecialchars($eventTitre, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</strong></p>';
if ($eventDate !== '') {
  $html .= '<p>Date : ' . htmlspecialchars($eventDate, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';
}
$html .= '<p>Votre billet (QR) :</p>';
$html .= '<div style="padding:12px;border:1px solid #ddd;border-radius:12px;display:inline-block;background:#fafafa">';
$html .= '<img src="' . htmlspecialchars($qrUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" alt="QR code billet" style="width:200px;height:200px;" />';
$html .= '</div>';
$html .= '<p style="margin-top:16px;">Lien de vérification : <a href="' . htmlspecialchars($verificationUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">' . htmlspecialchars($verificationUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</a></p>';
$html .= '<p style="color:#666;font-size:12px;">Réservation ID : #' . $reservationId . '</p>';
$html .= '</div>';

$headers = [];
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-type: text/html; charset=UTF-8';
$headers[] = 'From: ' . $fromName . ' <' . $fromEmail . '>';

$mailOk = @mail($to, $subject, $html, implode("\r\n", $headers));

$_SESSION['flash'] = $_SESSION['flash'] ?? [];
if ($mailOk) {
  $_SESSION['flash']['success'] = 'Inscription confirmée. Email envoyé avec votre QR billet.';
} else {
  $_SESSION['flash']['success'] = 'Inscription confirmée. (Envoi email peut être indisponible sur ce serveur.)';
}

redirect('/pages/billet_qr.php?event_id=' . (int)$eventId);

