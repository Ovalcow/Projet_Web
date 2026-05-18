<?php declare(strict_types=1);

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_login();

// Admin only
require_role(['admin']);


$pageTitle = 'Dashboard Admin';

$nbUsersRow = db_single('SELECT COUNT(*) AS c FROM users');
$nbUsers = isset($nbUsersRow['c']) ? (int)$nbUsersRow['c'] : 0;

$nbEventsRow = db_single('SELECT COUNT(*) AS c FROM events');
$nbEvents = isset($nbEventsRow['c']) ? (int)$nbEventsRow['c'] : 0;

$nbReservationsRow = db_single('SELECT COUNT(*) AS c FROM reservations');
$nbReservations = isset($nbReservationsRow['c']) ? (int)$nbReservationsRow['c'] : 0;



require_once __DIR__ . '/../../includes/header.php';
?>

<h1>Tableau de bord administrateur</h1>

<div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:16px; margin-top:20px;">
  <div style="text-align:center;">
    <p style="font-size:32px; font-weight:700; color:var(--primary); margin:0;"><?= (int)$nbUsers ?></p>
    <p><strong>Utilisateurs</strong></p>
  </div>
  <div style="text-align:center;">
    <p style="font-size:32px; font-weight:700; color:var(--primary); margin:0;"><?= (int)$nbEvents ?></p>
    <p><strong>Événements</strong></p>
  </div>
  <div style="text-align:center;">
    <p style="font-size:32px; font-weight:700; color:var(--primary); margin:0;"><?= (int)$nbReservations ?></p>
    <p><strong>Réservations</strong></p>
  </div>
</div>

<div style="margin-top:20px; display:flex; gap:12px; flex-wrap:wrap;">
  <a class="btn" href="/pages/admin/utilisateurs.php">Gérer utilisateurs</a>
  <a class="btn btn-secondary" href="/pages/admin/evenements.php">Gérer événements</a>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

