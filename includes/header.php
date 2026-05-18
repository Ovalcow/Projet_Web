<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

$currentUser = $currentUser ?? ($_SESSION['user'] ?? null);
$userRole = $currentUser['role'] ?? null;
$userName = $currentUser['nom'] ?? $currentUser['prenom'] ?? 'Utilisateur';

$pageTitle = $pageTitle ?? 'OmnesEvent';

function active_link(string $targetPath): string
{
    $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    return $currentPath === $targetPath ? ' active' : '';
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= e($pageTitle) ?> | OmnesEvent</title>

    <link rel="stylesheet" href="/assets/css/style.css" />
    <link rel="stylesheet" href="/assets/css/responsive.css" />
</head>

<body>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="/index.php" aria-label="Accueil OmnesEvent">
            <img class="brand-logo" src="/assets/img/omnes-logo.webp" alt="Logo OmnesEvent" />
            <span class="brand-name">OMNES Event</span>
        </a>

        <button class="burger-menu" type="button" aria-label="Ouvrir le menu" aria-expanded="false">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <nav class="nav" aria-label="Navigation principale">
            <a class="nav-link<?= active_link('/index.php') ?>" href="/index.php">Accueil</a>
            <a class="nav-link<?= active_link('/pages/events/liste.php') ?>" href="/pages/events/liste.php">Événements</a>

            <?php if (!empty($currentUser)): ?>

                <?php if ($userRole === 'participant'): ?>
                    <a class="nav-link<?= active_link('/pages/reservations/mes_billets.php') ?>" href="/pages/reservations/mes_billets.php">Mes billets</a>
                <?php endif; ?>

                <?php if ($userRole === 'organisateur'): ?>
                    <a class="nav-link<?= active_link('/pages/events/creer.php') ?>" href="/pages/events/creer.php">Créer événement</a>
                    <a class="nav-link<?= active_link('/pages/reservations/inscrits.php') ?>" href="/pages/reservations/inscrits.php">Inscrits</a>
                <?php endif; ?>

                <?php if ($userRole === 'admin'): ?>
                    <a class="nav-link<?= active_link('/pages/admin/dashboard.php') ?>" href="/pages/admin/dashboard.php">Admin</a>
                    <a class="nav-link<?= active_link('/pages/admin/utilisateurs.php') ?>" href="/pages/admin/utilisateurs.php">Utilisateurs</a>
                    <a class="nav-link<?= active_link('/pages/admin/evenements.php') ?>" href="/pages/admin/evenements.php">Modération</a>
                <?php endif; ?>

                <a class="nav-link<?= active_link('/pages/auth/profil.php') ?>" href="/pages/auth/profil.php">Mon profil</a>
                <span class="nav-user">Bonjour <?= e($userName) ?></span>
                <a class="nav-link nav-link-danger" href="/pages/auth/logout.php">Déconnexion</a>

            <?php else: ?>

                <a class="nav-link<?= active_link('/pages/auth/login.php') ?>" href="/pages/auth/login.php">Connexion</a>
                <a class="nav-link nav-link-primary<?= active_link('/pages/auth/register.php') ?>" href="/pages/auth/register.php">Inscription</a>

            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="site-main">
    <div class="container">
        <?php if (!empty($_SESSION['flash'])): ?>
            <div class="flash-message flash-<?= e($_SESSION['flash']['type'] ?? 'info') ?>">
                <?= e($_SESSION['flash']['message'] ?? '') ?>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>
