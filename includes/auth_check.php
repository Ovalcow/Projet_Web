<?php
// Inclure APRÈS config/init.php sur les pages protégées
// Redirige vers login si l'utilisateur n'est pas connecté

if (!$currentUser) {
    header('Location: /pages/auth/login.php');
    exit();
}