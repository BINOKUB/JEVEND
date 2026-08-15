<?php
// =============================================================================
// SCRIPT      : connexion.php
// REVISION    : 4.1 - Persistance mobile (localStorage) + Saisie auto du code OTP
// =============================================================================
session_start();
require_once 'config.php';

// Redirection si déjà connecté
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

$redirect_cible = $_GET['redirect'] ?? $_POST['redirect'] ?? $_SESSION['redirect_after_login'] ?? '';
if (!empty($redirect_cible)) {
    $_SESSION['redirect_after_login'] = $redirect_cible;
}

$erreur = $_SESSION['erreur_connexion'] ?? '';
$succes = $_SESSION['succes_connexion'] ?? '';
unset($_SESSION['erreur_connexion'], $_SESSION['succes_connexion']);

$email_saisi = $_SESSION['temp_email_connexion'] ?? '';
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
            
            <!-- ÉTAPE 1 : DEMANDE DE CODE -->
            <div class="form-bloc" style="flex: 1; background: #ffffff; padding: 30px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box;">
                <h3 style="margin-top:0; color: #0f172a;">1. Demander une clé d'accès</h3>
                <p style="color: #64748b; font-size: 0.85rem; line-height: 1.5;">Entrez votre adresse courriel. Si vous êtes inscrit, un code d'accès temporaire valide pendant 15 minutes vous sera instantanément envoyé.</p>
                
                <form action="connexion_execute.php" method="POST" style="margin-top: 20px;" onsubmit="sauvegarderCourrielLocal()">
                    <input type="hidden" name="action" value="demande_code">
                    
                    <div class="champ-groupe">
                        <label for="courriel_demande">Votre adresse courriel :</label>
                        <input type="email" name="courriel" id="courriel_demande" value="<?= htmlspecialchars($email_saisi) ?>" required style="width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #cbd5e1; border-radius: 4px;">
                    </div>
                    
                    <button type="submit" class="btn-connexion-etape1" style="margin-top: 15px; width: 100%; padding: 12px; background-color: #0f172a; color: #ffffff; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">
                        ✉️ Recevoir mon code unique
                    </button>
                </form>
            </div>

            <!-- ÉTAPE 2 : VALIDATION DU CODE -->
            <div class="form-bloc" style="flex: 1; background: #ffffff; padding: 30px; border: 1px solid #e2e8f0; border-radius: 8px; border-top: 4px solid #2563eb; box-sizing: border-box;">
                <h3 style="margin-top:0; color: #2563eb;">2. Saisir le code de sécurité</h3>
                
                <p style="color: #15803d; font-size: 0.95rem; font-weight: bold; line-height: 1.5; background-color: #f0fdf4; padding: 10px; border-radius: 6px; border: 1px solid #bbf7d0;">
                    👉 Recopiez les 6 chiffres reçus dans votre boîte de réception pour valider votre identité.
                </p>
                
                <form action="connexion_execute.php" method="POST" style="margin-top: 20px;">
                    <input type="hidden" name="action" value="valider_code">
                    
                    <div class="champ-groupe">
                        <label style="font-weight: bold; color: #0f172a;">Courriel de validation actif :</label>
                        <div id="affichage_courriel_actif" style="background-color: #f8fafc; padding: 10px; border-radius: 4px; border: 1px solid #e2e8f0; font-family: monospace; font-size: 0.9rem; color: #334155; overflow-x: auto;">
                            <?= !empty($email_saisi) ? htmlspecialchars($email_saisi) : '<i>Aucun courriel spécifié pour le moment</i>' ?>
                        </div>
                    </div>

                    <div class="champ-groupe" style="margin-top: 15px;">
                        <label for="code_securite" style="color: #2563eb; font-weight: bold;">Code secret à 6 chiffres :</label>
                        <!-- ATTRIBUTS MOBILIERS AMÉLIORÉS (one-time-code / numeric) -->
                        <input type="text" name="code_securite" id="code_securite" inputmode="numeric" pattern="[0-9]*" maxlength="6" placeholder="Ex: 584920" required autocomplete="one-time-code" <?= empty($email_saisi) ? 'disabled' : '' ?> style="width: 100%; padding: 12px; font-size: 1.2rem; font-weight: bold; text-align: center; letter-spacing: 4px; box-sizing: border-box; border: 2px solid #2563eb; border-radius: 4px; color: #2563eb; background-color: #f8fafc;">
                    </div>
                    
                    <button type="submit" id="btnConfirm" class="btn-connexion-etape2" <?= empty($email_saisi) ? 'disabled' : '' ?> style="margin-top: 15px; width: 100%; padding: 12px; background-color: <?= empty($email_saisi) ? '#cbd5e1' : '#2563eb' ?>; color: #ffffff; border: none; border-radius: 4px; font-weight: bold; cursor: <?= empty($email_saisi) ? 'not-allowed' : 'pointer' ?>;">
                        🔑 Confirmer et entrer
                    </button>
                </form>
            </div>

        </div>

        <div style="text-align: center; margin-top: 30px; font-size: 0.9rem; color: #64748b; padding-bottom: 20px;">
            Pas encore de compte ? <a href="inscription.php" style="color: #2563eb; font-weight: bold; text-decoration: none;">Créer mon compte gratuit en 10 secondes</a>
        </div>
    </div>

    <script>
    // 1. Sauvegarde du courriel dans la mémoire locale du téléphone lors de l'envoi
    function sauvegarderCourrielLocal() {
        const email = document.getElementById('courriel_demande').value.trim();
        if (email) {
            localStorage.setItem('jevend_courriel_attente', email);
        }
    }

    // 2. Restauration automatique si le navigateur mobile se rafraîchit en revenant
    window.addEventListener('DOMContentLoaded', () => {
        const inputCode = document.getElementById('code_securite');
        const btnConfirm = document.getElementById('btnConfirm');
        const affichageEmail = document.getElementById('affichage_courriel_actif');
        const courrielSauvegarde = localStorage.getItem('jevend_courriel_attente');

        // Si le serveur n'a pas transmis le courriel en PHP mais qu'il est en mémoire locale
        if (courrielSauvegarde && (!affichageEmail.innerText || affichageEmail.innerText.includes('Aucun courriel'))) {
            affichageEmail.innerText = courrielSauvegarde;
            inputCode.disabled = false;
            btnConfirm.disabled = false;
            btnConfirm.style.backgroundColor = '#2563eb';
            btnConfirm.style.cursor = 'pointer';
        }
    });
    </script>
</body>
</html>
