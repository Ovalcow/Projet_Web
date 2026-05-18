    </div>
</main>

<footer class="site-footer">
    <div class="container footer-inner">
        <div class="footer-brand">
            <strong>OMNES Event</strong>
            <p>Plateforme de gestion et de réservation d'événements.</p>
        </div>

        <nav class="footer-nav" aria-label="Navigation secondaire">
            <a href="/index.php">Accueil</a>
            <a href="/pages/events/liste.php">Événements</a>

            <?php if (!empty($_SESSION['user'])): ?>
                <a href="/pages/auth/profil.php">Mon profil</a>
                <a href="/pages/auth/logout.php">Déconnexion</a>
            <?php else: ?>
                <a href="/pages/auth/login.php">Connexion</a>
                <a href="/pages/auth/register.php">Inscription</a>
            <?php endif; ?>
        </nav>

        <p class="footer-copy">
            &copy; <?= date('Y') ?> OmnesEvent — Projet Web Dynamique ING2.
        </p>
    </div>
</footer>

<script src="/assets/js/main.js"></script>
<script src="/assets/js/search.js"></script>
</body>
</html>
