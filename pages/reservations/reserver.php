<?php
/**
 * pages/reservations/reserver.php
 * Fusion optimisée des deux versions d'inscription.
 *
 * Compatibilité gardée :
 * - ancienne version : GET ?event_id=...
 * - nouvelle version : POST event_id + CSRF éventuel
 *
 * Recommandé : appeler cette page en POST depuis detail.php.
 */
declare(strict_types=1);

include('../../config/init.php');
include('../../includes/auth_check.php');
include('../../includes/functions.php');

if (function_exists('require_login')) {
    require_login();
}

function oh(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function reservation_flash(string $type, string $message): void
{
    if (function_exists('flash')) {
        flash($type, $message);
        return;
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $_SESSION['flash'] = $_SESSION['flash'] ?? [];
    $_SESSION['flash'][$type] = $message;
}

function reservation_redirect(string $url): void
{
    if (function_exists('redirect')) {
        redirect($url);
    }

    header('Location: ' . $url);
    exit;
}

function reservation_absolute_url(string $path): string
{
    $base = rtrim((string)(getenv('APP_BASE_URL') ?: ''), '/');
    if ($base === '') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string)($_SERVER['HTTP_HOST'] ?? '');
        $base = $host !== '' ? ($scheme . '://' . $host) : '';
    }

    return $base . $path;
}

function reservations_has_column(PDO $bdd, string $table, string $column): bool
{
    static $cache = [];
    $key = $table . '.' . $column;

    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    try {
        $stmt = $bdd->prepare(
            "SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table_name
               AND COLUMN_NAME = :column_name"
        );
        $stmt->execute([
            'table_name' => $table,
            'column_name' => $column,
        ]);
        $cache[$key] = ((int)$stmt->fetchColumn() > 0);
    } catch (Throwable $e) {
        $cache[$key] = false;
    }

    return $cache[$key];
}

if (!isset($bdd) || !$bdd instanceof PDO) {
    http_response_code(500);
    echo 'Connexion base de données indisponible.';
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$eventId = $method === 'POST'
    ? (int)($_POST['event_id'] ?? 0)
    : (int)($_GET['event_id'] ?? 0);

if ($eventId <= 0) {
    reservation_flash('error', 'ID événement invalide.');
    reservation_redirect('/pages/events/liste.php');
}

// CSRF uniquement si la fonction existe. On garde le GET legacy pour ne pas casser l'ancienne version.
if ($method === 'POST' && function_exists('csrf_verify') && !csrf_verify()) {
    reservation_flash('error', 'Requête invalide. Merci de réessayer.');
    reservation_redirect('/pages/events/detail.php?id=' . $eventId);
}

if (!in_array($method, ['GET', 'POST'], true)) {
    http_response_code(405);
    include('../../includes/header.php');
    echo '<section class="container"><p>Méthode non autorisée.</p></section>';
    include('../../includes/footer.php');
    exit;
}

$userId = (int)($currentUser['id'] ?? 0);
if ($userId <= 0) {
    reservation_flash('error', 'Vous devez être connecté pour réserver.');
    reservation_redirect('/pages/auth/login.php');
}

$reservationId = 0;
$event = null;

try {
    $bdd->beginTransaction();

    // Verrouille la ligne événement pour éviter les sur-réservations simultanées.
    $stmt = $bdd->prepare(
        "SELECT e.id, e.titre, e.date_event, e.jauge_max,
                (SELECT COUNT(*) FROM reservations r WHERE r.event_id = e.id) AS nb_reservations
         FROM events e
         WHERE e.id = :id
         FOR UPDATE"
    );
    $stmt->execute(['id' => $eventId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$event) {
        throw new RuntimeException('Événement introuvable.');
    }

    $check = $bdd->prepare(
        'SELECT id FROM reservations WHERE event_id = :event_id AND participant_id = :participant_id LIMIT 1'
    );
    $check->execute([
        'event_id' => $eventId,
        'participant_id' => $userId,
    ]);
    $already = $check->fetch(PDO::FETCH_ASSOC);
    $check->closeCursor();

    if ($already) {
        throw new RuntimeException('Vous êtes déjà inscrit à cet événement.');
    }

    $nbReservations = (int)$event['nb_reservations'];
    $jaugeMax = (int)$event['jauge_max'];

    if ($jaugeMax > 0 && $nbReservations >= $jaugeMax) {
        throw new RuntimeException('Plus de places disponibles.');
    }

    $hasPresenceStatus = reservations_has_column($bdd, 'reservations', 'presence_status');

    if ($hasPresenceStatus) {
        $insert = $bdd->prepare(
            "INSERT INTO reservations (event_id, participant_id, presence_status)
             VALUES (:event_id, :participant_id, 'pending')"
        );
    } else {
        $insert = $bdd->prepare(
            'INSERT INTO reservations (event_id, participant_id)
             VALUES (:event_id, :participant_id)'
        );
    }

    $insert->execute([
        'event_id' => $eventId,
        'participant_id' => $userId,
    ]);

    $reservationId = (int)$bdd->lastInsertId();
    $bdd->commit();
} catch (Throwable $e) {
    if ($bdd->inTransaction()) {
        $bdd->rollBack();
    }

    reservation_flash('error', $e->getMessage());
    reservation_redirect('/pages/events/detail.php?id=' . $eventId);
}

// Email QR : non bloquant. Si mail() échoue, la réservation reste valide.
if ($reservationId > 0 && is_array($event)) {
    $verificationPath = '/pages/reservations/billet_verify.php?reservation_id=' . $reservationId;
    $verificationUrl = reservation_absolute_url($verificationPath);
    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($verificationUrl);

    $to = trim((string)($currentUser['email'] ?? ''));

    if ($to !== '') {
        $nom = (string)($currentUser['nom'] ?? $currentUser['prenom'] ?? 'Participant');
        $eventTitre = (string)($event['titre'] ?? 'Événement');
        $eventDate = !empty($event['date_event'])
            ? date('d/m/Y H:i', strtotime((string)$event['date_event']))
            : '';

        $subject = 'Votre billet QR – ' . $eventTitre;
        $fromName = (string)(getenv('MAIL_FROM_NAME') ?: 'OmnesEvent');
        $fromEmail = (string)(getenv('MAIL_FROM') ?: 'no-reply@example.com');

        $html = '<div style="font-family:Arial,Helvetica,sans-serif;line-height:1.4">';
        $html .= '<h2>Bonjour ' . oh($nom) . '</h2>';
        $html .= '<p>Votre inscription à <strong>' . oh($eventTitre) . '</strong> est confirmée.</p>';
        if ($eventDate !== '') {
            $html .= '<p>Date : ' . oh($eventDate) . '</p>';
        }
        $html .= '<p>Votre billet QR :</p>';
        $html .= '<img src="' . oh($qrUrl) . '" alt="QR code billet" style="width:200px;height:200px;" />';
        $html .= '<p>Lien de vérification : <a href="' . oh($verificationUrl) . '">' . oh($verificationUrl) . '</a></p>';
        $html .= '<p style="color:#666;font-size:12px;">Réservation ID : #' . $reservationId . '</p>';
        $html .= '</div>';

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $fromName . ' <' . $fromEmail . '>',
        ];

        @mail($to, $subject, $html, implode("\r\n", $headers));
    }
}

reservation_flash('success', 'Inscription confirmée. Votre billet QR est disponible.');
reservation_redirect('/pages/reservations/billet_qr.php?event_id=' . $eventId);
