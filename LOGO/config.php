<?php
date_default_timezone_set('America/Montreal');
// =============================================================================
// CONFIGURATION SERVEUR ET SÉCURITÉ DATABASE
// REVISION : 3.2 - Définition globale centralisée du Mode Maintenance
// NOM DU SCRIPT : config.php
// =============================================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'jevend_db');
define('DB_USER', 'root');
define('DB_PASS', '45309100ldmte');

try {
    $bdd = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) {
    die("Erreur critique de connexion au serveur de données.");
}

// =============================================================================
// RECONNEXION AUTOMATIQUE GLISSANTE (RÉSERVÉE EXCLUSIVEMENT AUX MEMBRES)
// =============================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_utilisateur']) && isset($_COOKIE['jevend_remember'])) {
    $token_recu = trim($_COOKIE['jevend_remember']);

    if (!empty($token_recu) && strlen($token_recu) === 64) {
        try {
            // Recherche du membre lié au jeton valide
            $stmt_auto = $bdd->prepare("
                SELECT id_utilisateur, nom, courriel, role, type_compte 
                FROM jevend_utilisateurs 
                WHERE jeton_connexion = ? 
                  AND jeton_expiration > NOW() 
                  AND statut = 'actif'
                LIMIT 1
            ");
            $stmt_auto->execute([$token_recu]);
            $membre_auto = $stmt_auto->fetch(PDO::FETCH_ASSOC);

            // SÉCURITÉ : On interdit la restauration automatique si le rôle est 'admin'
            if ($membre_auto && $membre_auto['role'] !== 'admin') {
                
                // 1. Rétablissement complet de TOUTES les variables de session
                $_SESSION['id_utilisateur'] = $membre_auto['id_utilisateur'];
                $_SESSION['nom']            = $membre_auto['nom'];
                $_SESSION['courriel']       = strtolower(trim($membre_auto['courriel']));
                $_SESSION['role']           = $membre_auto['role'];
                $_SESSION['type_compte']    = $membre_auto['type_compte'] ?? 'particulier';

                // 2. RENOUVELLEMENT GLISSANT (60 jours pour le membre)
                $nouvelle_expiration = date('Y-m-d H:i:s', strtotime('+60 days'));
                $update_glissant = $bdd->prepare("
                    UPDATE jevend_utilisateurs 
                    SET jeton_expiration = ? 
                    WHERE id_utilisateur = ?
                ");
                $update_glissant->execute([$nouvelle_expiration, $membre_auto['id_utilisateur']]);

                // 3. Prolongation du cookie
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
                // Si jeton expiré, invalide OU si tentative d'auto-connexion admin : Destruction du cookie
                setcookie('jevend_remember', '', time() - 3600, '/');
            }
        } catch (PDOException $e) {
            // Silencieux
        }
    }
}

// =============================================================================
// VERIFICATION CENTRALISÉE DU MODE MAINTENANCE
// =============================================================================
try {
    $stmt_maint = $bdd->query("SELECT valeur_parametre FROM jevend_parametres WHERE cle_parametre = 'maintenance_actif'");
    $maint_status = $stmt_maint->fetchColumn();
    define('SITE_EN_MAINTENANCE', $maint_status === '1');
} catch (PDOException $e) {
    define('SITE_EN_MAINTENANCE', false);
}
