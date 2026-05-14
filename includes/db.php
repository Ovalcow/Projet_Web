<?php
// Connexion à MySQL avec PDO (comme vu en cours)

try {
    $bdd = new PDO(
        'mysql:host=localhost;dbname=omnes_event;charset=utf8',
        'root',
        '',
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
} catch (Exception $e) {
    die('Erreur : ' . $e->getMessage());
}