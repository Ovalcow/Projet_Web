<?php
include('../../config/init.php');
include('../../includes/auth_check.php');

$pageTitle = 'Détail événement';

if (!isset($_GET['id'])) {
    include('../../includes/header.php');
    echo '<section class="container"><p>ID événement manquant.</p></section>';
    include('../../includes/footer.php');
    exit;
}

$id = (int)$_GET['id'];

if ($id <= 0) {
    include('../../includes/header.php');
    echo '<section class="container"><p>ID événement invalide.</p></section>';
    include('../../includes/footer.php');
    exit;
}

$requete = $bdd->prepare(
    "SELECT e.id, e.titre, e.description, e.date_event, e.lieu, e.jauge_max,
            (SELECT COUNT(*) FROM reservations r WHERE r.event_id = e.id) AS nb_reservations,
            e.affiche_path,
            a.nom AS association_nom,
            c.nom AS category_nom
     FROM events e
     LEFT JOIN associations a ON a.id = e.association_id
     LEFT JOIN categories c ON c.id = e.category_id
     WHERE e.id = :id"
);
$requete->execute(array('id' => $id));
$event = $requete->fetch();
$requete->closeCursor();

include('../../includes/header.php');

if (!$event) {
    echo '<section class="container"><p>Événement introuvable.</p></section>';
    include('../../includes/footer.php');
    exit;
}

$nb  = (int)$event['nb_reservations'];
$max = (int)$event['jauge_max'];
$placesRestantes = $max - $nb;
if ($placesRestantes < 0) { $placesRestantes = 0; }

// Vérifier si l'utilisateur est déjà inscrit
$reqInscrit = $bdd->prepare('SELECT id FROM reservations WHERE event_id = :eid AND participant_id = :uid');
$reqInscrit->execute(array('eid' => $id, 'uid' => $currentUser['id']));
$dejaInscrit = $reqInscrit->fetch();
$reqInscrit->closeCursor();
?>

<section class="container">
    <h1><?php echo htmlspecialchars($event['titre']); ?></h1>

    <div class="event-detail">
        <div style="display:flex; gap:14px; align-items:flex-start; flex-wrap:wrap;">
            <div>
                <?php if (!empty($event['affiche_path'])): ?>
                    <img src="/uploads/events/<?php echo htmlspecialchars($event['affiche_path']); ?>"
                         alt="Affiche"
                         style="width:180px; height:180px; object-fit:cover; border-radius:14px;" />
                <?php else: ?>
                    <img src="/assets/img/default_event.jpg"
                         alt="Affiche par défaut"
                         style="width:180px; height:180px; object-fit:cover; border-radius:14px;" />
                <?php endif; ?>
            </div>

            <div style="flex:1; min-width:240px;">
                <p><?php echo date('d/m/Y H:i', strtotime($event['date_event'])); ?></p>
                <p><?php echo htmlspecialchars($event['lieu']); ?></p>
                <p>Catégorie : <?php echo htmlspecialchars($event['category_nom'] ?? ''); ?></p>
                <p>Association : <?php echo htmlspecialchars($event['association_nom'] ?? ''); ?></p>
                <p>Places restantes : <strong><?php echo $placesRestantes; ?></strong> / <?php echo $max; ?></p>

                <div style="margin-top:12px; display:flex; gap:10px;">
                    <a class="btn btn-secondary" href="/pages/events/liste.php">Retour</a>

                    <?php if ($dejaInscrit): ?>
                        <span class="msg msg-success" style="margin:0;">Vous êtes inscrit</span>
                        <a class="btn" href="/pages/reservations/annuler.php?event_id=<?php echo $id; ?>" style="background:var(--accent);">Annuler</a>
                    <?php elseif ($placesRestantes > 0): ?>
                        <a class="btn" href="/pages/reservations/reserver.php?event_id=<?php echo $id; ?>">S'inscrire</a>
                    <?php else: ?>
                        <span class="msg msg-warning" style="margin:0;">Complet</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div style="margin-top:14px;">
            <h2>Description</h2>
            <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
        </div>
    </div>
</section>

<?php include('../../includes/footer.php'); ?>