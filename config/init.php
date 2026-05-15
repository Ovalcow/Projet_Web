<?php
// Fichier d'initialisation : à inclure en haut de CHAQUE page, AVANT tout HTML
// session_start() + connexion BDD + chargement de l'utilisateur connecté

session_start();

include(__DIR__ . '/db.php');

// Charger l'utilisateur connecté (s'il y en a un)
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