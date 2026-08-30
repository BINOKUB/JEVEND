<?php
// =============================================================================
// NOM DU SCRIPT : deconnexion.php
// REVISION     : 2.0 - Suppression du Cookie Longue Durée (60 jours) et de la session
// =============================================================================

session_start();
require_once 'config.php';

// 1. Invalider le jeton en BDD si l'utilisateur était connecté
if (isset($_SESSION['id_utilisateur'])) {
    try {
        $stmt_reset = $bdd->prepare("
            UPDATE jevend_utilisateurs 
            SET jeton_connexion = NULL, jeton_expiration = NULL 
            WHERE id_utilisateur = ?
        ");
        $stmt_reset->execute([$_SESSION['id_utilisateur']]);
    } catch (PDOException $e) { }
}

// 2. Supprimer le cookie longue durée "jevend_remember"
if (isset($_COOKIE['jevend_remember'])) {
    setcookie('jevend_remember', '', time() - 3600, '/', '', false, true);
}

// 3. Vider toutes les variables de session
$_SESSION = array();

// 4. Détruire le cookie de session PHP natif
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 5. Destruction complète de la session
session_destroy();

// 6. Redirection vers la page de connexion
header("Location: connexion.php");
exit();
