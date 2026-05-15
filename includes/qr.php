<?php declare(strict_types=1);

function qr_generate_png_data_uri(string $text, int $size = 250): ?string {
  // Sans lib QR disponible côté serveur, on ne peut pas générer un PNG QR.
  // Les pages afficheront un fallback.
  return null;
}

