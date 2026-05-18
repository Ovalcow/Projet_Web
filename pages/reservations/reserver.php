<?php
declare(strict_types=1);

include('../../config/init.php');
include('../../includes/auth_check.php');
include('../../includes/functions.php');

if (function_exists('require_login')) {
    require_login();
}

function reservation_flash(string $type, string $message): void {
    if (function_exists('flash')) {
        flash($type, $message);
    } else {
        $_SESSION['flash'][$type] = $message;
    }
}

function reservation_redirect(string $url): void {
    if (function_exists('redirect')) {
        redirect($url);
    }
    header('Location: ' . $url);
    exit;
}

function table_has_column(PDO $bdd, string $table, string $column): bool {
    static $cache = [];
    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    try {
        $stmt = $bdd->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table_name
               AND COLUMN_NAME = :column_name"
        );
        $stmt->execute(['table_name' => $table, 'column_name' => $column]);
        return $cache[$key] = ((int)$stmt->fetchColumn() > 0);
    } catch (Throwable $e) {
        return $cache[$key] = false;
    }
}

function app_absolute_url(string $path): string {
    $base = rtrim((string)(getenv('APP_BASE_URL') ?: ''), '/');
    if ($base === '') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string)($_SERVER['HTTP_HOST'] ?? '');
        $base = $host !== '' ? $scheme . '://' . $host : '';
    }
    return $base . $path;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$eventId = $method === 'POST' ? (int)($_POST['event_id'] ?? 0) : (int)($_GET['event_id'] ?? 0);
$userId = (int)($currentUser['id'] ?? 0);

if ($eventId <= 0) {
    reservation_flash('error', 'ID événement invalide.');
    reservation_redirect('/pages/events/liste.php');
}

if ($method === 'POST' && function_exists('csrf_verify') && !csrf_verify()) {
    reservation_flash('error', 'Requête invalide. Merci de réessayer.');
    reservation_redirect('/pages/events/detail.php?id=' . $eventId);
}

$hasPresence = table_has_column($bdd, 'reservations', 'presence_status');
$reservationId = 0;

try {
    $bdd->beginTransaction();

    $stmt = $bdd->prepare(
        "SELECT e.id, e.titre, e.date_event, e.jauge_max,
                COUNT(r.id) AS nb_reservations
         FROM events e
         LEFT JOIN reservations r ON r.event_id = e.id
         WHERE e.id = :id
         GROUP BY e.id
         FOR UPDATE"
    );
    $stmt->execute(['id' => $eventId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$event) {
        throw new RuntimeException('Événement introuvable.');
    }

    $check = $bdd->prepare('SELECT id FROM reservations WHERE event_id = :event_id AND participant_id = :participant_id LIMIT 1');
    $check->execute(['event_id' => $eventId, 'participant_id' => $userId]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);
    $check->closeCursor();

    if ($existing) {
        $reservationId = (int)$existing['id'];
        $bdd->commit();
        reservation_flash('warning', 'Vous êtes déjà inscrit à cet événement.');
        reservation_redirect('/pages/reservations/billet_qr.php?event_id=' . $eventId);
    }

    $placesRestantes = (int)$event['jauge_max'] - (int)$event['nb_reservations'];
    if ($placesRestantes <= 0) {
        throw new RuntimeException('Plus de places disponibles.');
    }

    if ($hasPresence) {
        $insert = $bdd->prepare(
            "INSERT INTO reservations (event_id, participant_id, presence_status)
             VALUES (:event_id, :participant_id, 'pending')"
        );
    } else {
        $insert = $bdd->prepare(
            "INSERT INTO reservations (event_id, participant_id)
             VALUES (:event_id, :participant_id)"
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

try {
    $to = (string)($currentUser['email'] ?? '');
    if ($to !== '' && $reservationId > 0) {
        $verifyUrl = app_absolute_url('/pages/reservations/billet_verify.php?reservation_id=' . $reservationId);
        $qrUrl = 'https://chart.googleapis.com/chart?cht=qr&chs=220x220&chl=' . rawurlencode($verifyUrl);
        $subject = 'Votre billet OmnesEvent';
        $html = '<h1>Inscription confirmée</h1>'
              . '<p>Votre inscription est confirmée.</p>'
              . '<p><a href="' . htmlspecialchars($verifyUrl, ENT_QUOTES, 'UTF-8') . '">Lien de vérification du billet</a></p>'
              . '<p><img src="' . htmlspecialchars($qrUrl, ENT_QUOTES, 'UTF-8') . '" alt="QR billet"></p>';
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: OmnesEvent <no-reply@omnesevent.local>',
        ];
        @mail($to, $subject, $html, implode("\r\n", $headers));
    }
} catch (Throwable $e) {
    // L’email ne doit pas annuler la réservation.
}

reservation_flash('success', 'Inscription confirmée.');
reservation_redirect('/pages/reservations/billet_qr.php?event_id=' . $eventId);
