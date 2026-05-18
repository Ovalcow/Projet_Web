<?php declare(strict_types=1);
require_once __DIR__ . '/role_check.php';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php if (isset($pageTitle)) { echo htmlspecialchars($pageTitle); } else { echo 'OmnesEvent'; } ?></title>
    <link rel="stylesheet" href="/assets/css/style.css" />
</head>
<body>
  <header class="site-header">
    <div class="container header-inner">
      <a class="brand" href="/pages/index.php">OmnesEvent</a>
      <nav class="nav">
        <a class="nav-link" href="/pages/events.php">Événements</a>
        <a class="nav-link" href="/pages/profile.php">Mon profil</a>
<?php if (!empty($currentUser) && user_is_organisateur()): ?>
          <a class="nav-link" href="/pages/event_create.php">Créer événement</a>
        <?php endif; ?>
        <?php if (!empty($currentUser)): ?>
          <a class="nav-link" href="/pages/logout.php">Déconnexion</a>
        <?php else: ?>
          <a class="nav-link" href="/pages/login.php">Connexion</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>

    <main class="site-main">
        <div class="container">