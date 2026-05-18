<?php
// Page publique : on n'inclut PAS auth_check (sinon boucle infinie)
include('../../config/init.php');
include('../../includes/functions.php');

// Déjà connecté → accueil
if ($currentUser) {
    header('Location: /index.php');
    exit();
}

// Traitement POST (TP9)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['email'], $_POST['mot_de_passe'])) {

        $email = strtolower(trim($_POST['email']));
        $mdp   = $_POST['mot_de_passe'];

        // Vérification basique
        if ($email === '' || $mdp === '') {
            header('Location: /pages/auth/login.php?erreur=vide');
            exit();
        }

        // Requête préparée (cours slide 84)
        $requete = $bdd->prepare('SELECT id, nom, email, password_hash, role FROM users WHERE email = :email');
        $requete->execute(array('email' => $email));
        $user = $requete->fetch();
        $requete->closeCursor();

        if ($user && password_verify($mdp, $user['password_hash'])) {
            // Régénérer l'ID de session pour éviter le session fixation
            session_regenerate_id(true);

            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['tentatives'] = 0;

            header('Location: /index.php');
            exit();
        }

        // TP10 : compteur de tentatives
        if (!isset($_SESSION['tentatives'])) {
            $_SESSION['tentatives'] = 0;
        }
        $_SESSION['tentatives']++;

        // TP10 : sleep(5) après 3 tentatives
        if ($_SESSION['tentatives'] >= 3) {
            sleep(5);
            $_SESSION['tentatives'] = 0;
        }

        header('Location: /pages/auth/login.php?erreur=1');
        exit();

    } else {
        header('Location: /pages/auth/login.php?erreur=vide');
        exit();
    }
}

$pageTitle = 'Connexion';
include('../../includes/header.php');
?>

<div class="login-box">
    <div class="login-logo">OE</div>
    <h1>Connexion</h1>
    <p class="login-subtitle">Accédez à votre espace OmnesEvent</p>

    <?php afficher_flash(); ?>

    <?php if (isset($_GET['erreur']) && $_GET['erreur'] === '1'): ?>
        <p class="msg msg-error">Email ou mot de passe incorrect.</p>
    <?php endif; ?>

    <?php if (isset($_GET['erreur']) && $_GET['erreur'] === 'vide'): ?>
        <p class="msg msg-error">Veuillez remplir tous les champs.</p>
    <?php endif; ?>

    <?php if (isset($_SESSION['tentatives']) && $_SESSION['tentatives'] > 0): ?>
        <p class="msg msg-warning">Tentatives : <?php echo (int)$_SESSION['tentatives']; ?></p>
    <?php endif; ?>

    <form method="post" action="/pages/auth/login.php">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" placeholder="votre@email.com" required />

        <label for="mot_de_passe">Mot de passe</label>
        <input type="password" name="mot_de_passe" id="mot_de_passe" placeholder="Votre mot de passe" required />

        <!-- Checkbox pour afficher le mot de passe -->
        <label class="show-password-label">
            <input type="checkbox" id="toggle_mdp" onclick="togglePassword('mot_de_passe', this)" />
            <span>Afficher le mot de passe</span>
        </label>

        <button type="submit" class="btn">Se connecter</button>
    </form>

    <p class="form-footer">Pas encore de compte ? <a href="/pages/auth/register.php">Créer un compte</a></p>
</div>

<script>
function togglePassword(inputId, checkbox) {
    var input = document.getElementById(inputId);
    if (checkbox.checked) {
        input.type = 'text';
    } else {
        input.type = 'password';
    }
}
</script>

<?php include('../../includes/footer.php'); ?>