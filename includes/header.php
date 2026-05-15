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
                <a class="nav-link" href="/pages/auth/profile.php">Mon profil</a>

                <?php if (isset($currentUser) && $currentUser): ?>
                    <!-- TP10 : afficher un message discret pour identifier l'utilisateur -->
                    <span class="nav-link">Bonjour <?php echo htmlspecialchars($currentUser['nom']); ?></span>
                    <a class="nav-link" href="/pages/auth/logout.php">Déconnexion</a>
                <?php else: ?>
                    <a class="nav-link" href="/pages/auth/login.php">Connexion</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="site-main">
        <div class="container">