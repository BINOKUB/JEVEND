<?php
// =============================================================================
// NOM DU SCRIPT : connexion_execute.php
// REVISION     : 1.1 - Correction des variables de template courriel ($code_securite & $nom_affiche)
// =============================================================================
session_start();
require_once 'config.php';
date_default_timezone_set('America/Montreal');

// Redirection si déjà connecté
if (isset($_SESSION['id_utilisateur'])) {
    header("Location: connexion.php");
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
                // Code secret à 6 chiffres
                $code_securite = (string)rand(100000, 999999);
                $expiration = date('Y-m-d H:i:s', strtotime('+15 minutes'));

                $update = $bdd->prepare("UPDATE jevend_utilisateurs SET jeton_connexion = ?, jeton_expiration = ? WHERE id_utilisateur = ?");
                $update->execute([$code_securite, $expiration, $user['id_utilisateur']]);

                $_SESSION['temp_email_connexion'] = $courriel;
                $_SESSION['dernier_envoi_code'] = time();
                $_SESSION['essais_code_connexion'] = 0; // Réinitialisation du compteur d'essais

                // Console Log pour environnement de dev/test
                echo "<script>console.log('%c[TEST JETON] Courriel: " . addslashes($courriel) . " | CODE SECRET: " . $code_securite . "', 'background: #16a34a; color: #ffffff; font-size: 14px; padding: 8px; font-weight: bold; border-radius: 4px;');</script>";

                $headers  = "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                $headers .= "From: jevend.com <no-reply@jevend.com>\r\n";
                $headers .= "Reply-To: no-reply@jevend.com\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion();

                $sujet = "Votre code de connexion unique - jevend.com";
                $nom_affiche = htmlspecialchars($user['nom'] ?? 'Membre');

                $message = "
<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
</head>
<body style='margin: 0; padding: 0; background-color: #f1f5f9; font-family: Arial, sans-serif;'>
    <table width='100%' border='0' cellspacing='0' cellpadding='0' style='background-color: #f1f5f9; padding: 40px 10px;'>
        <tr>
            <td align='center'>
                <table width='100%' style='max-width: 550px; background-color: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0;' border='0' cellspacing='0' cellpadding='0'>
                    <!-- EN-TÊTE SANS IMAGE BRISÉE -->
                    <tr>
                        <td align='center' style='background-color: #0f172a; padding: 25px;'>
                            <h1 style='margin: 0; color: #ffffff; font-size: 1.8rem; font-style: italic; letter-spacing: -1px;'>jevend.com</h1>
                        </td>
                    </tr>
                    <!-- CORPS DU MESSAGE -->
                    <tr>
                        <td style='padding: 40px 30px; color: #334155; text-align: center;'>
                            <h2 style='margin: 0 0 15px 0; font-size: 1.4rem; color: #0f172a;'>
                                Bonjour " . $nom_affiche . " !
                            </h2>
                            <p style='margin: 0 0 25px 0; font-size: 0.95rem; color: #475569;'>
                                Voici votre code unique pour accéder à votre espace :
                            </p>
                            <!-- PAVE CODE SECRET MODERNE -->
                            <div style='text-align: center; margin-bottom: 25px;'>
                                <div style='background-color: #2563eb; color: #ffffff; font-size: 2.2rem; font-weight: bold; letter-spacing: 8px; padding: 15px 25px; display: inline-block; border-radius: 8px; font-family: Arial, sans-serif; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);'>
                                    " . $code_securite . "
                                </div>
                            </div>
                            <p style='margin: 0; font-size: 0.8rem; color: #94a3b8;'>
                                Ce code secret est valide pendant 15 minutes.
                            </p>
                        </td>
                    </tr>
                    <!-- PIED DE PAGE -->
                    <tr>
                        <td align='center' style='background-color: #f8fafc; padding: 15px; border-top: 1px solid #e2e8f0; font-size: 0.75rem; color: #94a3b8;'>
                            « Premier arrivé, premier vendu » — jevend.com
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>";

                @mail($courriel, $sujet, $message, $headers);

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

    // Gestion de la protection Anti-Brute Force (5 essais maximum)
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

                // Nettoyage du jeton utilisé
                $clear = $bdd->prepare("UPDATE jevend_utilisateurs SET jeton_connexion = NULL, jeton_expiration = NULL WHERE id_utilisateur = ?");
                $clear->execute([$user['id_utilisateur']]);

                unset($_SESSION['temp_email_connexion'], $_SESSION['essais_code_connexion']);

                // SECURISATION SESSION : Régénération de l'ID pour prévenir la fixation de session
                session_regenerate_id(true);

                // ENREGISTREMENT DE LA SESSION MEMBRE
                $_SESSION['id_utilisateur'] = $user['id_utilisateur'];
                $_SESSION['nom']            = $user['nom'];
                $_SESSION['courriel']       = $user['courriel'];
                $_SESSION['type_compte']    = $user['type_compte'] ?? 'particulier';
                $_SESSION['role']           = $user['role'] ?? 'membre';

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
            $_SESSION['erreur_connexion'] = "Le code secret saisi est incorrect ou a expiré (Délai maximal de 15 minutes dépassé).";
        }
    } catch (PDOException $e) {
        $_SESSION['erreur_connexion'] = "Une erreur technique s'est produite lors de la validation de vos accès.";
    }

    header("Location: connexion.php");
    exit();
}

// Sécurité : redirection par défaut si accès direct à connexion_execute.php
header("Location: connexion.php");
exit();
