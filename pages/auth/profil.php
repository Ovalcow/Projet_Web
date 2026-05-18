<?php
include('../../config/init.php');
include('../../includes/auth_check.php');
include('../../includes/functions.php');

// Charger les infos complètes avec l'association
$requete = $bdd->prepare(
    'SELECT u.*, a.nom AS association_nom
     FROM users u
     LEFT JOIN associations a ON u.association_id = a.id
     WHERE u.id = ?'
);
$requete->execute(array($currentUser['id']));
$user = $requete->fetch();
$requete->closeCursor();

// ============ TRAITEMENT POST : modification du profil ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // --- Modifier nom / email ---
    if ($_POST['action'] === 'modifier_infos') {

        $nom   = trim($_POST['nom'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));

        if ($nom === '' || $email === '') {
            flash('error', 'Le nom et l\'email sont obligatoires.');
            header('Location: /pages/auth/profil.php');
            exit();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Adresse email invalide.');
            header('Location: /pages/auth/profil.php');
            exit();
        }

        // Vérifier que l'email n'est pas pris par un autre utilisateur
        $reqCheck = $bdd->prepare('SELECT id FROM users WHERE email = :email AND id != :id');
        $reqCheck->execute(array('email' => $email, 'id' => $currentUser['id']));
        if ($reqCheck->fetch()) {
            flash('error', 'Cet email est déjà utilisé par un autre compte.');
            header('Location: /pages/auth/profil.php');
            exit();
        }
        $reqCheck->closeCursor();

        // Mise à jour
        $requete = $bdd->prepare('UPDATE users SET nom = :nom, email = :email WHERE id = :id');
        $requete->execute(array('nom' => $nom, 'email' => $email, 'id' => $currentUser['id']));

        flash('success', 'Informations mises à jour.');
        header('Location: /pages/auth/profil.php');
        exit();
    }

    // --- Changer le mot de passe ---
    if ($_POST['action'] === 'changer_mdp') {

        $ancien = $_POST['ancien_mdp'] ?? '';
        $nouveau = $_POST['nouveau_mdp'] ?? '';
        $confirm = $_POST['confirm_mdp'] ?? '';

        // Vérifier l'ancien mot de passe
        if (!password_verify($ancien, $user['password_hash'])) {
            flash('error', 'Ancien mot de passe incorrect.');
            header('Location: /pages/auth/profil.php');
            exit();
        }

        // Vérifier que les deux nouveaux mots de passe correspondent
        if ($nouveau !== $confirm) {
            flash('error', 'Les nouveaux mots de passe ne correspondent pas.');
            header('Location: /pages/auth/profil.php');
            exit();
        }

        // Vérifier la longueur minimale
        if (strlen($nouveau) < 6) {
            flash('error', 'Le mot de passe doit contenir au moins 6 caractères.');
            header('Location: /pages/auth/profil.php');
            exit();
        }

        // Mise à jour du mot de passe
        $requete = $bdd->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $requete->execute(array('hash' => password_hash($nouveau, PASSWORD_DEFAULT), 'id' => $currentUser['id']));

        flash('success', 'Mot de passe modifié avec succès.');
        header('Location: /pages/auth/profil.php');
        exit();
    }
}

$pageTitle = 'Mon profil';
include('../../includes/header.php');
?>

<h1>Mon profil</h1>

<?php afficher_flash(); ?>

<!-- Informations actuelles -->
<div class="profile-info">
    <p><strong>Rôle :</strong> <?php echo htmlspecialchars($user['role']); ?></p>
    <?php if ($user['association_nom']): ?>
        <p><strong>Association :</strong> <?php echo htmlspecialchars($user['association_nom']); ?></p>
    <?php endif; ?>
    <p><strong>Membre depuis :</strong> <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></p>
</div>

<!-- Formulaire modification nom / email -->
<h2 style="margin-top:30px;">Modifier mes informations</h2>

<form method="post" action="/pages/auth/profil.php" style="max-width:500px;">
    <input type="hidden" name="action" value="modifier_infos" />

    <label for="nom">Nom</label>
    <input type="text" name="nom" id="nom" value="<?php echo htmlspecialchars($user['nom']); ?>" required />

    <label for="email">Email</label>
    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" required />

    <button type="submit" class="btn" style="margin-top:14px;">Enregistrer</button>
</form>

<!-- Formulaire changement de mot de passe -->
<h2 style="margin-top:30px;">Changer le mot de passe</h2>

<form method="post" action="/pages/auth/profil.php" style="max-width:500px;">
    <input type="hidden" name="action" value="changer_mdp" />

    <label for="ancien_mdp">Mot de passe actuel</label>
    <input type="password" name="ancien_mdp" id="ancien_mdp" required />

    <label for="nouveau_mdp">Nouveau mot de passe</label>
    <input type="password" name="nouveau_mdp" id="nouveau_mdp" placeholder="Minimum 6 caractères" required />

    <label for="confirm_mdp">Confirmer le nouveau mot de passe</label>
    <input type="password" name="confirm_mdp" id="confirm_mdp" required />

    <!-- Checkbox pour voir les mots de passe -->
    <label class="show-password-label">
        <input type="checkbox" onclick="toggleProfilePasswords(this)" />
        <span>Afficher les mots de passe</span>
    </label>

    <button type="submit" class="btn" style="margin-top:14px;">Changer le mot de passe</button>
</form>

<script>
function toggleProfilePasswords(checkbox) {
    var ids = ['ancien_mdp', 'nouveau_mdp', 'confirm_mdp'];
    for (var i = 0; i < ids.length; i++) {
        var input = document.getElementById(ids[i]);
        input.type = checkbox.checked ? 'text' : 'password';
    }
}
</script>

<?php include('../../includes/footer.php'); ?>