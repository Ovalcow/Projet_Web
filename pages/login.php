<?php
session_start();
include('../includes/db.php');

// Vérifier si déjà connecté → redirection vers accueil
$currentUser = null;
if (isset($_SESSION['user_id'])) {
    $requete = $bdd->prepare('SELECT id, nom, email, role FROM users WHERE id = ?');
    $requete->execute(array($_SESSION['user_id']));
    $currentUser = $requete->fetch();
    $requete->closeCursor();
}

if ($currentUser) {
    header('Location: /pages/index.php');
    exit();
}

// Traitement du formulaire (méthode POST - TP9)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['email'], $_POST['mot_de_passe'])) {

        $email = htmlspecialchars($_POST['email']);
        $mdp   = $_POST['mot_de_passe'];

        // Requête préparée (cours slide 84)
        $requete = $bdd->prepare('SELECT id, nom, email, password_hash, role FROM users WHERE email = :email');
        $requete->execute(array('email' => $email));
        $user = $requete->fetch();
        $requete->closeCursor();

        if ($user && password_verify($mdp, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['tentatives'] = 0;
            header('Location: /pages/index.php');
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

        header('Location: /pages/login.php?erreur=1');
        exit();

    } else {
        header('Location: /pages/login.php?erreur=1');
        exit();
    }
}

$pageTitle = 'Connexion';
include('../includes/header.php');
?>

<div class="login-box">

    <div class="login-logo">OE</div>

    <h1>Connexion</h1>
    <p class="login-subtitle">Accédez à votre espace OmnesEvent</p>

    <?php if (isset($_GET['erreur'])): ?>
        <p class="msg msg-error">Email ou mot de passe incorrect.</p>
    <?php endif; ?>

    <?php if (isset($_SESSION['tentatives']) && $_SESSION['tentatives'] > 0): ?>
        <p class="msg msg-warning">Tentatives : <?php echo $_SESSION['tentatives']; ?></p>
    <?php endif; ?>

    <form method="post" action="/pages/login.php">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" placeholder="votre@email.com" required />

        <label for="mot_de_passe">Mot de passe</label>
        <input type="password" name="mot_de_passe" id="mot_de_passe" placeholder="Votre mot de passe" required />

        <button type="submit" class="btn">Se connecter</button>
    </form>

    <p class="form-footer">Pas encore de compte ? <a href="/pages/register.php">Créer un compte</a></p>
</div>

<?php include('../includes/footer.php'); ?>