<?php declare(strict_types=1);

function redirect(string $url): void {
  header('Location: ' . $url);
  exit;
}

function flash_get(): array {
  if (empty($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
    return [];
  }
  $flash = $_SESSION['flash'];
  $_SESSION['flash'] = [];
  return $flash;
}

function flash_set(string $type, string $message): void {
  $_SESSION['flash'] = $_SESSION['flash'] ?? [];
  $_SESSION['flash'][$type] = $message;
}

// CSRF protection
// - csrf_token(): génère/retourne le token en session
// - csrf_verify(): valide le token reçu dans $_POST['csrf_token']
function csrf_token(): string {
  // La session doit idéalement être démarrée via includes/init.php
  if (session_status() !== PHP_SESSION_ACTIVE) {
    throw new RuntimeException('Session inactive: csrf_token() nécessite init.php');
  }

  if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return (string)$_SESSION['csrf_token'];
}

function csrf_verify(): bool {
  // La session doit idéalement être démarrée via includes/init.php
  if (session_status() !== PHP_SESSION_ACTIVE) {
    throw new RuntimeException('Session inactive: csrf_verify() nécessite init.php');
  }

  $token = $_POST['csrf_token'] ?? '';
  if (!is_string($token) || $token === '') {
    return false;
  }

  $sessionToken = $_SESSION['csrf_token'] ?? '';
  if (!is_string($sessionToken) || $sessionToken === '') {
    return false;
  }

  return hash_equals($sessionToken, $token);
}





