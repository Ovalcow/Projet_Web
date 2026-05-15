<?php
include('../../config/init.php');
include('../../includes/auth_check.php');
include('../../includes/role_check.php');
check_role('admin');

$nbUsers = $bdd->query('SELECT COUNT(*) FROM users')->fetchColumn();
$nbEvents = $bdd->query('SELECT COUNT(*) FROM events')->fetchColumn();
$nbReservations = $bdd->query('SELECT COUNT(*) FROM reservations')->fetchColumn();

$pageTitle = 'Dashboard Admin';
include('../../includes/header.php');
?>

<h1>Tableau de bord administrateur</h1>

<div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:16px; margin-top:20px;">
    <div class="profile-info" style="text-align:center;">
        <p style="font-size:32px; font-weight:700; color:var(--primary); margin:0;"><?php echo $nbUsers; ?></p>
        <p><strong>Utilisateurs</strong></p>
    </div>
    <div class="profile-info" style="text-align:center;">
        <p style="font-size:32px; font-weight:700; color:var(--primary); margin:0;"><?php echo $nbEvents; ?></p>
        <p><strong>Événements</strong></p>
    </div>
    <div class="profile-info" style="text-align:center;">
        <p style="font-size:32px; font-weight:700; color:var(--primary); margin:0;"><?php echo $nbReservations; ?></p>
        <p><strong>Réservations</strong></p>
    </div>
</div>

<div style="margin-top:20px; display:flex; gap:12px;">
    <a class="btn" href="/pages/admin/utilisateurs.php">Gérer utilisateurs</a>
    <a class="btn btn-secondary" href="/pages/admin/evenements.php">Gérer événements</a>
</div>

<?php include('../../includes/footer.php'); ?>