<?php declare(strict_types=1);
require_once __DIR__ . '/../includes/db.php';

// Phase A : vraie gestion de session à implémenter.
// Pour l’instant, on redirige vers l’accueil.
@session_start();
$_SESSION = [];
@session_destroy();

header('Location: /pages/index.php');
exit;

