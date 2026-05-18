<?php
/**
 * pages/reservations/annuler.php
 * Annule la réservation du participant connecté.
 */
declare(strict_types=1);

include('../../config/init.php');
include('../../includes/auth_check.php');
include('../../includes/functions.php');

if (function_exists('require_login')) {
    require_login();
}

function reservation_flash(string $type, string $message): void
{
    if (function_exists('flash')) {
        flash($type, $message);
        return;
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

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$eventId = $method === 'POST'
    ? (int)($_POST['event_id'] ?? 0)
    : (int)($_GET['event_id'] ?? 0);

if ($eventId <= 0) {
    reservation_flash('error', 'ID événement invalide.');
    reservation_redirect('/pages/reservations/mes_billets.php');
}

if ($method === 'POST' && function_exists('csrf_verify') && !csrf_verify()) {
    reservation_flash('error', 'Requête invalide. Merci de réessayer.');
    reservation_redirect('/pages/events/detail.php?id=' . $eventId);
}

$userId = (int)($currentUser['id'] ?? 0);

$stmt = $bdd->prepare(
    'DELETE FROM reservations
     WHERE event_id = :event_id
       AND participant_id = :participant_id
     LIMIT 1'
);
$stmt->execute([
    'event_id' => $eventId,
    'participant_id' => $userId,
]);

if ($stmt->rowCount() > 0) {
    reservation_flash('success', 'Réservation annulée.');
} else {
    reservation_flash('warning', 'Aucune réservation à annuler pour cet événement.');
}

reservation_redirect('/pages/events/detail.php?id=' . $eventId);
