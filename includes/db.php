<?php
declare(strict_types=1);

// Connexion PDO + helpers.

$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_NAME = getenv('DB_NAME') ?: 'omnes_event';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';
$DB_CHARSET = 'utf8mb4';

$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";

try {
  $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ]);
} catch (Throwable $e) {
  // En prod: logger + page d’erreur générique.
  http_response_code(500);
  echo 'Erreur de connexion DB.';
  exit;
}

function db_query(string $sql, array $params = []): array {
  global $pdo;
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  return $stmt->fetchAll();
}

function db_execute(string $sql, array $params = []): int {
  global $pdo;
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  return $stmt->rowCount();
}

function db_single(string $sql, array $params = []): ?array {
  global $pdo;
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $row = $stmt->fetch();
  return $row ?: null;
}

function e(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

