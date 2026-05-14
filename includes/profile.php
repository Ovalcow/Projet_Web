<?php
// Inclusion de auth.php (session_start + connexion BDD)
include('../includes/auth.php');

// Si pas connecté, redirection vers login
if (!$currentUser) {
    header('Location: /pages/login.php');
    exit();
}

$requete = $bdd->prepare(
    'SELECT u.*, a.nom AS association_nom
     FROM users u
     LEFT JOIN associations a ON u.association_id = a.id
     WHERE u.id = ?'
);
$requete->execute(array($currentUser['id']));
$user = $requete->fetch();
$requete->closeCursor();

$pageTitle = 'Mon profil';
include('../includes/header.php');
?>

<h1>Mon profil</h1>

<div class="profile-info">
    <p><strong>Nom :</strong> <?php echo htmlspecialchars($user['nom']); ?></p>
    <p><strong>Email :</strong> <?php echo htmlspecialchars($user['email']); ?></p>
    <p><strong>Rôle :</strong> <?php echo htmlspecialchars($user['role']); ?></p>

    <?php if ($user['association_nom']): ?>
        <p><strong>Association :</strong> <?php echo htmlspecialchars($user['association_nom']); ?></p>
    <?php endif; ?>

    <p><strong>Membre depuis :</strong> <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></p>
</div>

<?php include('../includes/footer.php'); ?>