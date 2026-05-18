<?php
// Page publique : pas de auth_check
include('../../config/init.php');
include('../../includes/functions.php');

// Déjà connecté → accueil
if ($currentUser) {
    header('Location: /index.php');
    exit();
}

// Traitement POST (TP9)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['nom'], $_POST['email'], $_POST['mot_de_passe'], $_POST['confirm_mdp'])) {

        $nom       = trim($_POST['nom']);
        $email     = strtolower(trim($_POST['email']));
        $mdp       = $_POST['mot_de_passe'];
        $confirmMdp = $_POST['confirm_mdp'];

        // Vérifier que les champs ne sont pas vides
        if ($nom === '' || $email === '' || $mdp === '') {
            header('Location: /pages/auth/register.php?erreur=vide');
            exit();
        }

        // Vérifier que l'email est valide
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: /pages/auth/register.php?erreur=email');
            exit();
        }

        // Vérifier que les deux mots de passe correspondent
        if ($mdp !== $confirmMdp) {
            header('Location: /pages/auth/register.php?erreur=mdp');
            exit();
        }

        // Vérifier longueur minimale du mot de passe
        if (strlen($mdp) < 6) {
            header('Location: /pages/auth/register.php?erreur=court');
            exit();
        }

        // Vérifier que l'email n'est pas déjà utilisé (requête préparée)
        $requete = $bdd->prepare('SELECT id FROM users WHERE email = :email');
        $requete->execute(array('email' => $email));
        $existe = $requete->fetch();
        $requete->closeCursor();

        if ($existe) {
            header('Location: /pages/auth/register.php?erreur=existe');
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

        flash('success', 'Compte créé ! Vous pouvez vous connecter.');
        header('Location: /pages/auth/login.php');
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

    <?php afficher_flash(); ?>

    <?php if (isset($_GET['erreur'])): ?>
        <?php
        $err = $_GET['erreur'];
        if ($err === 'vide') {
            $msg = 'Tous les champs sont obligatoires.';
        } elseif ($err === 'email') {
            $msg = 'Adresse email invalide.';
        } elseif ($err === 'mdp') {
            $msg = 'Les mots de passe ne correspondent pas.';
        } elseif ($err === 'court') {
            $msg = 'Le mot de passe doit contenir au moins 6 caractères.';
        } elseif ($err === 'existe') {
            $msg = 'Cet email est déjà utilisé.';
        } else {
            $msg = 'Erreur inconnue.';
        }
        ?>
        <p class="msg msg-error"><?php echo $msg; ?></p>
    <?php endif; ?>

    <form method="post" action="/pages/auth/register.php">
        <label for="nom">Nom</label>
        <input type="text" name="nom" id="nom" placeholder="Votre nom" required />

        <label for="email">Email</label>
        <input type="email" name="email" id="email" placeholder="votre@email.com" required />

        <label for="mot_de_passe">Mot de passe</label>
        <input type="password" name="mot_de_passe" id="mot_de_passe" placeholder="Minimum 6 caractères" required />

        <label for="confirm_mdp">Confirmer le mot de passe</label>
        <input type="password" name="confirm_mdp" id="confirm_mdp" placeholder="Retapez votre mot de passe" required />

        <!-- Checkbox pour afficher les mots de passe -->
        <label class="show-password-label">
            <input type="checkbox" id="toggle_mdp" onclick="togglePasswords(this)" />
            <span>Afficher les mots de passe</span>
        </label>

        <button type="submit" class="btn">Créer un compte</button>
    </form>

    <p class="form-footer">Déjà inscrit ? <a href="/pages/auth/login.php">Se connecter</a></p>
</div>

<script>
function togglePasswords(checkbox) {
    var mdp = document.getElementById('mot_de_passe');
    var confirm = document.getElementById('confirm_mdp');
    if (checkbox.checked) {
        mdp.type = 'text';
        confirm.type = 'text';
    } else {
        mdp.type = 'password';
        confirm.type = 'password';
    }
}
</script>

<?php include('../../includes/footer.php'); ?>