<?php
// Inclure en haut de chaque page protégée, AVANT tout code HTML
session_start();

include('db.php');

$currentUser = null;

if (isset($_SESSION['user_id'])) {
    $requete = $bdd->prepare('SELECT id, nom, email, role, association_id FROM users WHERE id = ?');
    $requete->execute(array($_SESSION['user_id']));
    $currentUser = $requete->fetch();
    $requete->closeCursor();

    // Si l'utilisateur n'existe plus en BDD, on détruit la session
    if (!$currentUser) {
        $_SESSION = array();
        session_destroy();
        session_start();
    }
}

// Si aucun utilisateur valide n'est connecté, redirection vers la page de connexion
if (!$currentUser) {
    header('Location: login.php');
    exit();
}