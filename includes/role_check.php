<?php
// Vérification du rôle de l'utilisateur
// Utilisation : include cette page après auth_check.php, puis appeler check_role()

function check_role($role_requis) {
    global $currentUser;

    if (!$currentUser || $currentUser['role'] !== $role_requis) {
        header('Location: /index.php');
        exit();
    }
}

// Vérifier si l'utilisateur a au moins un des rôles autorisés
function check_roles($roles_autorises) {
    global $currentUser;

    if (!$currentUser || !in_array($currentUser['role'], $roles_autorises)) {
        header('Location: /index.php');
        exit();
    }
}