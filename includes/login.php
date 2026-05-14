<?php
// Inclusion de auth.php qui fait session_start() + connexion BDD
include('../includes/auth.php');

if ($currentUser) {
    header('Location: /pages/index.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['email'], $_POST['mot_de_passe'])) {
        $email = htmlspecialchars($_POST['email']);
        $mdp   = $_POST['mot_de_passe']; 

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

        if (!isset($_SESSION['tentatives'])) {
            $_SESSION['tentatives'] = 0;
        }
        $_SESSION['tentatives']++;

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

<h1>Connexion</h1>

<?php if (isset($_GET['erreur'])): ?>
    <p class="msg msg-error">Email ou mot de passe incorrect.</p>
<?php endif; ?>

<?php
// TP10 : afficher le nombre de tentatives
if (isset($_SESSION['tentatives']) && $_SESSION['tentatives'] > 0): ?>
    <p class="msg msg-warning">Tentatives : <?php echo $_SESSION['tentatives']; ?></p>
<?php endif; ?>

<!-- Formulaire POST (TP9) -->
<form method="post" action="/pages/login.php">
    <label for="email">Email</label>
    <input type="email" name="email" id="email" required />

    <label for="mot_de_passe">Mot de passe</label>
    <input type="password" name="mot_de_passe" id="mot_de_passe" required />

    <button type="submit" class="btn">Se connecter</button>
</form>

<p><a href="/pages/register.php">Créer un compte</a></p>

<?php include('../includes/footer.php'); ?>