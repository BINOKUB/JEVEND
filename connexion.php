<?php
// =============================================================================
// SCRIPT : connexion.php
// REVISION : 3.1 - Redirection dynamique basée sur le champ `role` de la BDD
// NOM DU SCRIPT : connexion.php
// =============================================================================
session_start();
require_once 'config.php';
date_default_timezone_set('America/Montreal');

// Capturation et mémorisation de l'URL de retour souhaitée[cite: 6]
$redirect_cible = $_GET['redirect'] ?? $_POST['redirect'] ?? $_SESSION['redirect_after_login'] ?? '';
if (!empty($redirect_cible)) {
    $_SESSION['redirect_after_login'] = $redirect_cible;
}

// Si l'utilisateur est déjà connecté, redirection dynamique basée sur son rôle[cite: 6]
if (isset($_SESSION['id_utilisateur'])) {
    $destination = $_SESSION['redirect_after_login'] ?? null;
    unset($_SESSION['redirect_after_login']);

    if (!empty($destination)) {
        header('Location: ' . $destination);
    } elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header('Location: panneau.php');
    } elseif (isset($_SESSION['type_compte']) && $_SESSION['type_compte'] === 'pro') {
        header('Location: espace_membre_pro.php');
    } else {
        header('Location: espace_membre.php');
    }
    exit();
}

$erreur = "";
$succes = "";

// Récupération automatique du courriel si on vient tout juste de s'inscrire[cite: 6]
$email_saisi = $_SESSION['temp_email_connexion'] ?? '';

// ÉTAPE 1 : DEMANDE DE LIEN / GÉNÉRATION DU JETON[cite: 6]
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_demande'])) {
    $courriel = trim($_POST['courriel'] ?? '');

    if (empty($courriel) || !filter_var($courriel, FILTER_VALIDATE_EMAIL)) {
        $erreur = "Veuillez entrer une adresse courriel valide.";
    } else {
        try {
            $stmt = $bdd->prepare("SELECT id_utilisateur, nom, statut FROM jevend_utilisateurs WHERE courriel = ?");
            $stmt->execute([$courriel]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                if ($user['statut'] === 'bloque') {
                    $erreur = "L'accès à ce compte a été suspendu par l'administration.";
                } else {
                    $code_securite = rand(100000, 999999);
                    $expiration = date('Y-m-d H:i:s', strtotime('+15 minutes'));

                    $update = $bdd->prepare("UPDATE jevend_utilisateurs SET jeton_connexion = ?, jeton_expiration = ? WHERE id_utilisateur = ?");
                    $update->execute([$code_securite, $expiration, $user['id_utilisateur']]);

                    $_SESSION['temp_email_connexion'] = $courriel;
                    $email_saisi = $courriel;

                    echo "<script>console.log('%c[TEST JETON] Courriel: " . addslashes($courriel) . " | CODE SECRET: " . $code_securite . "', 'background: #16a34a; color: #ffffff; font-size: 14px; padding: 8px; font-weight: bold; border-radius: 4px;');</script>";
                    
                    $headers = "MIME-Version: 1.0\r\n";
                    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                    $headers .= "From: jevend.com <no-reply@jevend.com>\r\n";
                    $headers .= "Reply-To: no-reply@jevend.com\r\n";
                    $headers .= "X-Mailer: PHP/" . phpversion();

                    $sujet = "Votre code de connexion unique - jevend.com";

                    $message = "
                    <!DOCTYPE html>
                    <html lang='fr'>
                    <head><meta charset='UTF-8'></head>
                    <body style='background-color: #f1f5f9; font-family: Arial, sans-serif; padding: 20px;'>
                        <div style='max-width: 500px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center;'>
                            <h2>Bonjour " . htmlspecialchars($user['nom']) . ",</h2>
                            <p>Voici votre code unique pour accéder à votre espace :</p>
                            <div style='font-size: 2rem; font-weight: bold; color: #2563eb; letter-spacing: 5px; margin: 20px 0; padding: 10px; background: #f8fafc; border: 2px dashed #2563eb;'>
                                " . $code_securite . "
                            </div>
                            <p style='font-size: 0.8rem; color: #94a3b8;'>Valide pendant 15 minutes.</p>
                        </div>
                    </body>
                    </html>";

                    @mail($courriel, $sujet, $message, $headers);

                    $succes = "Un code secret d'accès unique a été généré. Consultez votre boîte de réception.";
                }
            } else {
                $erreur = "Aucun compte n'est configuré avec cette adresse courriel. Veuillez d'abord vous inscrire.";
            }
        } catch (PDOException $e) {
            $erreur = "Une erreur technique est survenue lors de la communication avec la base de données.";
        }
    }
}

// ÉTAPE 2 : VALIDATION DU CODE DE SÉCURITÉ ET CHARGEMENT DU RÔLE DEPUIS LA BDD[cite: 6]
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_validation'])) {
    $courriel_session = $_SESSION['temp_email_connexion'] ?? '';
    $code_saisi = trim($_POST['code_securite'] ?? '');
    $date_actuelle = date('Y-m-d H:i:s');

    if (empty($courriel_session)) {
        $erreur = "Session expirée ou invalide. Veuillez recommencer à l'Étape 1.";
    } elseif (empty($code_saisi) || strlen($code_saisi) !== 6) {
        $erreur = "Le code de sécurité doit être composé de 6 chiffres.";
    } else {
        try {
            $stmt = $bdd->prepare("SELECT * FROM jevend_utilisateurs WHERE courriel = ?");
            $stmt->execute([$courriel_session]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && $user['jeton_connexion'] === $code_saisi && $user['jeton_expiration'] >= $date_actuelle) {
                
                if ($user['statut'] === 'bloque') {
                    $erreur = "Validation impossible : Ce compte est suspendu.";
                    
                    $clear = $bdd->prepare("UPDATE jevend_utilisateurs SET jeton_connexion = NULL, jeton_expiration = NULL WHERE id_utilisateur = ?");
                    $clear->execute([$user['id_utilisateur']]);
                    unset($_SESSION['temp_email_connexion']);
                } else {
                    
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

                    $clear = $bdd->prepare("UPDATE jevend_utilisateurs SET jeton_connexion = NULL, jeton_expiration = NULL WHERE id_utilisateur = ?");
                    $clear->execute([$user['id_utilisateur']]);

                    unset($_SESSION['temp_email_connexion']);

                    // ENREGISTREMENT DE LA SESSION DEPUIS LA BDD
                    $_SESSION['id_utilisateur'] = $user['id_utilisateur'];
                    $_SESSION['nom']            = $user['nom'];
                    $_SESSION['courriel']       = $user['courriel'];
                    $_SESSION['type_compte']    = $user['type_compte'] ?? 'particulier';
                    $_SESSION['role']           = $user['role'] ?? 'membre'; // <-- Stockage dynamique du rôle BDD

                    // DÉTERMINATION DE LA DESTINATION SELON LE RÔLE DE LA BDD
                    $destination_defaut = 'espace_membre.php';

                    if ($_SESSION['role'] === 'admin') {
                        $destination_defaut = 'panneau.php';
                    } elseif ($_SESSION['type_compte'] === 'pro') {
                        $destination_defaut = 'espace_membre_pro.php';
                    }

                    // REDIRECTION : Priorité au retour à la page visitée avant la connexion, sinon défaut[cite: 6]
                    $destination_finale = $_SESSION['redirect_after_login'] ?? $destination_defaut;
                    unset($_SESSION['redirect_after_login']);

                    header('Location: ' . $destination_finale);
                    exit();
                }

            } else {
                $erreur = "Le code secret saisi est incorrect ou a expiré (Délai maximal de 15 minutes dépassé).";
            }
        } catch (PDOException $e) {
            $erreur = "Une erreur technique s'est produite lors de la validation de vos accès.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Connexion sans mot de passe — jevend.com</title>
    <link rel="stylesheet" href="style.css">
    <style>
        html, body { max-width: 100% !important; overflow-x: hidden !important; width: 100% !important; margin: 0; padding: 0; box-sizing: border-box; }
        .connexion-flex-wrapper { display: flex; gap: 30px; width: 100%; box-sizing: border-box; }
        
        .btn-connexion-etape1 {
            width: 100%;
            margin-top: 15px;
            background-color: #1e293b !important;
            color: #ffffff !important;
            border: none !important;
            padding: 12px !important;
            border-radius: 6px !important;
            font-weight: bold !important;
            font-size: 0.95rem !important;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        .btn-connexion-etape1:hover { background-color: #0f172a !important; }

        .btn-connexion-etape2 {
            width: 100%;
            margin-top: 15px;
            background-color: #2563eb !important;
            color: #ffffff !important;
            border: none !important;
            padding: 12px !important;
            border-radius: 6px !important;
            font-weight: bold !important;
            font-size: 0.95rem !important;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        .btn-connexion-etape2:hover { background-color: #1d4ed8 !important; }
        .btn-connexion-etape2:disabled { opacity: 0.5 !important; cursor: not-allowed !important; }

        @media (max-width: 768px) {
            .admin-conteneur { max-width: 100% !important; width: 100% !important; padding-left: 15px !important; padding-right: 15px !important; margin-top: 20px !important; box-sizing: border-box !important; }
            .connexion-flex-wrapper { flex-direction: column !important; gap: 20px !important; }
            .form-bloc { width: 100% !important; min-width: 100% !important; box-sizing: border-box !important; padding: 20px !important; }
        }
    </style>
</head>
<body class="admin-body">

    <?php include 'partials/_nav_publique.php'; ?>

    <div class="admin-conteneur" style="max-width: 900px; margin-top: 40px; margin-left: auto; margin-right: auto; box-sizing: border-box;">
        
        <?php if (!empty($erreur)): ?>
            <div class="erreur-msg" style="background-color: #fef2f2; color: #991b1b; padding: 12px; border-radius: 4px; font-size: 0.85rem; margin-bottom: 20px; border: 1px solid #fecaca; font-weight: bold; text-align: center; box-sizing: border-box;">
                ⚠️ <?= htmlspecialchars($erreur) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($succes)): ?>
            <div class="succes-msg" style="background-color: #f0fdf4; color: #166534; padding: 12px; border-radius: 4px; font-size: 0.85rem; margin-bottom: 20px; border: 1px solid #bbf7d0; font-weight: bold; text-align: center; box-sizing: border-box;">
                🚀 <?= htmlspecialchars($succes) ?>
            </div>
        <?php endif; ?>

        <div class="connexion-flex-wrapper">
            
            <div class="form-bloc" style="flex: 1; background: #ffffff; padding: 30px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box;">
                <h3 style="margin-top:0; color: #0f172a;">1. Demander une clé d'accès</h3>
                <p style="color: #64748b; font-size: 0.85rem; line-height: 1.5;">Entrez votre adresse courriel. Si vous êtes inscrit, un code d'accès temporaire valide pendant 15 minutes vous sera instantanément envoyé.</p>
                
                <form action="connexion.php" method="POST" style="margin-top: 20px;">
                    <input type="hidden" name="action_demande" value="1">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SESSION['redirect_after_login'] ?? '') ?>">
                    
                    <div class="champ-groupe">
                        <label for="courriel_demande">Votre adresse courriel :</label>
                        <input type="email" name="courriel" id="courriel_demande" value="<?= htmlspecialchars($email_saisi) ?>" required style="width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #cbd5e1; border-radius: 4px;">
                    </div>
                    
                    <button type="submit" class="btn-connexion-etape1">
                        ✉️ Recevoir mon code unique
                    </button>
                </form>
            </div>

            <div class="form-bloc" style="flex: 1; background: #ffffff; padding: 30px; border: 1px solid #e2e8f0; border-radius: 8px; border-top: 4px solid #2563eb; box-sizing: border-box;">
                <h3 style="margin-top:0; color: #2563eb;">2. Saisir le code de sécurité</h3>
                
                <p style="color: #15803d; font-size: 0.95rem; font-weight: bold; line-height: 1.5; background-color: #f0fdf4; padding: 10px; border-radius: 6px; border: 1px solid #bbf7d0;">
                    👉 Recopiez les 6 chiffres reçus dans votre boîte de réception pour valider votre identité.
                </p>
                
                <form action="connexion.php" method="POST" style="margin-top: 20px;">
                    <input type="hidden" name="action_validation" value="1">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SESSION['redirect_after_login'] ?? '') ?>">
                    
                    <div class="champ-groupe">
                        <label style="font-weight: bold; color: #0f172a;">Courriel de validation actif :</label>
                        <div style="background-color: #f8fafc; padding: 10px; border-radius: 4px; border: 1px solid #e2e8f0; font-family: monospace; font-size: 0.9rem; color: #334155; overflow-x: auto;">
                            <?= !empty($email_saisi) ? htmlspecialchars($email_saisi) : '<i>Aucun courriel spécifié pour le moment</i>' ?>
                        </div>
                    </div>

                    <div class="champ-groupe" style="margin-top: 15px;">
                        <label for="code_securite" style="color: #2563eb; font-weight: bold;">Code secret à 6 chiffres :</label>
                        <input type="text" name="code_securite" id="code_securite" maxlength="6" placeholder="Ex: 584920" required autocomplete="off" style="width: 100%; padding: 12px; font-size: 1.2rem; font-weight: bold; text-align: center; letter-spacing: 4px; box-sizing: border-box; border: 2px solid #2563eb; border-radius: 4px; color: #2563eb; background-color: #f8fafc;">
                    </div>
                    
                    <button type="submit" class="btn-connexion-etape2" <?= empty($email_saisi) ? 'disabled' : '' ?>>
                        🔑 Confirmer et entrer
                    </button>
                </form>
            </div>

        </div>

        <div style="text-align: center; margin-top: 30px; font-size: 0.9rem; color: #64748b; padding-bottom: 20px;">
            Pas encore de compte ? <a href="inscription.php" style="color: #2563eb; font-weight: bold; text-decoration: none;">Créer mon compte gratuit en 10 secondes</a>
        </div>
    </div>

</body>
</html>
