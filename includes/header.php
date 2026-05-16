<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="fr">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= isset($pageTitle) ? e($pageTitle) : 'OmnesEvent' ?></title>
  <link rel="stylesheet" href="/assets/css/style.css?v=<?php echo time(); ?>" />
</head>

<body>
  <header class="site-header">
    <div class="container header-inner">
      <a class="logo" href="/pages/index.php"><img class="logo" src="/assets/images/omnes-logo.webp"
          alt="Logo Omnes Education" />
        <h1>OMNES Event</h1>
      </a>
      <nav class="nav">
        <a class="nav-link" href="/pages/events.php">Événements</a>
        <a class="nav-link" href="/pages/profile.php">Mon profil</a>
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