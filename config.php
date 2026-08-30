<?php
date_default_timezone_set('America/Montreal');
// =============================================================================
// CONFIGURATION SERVEUR ET SÉCURITÉ DATABASE
// REVISION : 3.0 - Auto-connexion glissante (60 jours renouvelables)
// NOM DU SCRIPT : config.php
// =============================================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'jevend_db');
define('DB_USER', 'root');          // Remplace par ton utilisateur MariaDB si différent
define('DB_PASS', '45309100ldmte');  // Mot de passe de ta base de données MariaDB

try {
    $bdd = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) {
    die("Erreur critique de connexion au serveur de données.");
}

// =============================================================================
// RECONNEXION AUTOMATIQUE GLISSANTE (60 JOURS RENOUVELÉS À CHAQUE VISITE)
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
                // 1. Rétablissement instantané de la session membre
                $_SESSION['id_utilisateur'] = $membre_auto['id_utilisateur'];
                $_SESSION['role']           = $membre_auto['role'];
                $_SESSION['type_compte']     = $membre_auto['type_compte'];

                // 2. RENOUVELLEMENT GLISSANT : On repousse la date de 60 jours en BDD
                $nouvelle_expiration = date('Y-m-d H:i:s', strtotime('+60 days'));
                $update_glissant = $bdd->prepare("
                    UPDATE jevend_utilisateurs 
                    SET jeton_expiration = ? 
                    WHERE id_utilisateur = ?
                ");
                $update_glissant->execute([$nouvelle_expiration, $membre_auto['id_utilisateur']]);

                // 3. Prolongation du cookie sur le navigateur pour 60 jours supplémentaires
                $duree_cookie = time() + (60 * 24 * 60 * 60);
                setcookie(
                    'jevend_remember',
                    $token_recu,
                    [
                        'expires'  => $duree_cookie,
                        'path'     => '/',
                        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]
                );
            } else {
                // Jeton expiré ou invalide : nettoyage du cookie
                setcookie('jevend_remember', '', time() - 3600, '/');
            }
        } catch (PDOException $e) {
            // Silencieux en cas d'erreur
        }
    }
}
