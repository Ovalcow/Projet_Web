<?php
// TP10 : bouton déconnexion qui déconnecte l'utilisateur
session_start();  
$_SESSION = array();
session_destroy();
header('Location: /pages/login.php');
exit();