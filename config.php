<?php
date_default_timezone_set('America/Montreal');
// =============================================================================
// CONFIGURATION SERVEUR ET SÉCURITÉ DATABASE
// REVISION : 1.0
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
