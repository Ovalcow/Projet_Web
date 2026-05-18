<?php declare(strict_types=1);

require_once __DIR__ . '/init.php';

function require_login(): void {
  global $currentUser;

  if (empty($currentUser)) {
    $_SESSION['flash'] = $_SESSION['flash'] ?? [];
    $_SESSION['flash']['error'] = 'Veuillez vous connecter.';
    header('Location: /pages/login.php');
    exit;
  }
}

