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
            <a class="brand" href="/index.php">OmnesEvent</a>
            <nav class="nav">
                <a class="nav-link" href="/pages/events/liste.php">Événements</a>

                <?php if (isset($currentUser) && $currentUser): ?>
                    <!-- Liens visibles uniquement pour les connectés -->
                    <a class="nav-link" href="/pages/reservations/mes_billets.php">Mes billets</a>
                    <a class="nav-link" href="/pages/auth/profil.php">Mon profil</a>

                    <?php if ($currentUser['role'] === 'organisateur'): ?>
                        <a class="nav-link" href="/pages/events/creer.php">Créer événement</a>
                    <?php endif; ?>

                    <?php if ($currentUser['role'] === 'admin'): ?>
                        <a class="nav-link" href="/pages/admin/dashboard.php">Admin</a>
                    <?php endif; ?>

                    <!-- TP10 : message discret pour identifier l'utilisateur -->
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