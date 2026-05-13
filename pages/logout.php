<?php declare(strict_types=1);
require_once __DIR__ . '/../includes/init.php';

// Déconnexion : purge session et redirection.
@session_start();
$_SESSION = [];
@session_destroy();

header('Location: /pages/index.php');
exit;

