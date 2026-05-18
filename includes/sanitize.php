<?php declare(strict_types=1);

function e(mixed $value): string {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

