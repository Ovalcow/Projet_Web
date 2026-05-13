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


