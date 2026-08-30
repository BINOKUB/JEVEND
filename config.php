<?php
date_default_timezone_set('America/Montreal');
// =============================================================================
// CONFIGURATION SERVEUR ET SÉCURITÉ DATABASE
// REVISION : 2.0 - Auto-connexion via Cookie de Session Longue (60 Jours)
// NOM DU SCRIPT : config.php
// =============================================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'jevend_db');
define('DB_USER', 'root');          // Remplace par ton utilisateur MariaDB si différent
define('DB_PASS', '45309100ldmte');  // Mets ici le mot de passe de ta base de données MariaDB

try {
    $bdd = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) {
    die("Erreur critique de connexion au serveur de données.");
}

// =============================================================================
// RECONNEXION AUTOMATIQUE (60 JOURS)
// =============================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_utilisateur']) && isset($_COOKIE['jevend_remember'])) {
    $token_recu = trim($_COOKIE['jevend_remember']);

    if (!empty($token_recu) && strlen($token_recu) === 64) {
        try {
            // Recherche du membre lié au jeton valide et non expiré
            $stmt_auto = $bdd->prepare("
                SELECT id_utilisateur, role, type_compte 
                FROM jevend_utilisateurs 
                WHERE jeton_connexion = ? 
                  AND jeton_expiration > NOW() 
                  AND statut = 'actif'
                LIMIT 1
            ");
            $stmt_auto->execute([$token_recu]);
            $membre_auto = $stmt_auto->fetch(PDO::FETCH_ASSOC);

            if ($membre_auto) {
                // Rétablissement instantané de la session membre
                $_SESSION['id_utilisateur'] = $membre_auto['id_utilisateur'];
                $_SESSION['role']           = $membre_auto['role'];
                $_SESSION['type_compte']     = $membre_auto['type_compte'];
            } else {
                // Jeton expiré ou invalide : nettoyage du cookie
                setcookie('jevend_remember', '', time() - 3600, '/');
            }
        } catch (PDOException $e) {
            // Silencieux en cas d'erreur
        }
    }
}
