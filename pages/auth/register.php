<?php
// Page publique : pas de auth_check
include('../../config/init.php');
include('../../includes/functions.php');

if ($currentUser) {
    header('Location: /index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['nom'], $_POST['email'], $_POST['mot_de_passe'])) {

        $nom   = $_POST['nom'];
        $email = $_POST['email'];
        $mdp   = $_POST['mot_de_passe'];

        if ($nom === '' || $email === '' || $mdp === '') {
            header('Location: /pages/auth/register.php?erreur=vide');
            exit();
        }

        $requete = $bdd->prepare('SELECT id FROM users WHERE email = :email');
        $requete->execute(array('email' => $email));
        $existe = $requete->fetch();
        $requete->closeCursor();

        if ($existe) {
            header('Location: /pages/auth/register.php?erreur=existe');
            exit();
        }

        $requete = $bdd->prepare(
            'INSERT INTO users (nom, email, password_hash, role) VALUES (:nom, :email, :hash, :role)'
        );
        $requete->execute(array(
            'nom'   => $nom,
            'email' => $email,
            'hash'  => password_hash($mdp, PASSWORD_DEFAULT),
            'role'  => 'participant',
        ));

        header('Location: /pages/auth/register.php?succes=1');
        exit();

    } else {
        header('Location: /pages/auth/register.php?erreur=vide');
        exit();
    }
}

$pageTitle = 'Inscription';
include('../../includes/header.php');
?>

<div class="login-box">
    <div class="login-logo">OE</div>
    <h1>Inscription</h1>
    <p class="login-subtitle">Créez votre compte OmnesEvent</p>

    <?php if (isset($_GET['erreur'])): ?>
        <?php
        if ($_GET['erreur'] === 'vide') {
            $msg = 'Tous les champs sont obligatoires.';
        } elseif ($_GET['erreur'] === 'existe') {
            $msg = 'Cet email est déjà utilisé.';
        } else {
            $msg = 'Erreur inconnue.';
        }
        ?>
        <p class="msg msg-error"><?php echo $msg; ?></p>
    <?php endif; ?>

    <?php if (isset($_GET['succes'])): ?>
        <p class="msg msg-success">Compte créé ! Vous pouvez vous connecter.</p>
    <?php endif; ?>

    <form method="post" action="/pages/auth/register.php">
        <label for="nom">Nom</label>
        <input type="text" name="nom" id="nom" placeholder="Votre nom" required />

        <label for="email">Email</label>
        <input type="email" name="email" id="email" placeholder="votre@email.com" required />

        <label for="mot_de_passe">Mot de passe</label>
        <input type="password" name="mot_de_passe" id="mot_de_passe" placeholder="Choisissez un mot de passe" required />

        <button type="submit" class="btn">Créer un compte</button>
    </form>

    <p class="form-footer">Déjà inscrit ? <a href="/pages/auth/login.php">Se connecter</a></p>
</div>

<?php include('../../includes/footer.php'); ?>