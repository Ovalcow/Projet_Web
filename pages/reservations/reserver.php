<?php
include('../../config/init.php');
include('../../includes/auth_check.php');
include('../../includes/functions.php');

if (!isset($_GET['event_id'])) {
    header('Location: /pages/events/liste.php');
    exit();
}

$event_id = (int)$_GET['event_id'];

// Vérifier que l'événement existe et qu'il reste des places
$requete = $bdd->prepare(
    "SELECT e.id, e.jauge_max,
            (SELECT COUNT(*) FROM reservations r WHERE r.event_id = e.id) AS nb_reservations
     FROM events e WHERE e.id = :id"
);
$requete->execute(array('id' => $event_id));
$event = $requete->fetch();
$requete->closeCursor();

if (!$event) {
    flash('error', 'Événement introuvable.');
    header('Location: /pages/events/liste.php');
    exit();
}

$placesRestantes = (int)$event['jauge_max'] - (int)$event['nb_reservations'];

if ($placesRestantes <= 0) {
    flash('error', 'Plus de places disponibles.');
    header('Location: /pages/events/detail.php?id=' . $event_id);
    exit();
}

// Vérifier si déjà inscrit
$reqCheck = $bdd->prepare('SELECT id FROM reservations WHERE event_id = :eid AND participant_id = :uid');
$reqCheck->execute(array('eid' => $event_id, 'uid' => $currentUser['id']));
if ($reqCheck->fetch()) {
    flash('warning', 'Vous êtes déjà inscrit à cet événement.');
    header('Location: /pages/events/detail.php?id=' . $event_id);
    exit();
}
$reqCheck->closeCursor();

// Insérer la réservation
$requete = $bdd->prepare('INSERT INTO reservations (event_id, participant_id) VALUES (:eid, :uid)');
$requete->execute(array('eid' => $event_id, 'uid' => $currentUser['id']));

flash('success', 'Inscription confirmée !');
header('Location: /pages/events/detail.php?id=' . $event_id);
exit();