<?php
declare(strict_types=1);

// Connexion à MySQL avec PDO

try {
    $bdd = new PDO(
        'mysql:host=localhost;dbname=omnes_event;charset=utf8',
        'root',
        '',
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
} catch (Exception $e) {
    die('Erreur : ' . $e->getMessage());
}

/**
 * Retourne une seule ligne (ou null) depuis la BDD.
 * Utilise des requêtes préparées avec :param.
 */
function db_single(string $sql, array $params = []): ?array {
    global $bdd;
    $stmt = $bdd->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    return $row !== false ? $row : null;
}

/**
 * Retourne toutes les lignes depuis la BDD.
 */
function db_all(string $sql, array $params = []): array {
    global $bdd;
    $stmt = $bdd->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    return is_array($rows) ? $rows : [];
}

/**
 * Exécute une requête d'écriture (INSERT/UPDATE/DELETE).
 * Retourne le nombre de lignes affectées.
 */
function db_execute(string $sql, array $params = []): int {
    global $bdd;
    $stmt = $bdd->prepare($sql);
    $stmt->execute($params);
    $affected = $stmt->rowCount();
    $stmt->closeCursor();
    return (int)$affected;
}

