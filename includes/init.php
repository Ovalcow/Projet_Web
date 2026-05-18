<?php declare(strict_types=1);

// Bootstrap commun de l’application.
// - démarre la session
// - charge $currentUser depuis $_SESSION
//
// IMPORTANT : toutes les pages qui veulent utiliser la navbar avec état connecté
// doivent inclure ce fichier (directement ou indirectement via pages).

require_once __DIR__ . '/db.php';

// Durcissement session (avant session_start)
$cookieParams = [
  'lifetime' => 0,
  'path' => '/',
  'domain' => '',
  'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
  'httponly' => true,
  'samesite' => 'Strict',
];

if (PHP_VERSION_ID >= 70300) {
  session_set_cookie_params($cookieParams);
}

@session_start();


$currentUser = null;

if (!empty($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
  $userId = (int)$_SESSION['user_id'];

  $user = db_single(
    "SELECT id, role, association_id, nom, email, photo_path, is_organisateur_validated
     FROM users WHERE id = :id",
    [':id' => $userId]
  );

  if ($user) {
    $currentUser = $user;
  } else {
    // session invalide -> purge cohérente + restart session
    $_SESSION = [];
    @session_destroy();
    @session_start();
  }
}


// Helper: flash messages
if (empty($_SESSION['flash'])) {
  $_SESSION['flash'] = [];
}

