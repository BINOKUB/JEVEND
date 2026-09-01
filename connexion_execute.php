<?php
// =============================================================================
// NOM DU SCRIPT : connexion_execute.php
// REVISION     : 1.5 - Blocage de la création du cookie 60 jours pour le rôle Admin
// SCRIPT COMPLET ET SUIVI
// =============================================================================
session_start();
require_once 'config.php';
date_default_timezone_set('America/Montreal');

// Redirection si déjà connecté
if (isset($_SESSION['id_utilisateur'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header("Location: panneau.php");
    } else {
        header("Location: espace_membre.php");
    }
    exit();
}

$action = $_POST['action'] ?? '';

// -----------------------------------------------------------------------------
// ÉTAPE 1 : GÉNÉRATION ET ENVOI DU CODE DE SÉCURITÉ
// -----------------------------------------------------------------------------
if ($action === 'demande_code') {
    $courriel = strtolower(trim($_POST['courriel'] ?? ''));

    if (empty($courriel) || !filter_var($courriel, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['erreur_connexion'] = "Veuillez entrer une adresse courriel valide.";
        header("Location: connexion.php");
        exit();
    }

    // Protection Anti-Spam (Rate Limiting : 60 secondes entre chaque demande)
    if (isset($_SESSION['dernier_envoi_code']) && (time() - $_SESSION['dernier_envoi_code']) < 60) {
        $_SESSION['erreur_connexion'] = "Veuillez attendre une minute avant de demander un nouveau code.";
        header("Location: connexion.php");
        exit();
    }

    try {
        $stmt = $bdd->prepare("SELECT id_utilisateur, nom, statut FROM jevend_utilisateurs WHERE courriel = ?");
        $stmt->execute([$courriel]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if ($user['statut'] === 'bloque') {
                $_SESSION['erreur_connexion'] = "L'accès à ce compte a été suspendu par l'administration.";
            } else {
                // Récupération du délai de validité configuré dans la BDD (par défaut 15 min)
                $duree_validite = 15;
                $stmt_cfg = $bdd->query("SELECT duree_validite_minutes FROM jevend_format_email WHERE cle_template = 'code_connexion' LIMIT 1");
                $cfg_val = $stmt_cfg->fetchColumn();
                if ($cfg_val && (int)$cfg_val > 0) {
                    $duree_validite = (int)$cfg_val;
                }

                // Code secret à 6 chiffres
                $code_securite = (string)rand(100000, 999999);
                $expiration = date('Y-m-d H:i:s', strtotime('+' . $duree_validite . ' minutes'));

                $update = $bdd->prepare("UPDATE jevend_utilisateurs SET jeton_connexion = ?, jeton_expiration = ? WHERE id_utilisateur = ?");
                $update->execute([$code_securite, $expiration, $user['id_utilisateur']]);

                $_SESSION['temp_email_connexion'] = $courriel;
                $_SESSION['dernier_envoi_code'] = time();
                $_SESSION['essais_code_connexion'] = 0;

                // Console Log pour environnement de dev/test
                echo "<script>console.log('%c[TEST JETON] Courriel: " . addslashes($courriel) . " | CODE SECRET: " . $code_securite . "', 'background: #16a34a; color: #ffffff; font-size: 14px; padding: 8px; font-weight: bold; border-radius: 4px;');</script>";

                // -------------------------------------------------------------
                // CHARGEMENT DU VISUEL SÉPARÉ (_code_mail_visuel.php)
                // -------------------------------------------------------------
                $nom_affiche = htmlspecialchars($user['nom'] ?? 'Membre');
                $sujet = "Votre code de connexion unique - jevend.com";

                ob_start();
                include 'partials/_code_mail_visuel.php';
                $message = ob_get_clean();

                // -------------------------------------------------------------
                // BASCULEMENT D'ENVOI : PHP MAIL vs SMTP
                // -------------------------------------------------------------
                $mode_envoi = 'PHP_MAIL'; // Changer en 'SMTP' au besoin

                if ($mode_envoi === 'PHP_MAIL') {
                    $headers  = "MIME-Version: 1.0\r\n";
                    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                    $headers .= "From: jevend.com <no-reply@jevend.com>\r\n";
                    $headers .= "Reply-To: no-reply@jevend.com\r\n";
                    $headers .= "X-Mailer: PHP/" . phpversion();

                    @mail($courriel, $sujet, $message, $headers);
                } elseif ($mode_envoi === 'SMTP') {
                    /* Emplacement PHPMailer */
                }

                $_SESSION['succes_connexion'] = "Un code secret d'accès unique a été généré. Consultez votre boîte de réception.";
            }
        } else {
            $_SESSION['erreur_connexion'] = "Aucun compte n'est configuré avec cette adresse courriel. Veuillez d'abord vous inscrire.";
        }
    } catch (PDOException $e) {
        $_SESSION['erreur_connexion'] = "Une erreur technique est survenue lors de la communication avec la base de données.";
    }

    header("Location: connexion.php");
    exit();
}

// -----------------------------------------------------------------------------
// ÉTAPE 2 : VALIDATION DU CODE ET OUVERTURE DE SESSION
// -----------------------------------------------------------------------------
if ($action === 'valider_code') {
    $courriel_session = $_SESSION['temp_email_connexion'] ?? '';
    $code_saisi = trim($_POST['code_securite'] ?? '');
    $date_actuelle = date('Y-m-d H:i:s');

    if (empty($courriel_session)) {
        $_SESSION['erreur_connexion'] = "Session expirée ou invalide. Veuillez recommencer à l'Étape 1.";
        header("Location: connexion.php");
        exit();
    }

    if (!preg_match('/^[0-9]{6}$/', $code_saisi)) {
        $_SESSION['erreur_connexion'] = "Le code de sécurité doit être composé exactement de 6 chiffres.";
        header("Location: connexion.php");
        exit();
    }

    // Protection Anti-Brute Force (5 essais maximum)
    $_SESSION['essais_code_connexion'] = ($_SESSION['essais_code_connexion'] ?? 0) + 1;
    if ($_SESSION['essais_code_connexion'] > 5) {
        $clear = $bdd->prepare("UPDATE jevend_utilisateurs SET jeton_connexion = NULL, jeton_expiration = NULL WHERE courriel = ?");
        $clear->execute([$courriel_session]);
        unset($_SESSION['temp_email_connexion'], $_SESSION['essais_code_connexion']);

        $_SESSION['erreur_connexion'] = "Nombre maximal d'essais dépassé. Votre clé d'accès a été annulée par sécurité.";
        header("Location: connexion.php");
        exit();
    }

    try {
        $stmt = $bdd->prepare("SELECT * FROM jevend_utilisateurs WHERE courriel = ?");
        $stmt->execute([$courriel_session]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && !empty($user['jeton_connexion']) && $user['jeton_connexion'] === $code_saisi && $user['jeton_expiration'] >= $date_actuelle) {
            
            if ($user['statut'] === 'bloque') {
                $_SESSION['erreur_connexion'] = "Validation impossible : Ce compte est suspendu.";
                
                $clear = $bdd->prepare("UPDATE jevend_utilisateurs SET jeton_connexion = NULL, jeton_expiration = NULL WHERE id_utilisateur = ?");
                $clear->execute([$user['id_utilisateur']]);
                unset($_SESSION['temp_email_connexion']);
            } else {
                
                // Statistique d'appareil
                $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
                $type_appareil = 'ordinateur';
                $motsClesMobiles = ['Mobile', 'Android', 'Silk/', 'Kindle', 'BlackBerry', 'Opera Mini', 'Opera Mobi', 'iPhone', 'iPad'];

                foreach ($motsClesMobiles as $mot) {
                    if (stripos($userAgent, $mot) !== false) {
                        $type_appareil = 'cellulaire';
                        break;
                    }
                }

                try {
                    $stmt_stat = $bdd->prepare("INSERT INTO jevend_stats_connect (id_utilisateur, type_appareil, date_connexion) VALUES (?, ?, ?)");
                    $stmt_stat->execute([$user['id_utilisateur'], $type_appareil, $date_actuelle]);
                } catch (PDOException $e_stat) { }

                unset($_SESSION['temp_email_connexion'], $_SESSION['essais_code_connexion']);

                session_regenerate_id(true);

                // ENREGISTREMENT COMPLET DES VARIABLES DE SESSION
                $_SESSION['id_utilisateur'] = $user['id_utilisateur'];
                $_SESSION['nom']            = $user['nom'];
                $_SESSION['courriel']       = strtolower(trim($user['courriel']));
                $_SESSION['type_compte']    = $user['type_compte'] ?? 'particulier';
                $_SESSION['role']           = $user['role'] ?? 'membre';

                // GESTION SÉCURISÉE DES COOKIES SELON LE RÔLE
                if ($_SESSION['role'] === 'admin') {
                    // Supression de tout cookie d'auto-connexion existant pour l'admin
                    setcookie('jevend_remember', '', time() - 3600, '/');
                    
                    $clear_token = $bdd->prepare("UPDATE jevend_utilisateurs SET jeton_connexion = NULL, jeton_expiration = NULL WHERE id_utilisateur = ?");
                    $clear_token->execute([$user['id_utilisateur']]);
                } else {
                    // CRÉATION DU JETON 60 JOURS UNIQUEMENT POUR LES MEMBRES RÉGULIERS
                    $token_60_jours = bin2hex(random_bytes(32));
                    $expiration_60_jours = date('Y-m-d H:i:s', strtotime('+60 days'));

                    $update_token = $bdd->prepare("UPDATE jevend_utilisateurs SET jeton_connexion = ?, jeton_expiration = ? WHERE id_utilisateur = ?");
                    $update_token->execute([$token_60_jours, $expiration_60_jours, $user['id_utilisateur']]);

                    setcookie(
                        'jevend_remember',
                        $token_60_jours,
                        [
                            'expires'  => time() + (60 * 24 * 60 * 60),
                            'path'     => '/',
                            'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                            'httponly' => true,
                            'samesite' => 'Lax'
                        ]
                    );
                }

                // DÉTERMINATION DE LA DESTINATION SELON LE RÔLE
                $destination_defaut = 'espace_membre.php';

                if ($_SESSION['role'] === 'admin') {
                    $destination_defaut = 'panneau.php';
                } elseif ($_SESSION['type_compte'] === 'pro') {
                    $destination_defaut = 'espace_membre_pro.php';
                }

                $destination_finale = $_SESSION['redirect_after_login'] ?? $destination_defaut;
                unset($_SESSION['redirect_after_login']);

                header('Location: ' . $destination_finale);
                exit();
            }

        } else {
            $_SESSION['erreur_connexion'] = "Le code secret saisi est incorrect ou a expiré.";
        }
    } catch (PDOException $e) {
        $_SESSION['erreur_connexion'] = "Une erreur technique s'est produite lors de la validation de vos accès.";
    }

    header("Location: connexion.php");
    exit();
}

// Sécurité par défaut
header("Location: connexion.php");
exit();
