<?php declare(strict_types=1);

require_once __DIR__ . '/init.php';

function require_role(array $roles): void {
  global $currentUser;

  if (empty($currentUser) || !in_array($currentUser['role'], $roles, true)) {
    http_response_code(403);
    echo 'Accès interdit.';
    exit;
  }
}

function user_is_admin(): bool {
  global $currentUser;
  return !empty($currentUser) && ($currentUser['role'] === 'admin');
}

function user_is_organisateur(): bool {
  global $currentUser;
  return !empty($currentUser) && ($currentUser['role'] === 'organisateur');
}

function user_is_participant(): bool {
  global $currentUser;
  return !empty($currentUser) && ($currentUser['role'] === 'participant');
}

