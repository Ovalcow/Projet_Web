<?php declare(strict_types=1);

// Point d’entrée « auth ».
//
// Historique : certaines pages/anciens commits faisaient include/require 'auth.php'.
// Dans ce projet, l’auth réelle est implémentée dans `includes/auth_check.php`.
//
// On garde un seul endroit pour charger :
// - init/session + chargement de `$currentUser` (via auth_check)
// - helpers de contrôle d’accès (require_login)

require_once __DIR__ . '/auth_check.php';


