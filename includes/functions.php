<?php
// Fonctions utilitaires
// Redirection simple
function redirect($url) {
    header('Location: ' . $url);
    exit();
}
// Message flash en session (pour afficher après redirection)
function flash($type, $message) {
    $_SESSION['flash'] = array('type' => $type, 'message' => $message);
}

// Afficher le message flash (puis le supprimer)
function afficher_flash() {
    if (isset($_SESSION['flash'])) {
        $type = htmlspecialchars($_SESSION['flash']['type']);
        $message = htmlspecialchars($_SESSION['flash']['message']);
        echo '<p class="msg msg-' . $type . '">' . $message . '</p>';
        unset($_SESSION['flash']);
    }
}