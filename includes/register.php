<?php
include('../includes/auth.php');

if ($currentUser) {
    header('Location: /pages/index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['nom'], $_POST['email'], $_POST['mot_de_passe'])) {

        $nom   = htmlspecialchars($_POST['nom']);
        $email = htmlspecialchars($_POST['email']);
        $mdp   = $_POST['mot_de_passe'];

        if ($nom === '' || $email === '' || $mdp === '') {
            header('Location: /pages/register.php?erreur=vide');
            exit();
        }

        $requete = $bdd->prepare('SELECT id FROM users WHERE email = :email');
        $requete->execute(array('email' => $email));
        $existe = $requete->fetch();
        $requete->closeCursor();

        if ($existe) {
            header('Location: /pages/register.php?erreur=existe');
            exit();
        }

        // Insertion en base (requête préparée - cours slide 84)
        $requete = $bdd->prepare(
            'INSERT INTO users (nom, email, password_hash, role) VALUES (:nom, :email, :hash, :role)'
        );
        $requete->execute(array(
            'nom'   => $nom,
            'email' => $email,
            'hash'  => password_hash($mdp, PASSWORD_DEFAULT),
            'role'  => 'participant',
        ));

        header('Location: /pages/register.php?succes=1');
        exit();

    } else {
        header('Location: /pages/register.php?erreur=vide');
        exit();
    }
}

$pageTitle = 'Inscription';
include('../includes/header.php');
?>

<h1>Inscription</h1>

<?php if (isset($_GET['erreur'])): ?>
    <?php
    // Affichage du message d'erreur correspondant
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

<!-- Formulaire POST (TP9) -->
<form method="post" action="/pages/register.php">
    <label for="nom">Nom</label>
    <input type="text" name="nom" id="nom" required />

    <label for="email">Email</label>
    <input type="email" name="email" id="email" required />

    <label for="mot_de_passe">Mot de passe</label>
    <input type="password" name="mot_de_passe" id="mot_de_passe" required />

    <button type="submit" class="btn">Créer un compte</button>
</form>

<p><a href="/pages/login.php">Retour à la connexion</a></p>

<?php include('../includes/footer.php'); ?>