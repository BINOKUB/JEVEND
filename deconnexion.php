<?php
// NOM DU SCRIPT : deconnexion.php
// REVISION : 1.0 - Destruction propre de la session et redirection
// SCRIPT COMPLET ET SUIVI

session_start();

// On vide toutes les variables de session
$_SESSION = array();

// On détruit le cookie de session si existant
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// On détruit la session sur le serveur
session_destroy();

// Redirection immédiate vers la page de connexion
header("Location: connexion.php");
exit();
