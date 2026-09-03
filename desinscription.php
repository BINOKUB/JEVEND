<?php
// =============================================================================
// NOM DU SCRIPT : desinscription.php
// REVISION     : 1.4 - Purge Fichiers Physique (uploads/) + Purge BDD Séquentielle
// SCRIPT COMPLET ET SUIVI
// =============================================================================
session_start();
require_once 'config.php';

// Redirection si le visiteur n'est pas connecté
if (empty($_SESSION['id_utilisateur'])) {
    header("Location: connexion.php");
    exit();
}

$id_user = (int)$_SESSION['id_utilisateur'];
$msg_erreur = "";
$msg_succes = "";
$code_envoye = $_SESSION['desinscr_code_sent'] ?? false;

// -----------------------------------------------------------------------------
// 1. CHARGEMENT DES INFOS DU MEMBRE & COMPTAGE DES DONNÉES À PURGER
// -----------------------------------------------------------------------------
$user_info = null;
$stats_purge = [
    'annonces'      => 0,
    'bannieres'     => 0,
    'listes_envie'  => 0,
    'recherches'    => 0,
    'chats'         => 0
];

if (isset($bdd)) {
    try {
        // Infos membre
        $stmt_u = $bdd->prepare("SELECT nom, courriel FROM jevend_utilisateurs WHERE id_utilisateur = ?");
        $stmt_u->execute([$id_user]);
        $user_info = $stmt_u->fetch(PDO::FETCH_ASSOC);

        // Comptage Annonces
        $stmt_c = $bdd->prepare("SELECT COUNT(*) FROM jevend_annonces WHERE id_utilisateur = ?");
        $stmt_c->execute([$id_user]);
        $stats_purge['annonces'] = (int)$stmt_c->fetchColumn();

        // Comptage Bannières
        $stmt_c = $bdd->prepare("SELECT COUNT(*) FROM jevend_bannieres_actives WHERE id_utilisateur = ?");
        $stmt_c->execute([$id_user]);
        $stats_purge['bannieres'] = (int)$stmt_c->fetchColumn();

        // Comptage Listes d'envie
        $stmt_c = $bdd->prepare("SELECT COUNT(*) FROM jevend_listes_envie WHERE id_utilisateur = ?");
        $stmt_c->execute([$id_user]);
        $stats_purge['listes_envie'] = (int)$stmt_c->fetchColumn();

        // Comptage Recherches
        $stmt_c = $bdd->prepare("SELECT COUNT(*) FROM jevend_recherches WHERE id_utilisateur = ?");
        $stmt_c->execute([$id_user]);
        $stats_purge['recherches'] = (int)$stmt_c->fetchColumn();

        // Comptage Chats (Expediteur + Destinataire)
        $stmt_c = $bdd->prepare("SELECT COUNT(*) FROM jevend_chat WHERE id_expediteur = ? OR id_destinataire = ?");
        $stmt_c->execute([$id_user, $id_user]);
        $stats_purge['chats'] = (int)$stmt_c->fetchColumn();

    } catch (PDOException $e) {
        $msg_erreur = "Erreur de chargement des données : " . htmlspecialchars($e->getMessage());
    }
}

// -----------------------------------------------------------------------------
// 2. ÉTAPE 1 : DEMANDE DU CODE DE DÉSINSCRIPTION (POST)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_demander_code'])) {
    $case_cochee = isset($_POST['confirmer_desabonnement']);
    $motif_select = htmlspecialchars(trim($_POST['motif_desinteret'] ?? 'Non précisé'), ENT_QUOTES, 'UTF-8');

    if (!$case_cochee) {
        $msg_erreur = "Vous devez impérativement cocher la case 'Oui, je veux désabonner mon compte définitivement' pour recevoir le code.";
    } else {
        // Enregistrement du motif dans jevend_desinscription
        if (isset($bdd)) {
            try {
                $stmt_log = $bdd->prepare("INSERT INTO jevend_desinscription (nom, courriel, motif_desinteret) VALUES (?, ?, ?)");
                $stmt_log->execute([
                    $user_info['nom'] ?? 'Membre',
                    $user_info['courriel'] ?? '',
                    $motif_select
                ]);
            } catch (PDOException $e) { }
        }

        // Génération du code OTP à 6 chiffres
        $code_otp = rand(100000, 999999);
        $_SESSION['desinscr_otp_code'] = $code_otp;
        $_SESSION['desinscr_code_sent'] = true;
        $code_envoye = true;

        // Envoi du courriel
        $destinataire = $user_info['courriel'];
        $sujet = "=?UTF-8?B?" . base64_encode("Code de confirmation de désinscription — jevend.com") . "?=";
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: jevend.com <no-reply@jevend.com>\r\n";

        $corps_mail = "
        <div style='font-family: Arial, sans-serif; padding: 20px; background-color: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px;'>
            <h2 style='color: #dc2626;'>Code de désinscription</h2>
            <p>Bonjour <strong>".htmlspecialchars($user_info['nom'])."</strong>,</p>
            <p>Vous avez demandé la suppression définitive de votre compte sur <strong>jevend.com</strong>.</p>
            <p>Voici votre code de sécurité à saisir sur la page :</p>
            <div style='background: #0f172a; color: #ffffff; font-size: 1.8rem; font-weight: bold; letter-spacing: 5px; text-align: center; padding: 15px; border-radius: 6px; margin: 20px 0;'>
                $code_otp
            </div>
            <p style='color: #64748b; font-size: 0.85rem;'>Si vous n'êtes pas à l'origine de cette demande, ignorez ce message. Votre compte restera actif.</p>
        </div>
        ";

        @mail($destinataire, $sujet, $corps_mail, $headers);
        $msg_succes = "Un code de validation à 6 chiffres a été envoyé à votre adresse courriel : " . htmlspecialchars($destinataire);
    }
}

// -----------------------------------------------------------------------------
// 3. ÉTAPE 2 : VALIDATION DU CODE & PURGE DISQUE ET BDD
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_valider_desinscription'])) {
    $code_saisi = trim($_POST['code_verification'] ?? '');
    $code_attendu = $_SESSION['desinscr_otp_code'] ?? null;
    $case_cochee_finale = isset($_POST['confirmer_desabonnement']);

    if (!$case_cochee_finale) {
        $msg_erreur = "Action annulée : La case de confirmation 'Oui, je veux désabonner mon compte' doit rester cochée.";
    } elseif (empty($code_saisi) || (int)$code_saisi !== (int)$code_attendu) {
        $msg_erreur = "Le code de vérification est incorrect. Veuillez vérifier vos courriels.";
    } else {
        if (isset($bdd)) {
            try {
                $bdd->beginTransaction();

                // DOSSIER CIBLE DES IMAGES SUR LE DISQUE
                $dossier_uploads = __DIR__ . '/uploads/';

                // A. RÉCUPÉRATION ET EFFACEMENT PHYSIQUE DES IMAGES D'ANNONCES
                $stmt_get_img = $bdd->prepare("
                    SELECT nom_fichier 
                    FROM jevend_annonces_images 
                    WHERE id_annonces IN (SELECT id_annonces FROM jevend_annonces WHERE id_utilisateur = ?)
                ");
                $stmt_get_img->execute([$id_user]);
                $list_images_annonces = $stmt_get_img->fetchAll(PDO::FETCH_COLUMN);

                foreach ($list_images_annonces as $fichier) {
                    if (!empty($fichier)) {
                        $fichier_path = $dossier_uploads . $fichier;
                        if (file_exists($fichier_path)) {
                            @unlink($fichier_path); // Destruction physique sur le disque
                        }
                    }
                }

                // B. RÉCUPÉRATION ET EFFACEMENT PHYSIQUE DES IMAGES DE RECHERCHES
                $stmt_get_rec = $bdd->prepare("SELECT image_reference FROM jevend_recherches WHERE id_utilisateur = ?");
                $stmt_get_rec->execute([$id_user]);
                $list_images_recherches = $stmt_get_rec->fetchAll(PDO::FETCH_COLUMN);

                foreach ($list_images_recherches as $fichier_rec) {
                    if (!empty($fichier_rec)) {
                        $fichier_rec_path = $dossier_uploads . $fichier_rec;
                        if (file_exists($fichier_rec_path)) {
                            @unlink($fichier_rec_path);
                        }
                    }
                }

                // C. EFFACEMENT DES LIGNES BDD EN SÉQUENCE STRICTE
                // 1. Table images d'annonces
                $stmt_img = $bdd->prepare("DELETE FROM jevend_annonces_images WHERE id_annonces IN (SELECT id_annonces FROM jevend_annonces WHERE id_utilisateur = ?)");
                $stmt_img->execute([$id_user]);

                // 2. Bannières actives
                $stmt_ban = $bdd->prepare("DELETE FROM jevend_bannieres_actives WHERE id_utilisateur = ?");
                $stmt_ban->execute([$id_user]);

                // 3. Listes d'envies
                $stmt_env = $bdd->prepare("DELETE FROM jevend_listes_envie WHERE id_utilisateur = ?");
                $stmt_env->execute([$id_user]);

                // 4. Recherches
                $stmt_rec = $bdd->prepare("DELETE FROM jevend_recherches WHERE id_utilisateur = ?");
                $stmt_rec->execute([$id_user]);

                // 5. Chats & Tickets live
                $stmt_chat = $bdd->prepare("DELETE FROM jevend_chat WHERE id_expediteur = ? OR id_destinataire = ?");
                $stmt_chat->execute([$id_user, $id_user]);

                $stmt_tick = $bdd->prepare("DELETE FROM jevend_chat_tickets_live WHERE id_membre = ?");
                $stmt_tick->execute([$id_user]);

                // 6. Annonces
                $stmt_ann = $bdd->prepare("DELETE FROM jevend_annonces WHERE id_utilisateur = ?");
                $stmt_ann->execute([$id_user]);

                // 7. Compte membre en dernier
                $stmt_usr = $bdd->prepare("DELETE FROM jevend_utilisateurs WHERE id_utilisateur = ?");
                $stmt_usr->execute([$id_user]);

                $bdd->commit();

                // Destruction de la session et redirection
                unset($_SESSION['desinscr_otp_code']);
                unset($_SESSION['desinscr_code_sent']);
                session_destroy();

                header("Location: index.php?compte_supprime=1");
                exit();

            } catch (PDOException $e) {
                $bdd->rollBack();
                $msg_erreur = "Erreur BDD lors de la suppression : " . htmlspecialchars($e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Désinscription — jevend.com</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { 
            margin: 0; 
            padding: 0; 
            background-color: #f8fafc; 
            font-family: system-ui, -apple-system, sans-serif;
            display: block !important; 
            width: 100% !important;
        }

        .nav-membre-responsive {
            width: 100% !important;
            box-sizing: border-box !important;
        }

        .des-page-wrapper {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 40px 15px;
            box-sizing: border-box;
        }

        .des-card { 
            background: #ffffff; 
            border: 1px solid #cbd5e1; 
            border-radius: 8px; 
            padding: 30px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.05); 
            width: 100%;
            max-width: 650px;
            box-sizing: border-box;
        }

        .des-titre { margin-top: 0; color: #991b1b; font-size: 1.4rem; text-align: center; border-bottom: 2px solid #fee2e2; padding-bottom: 12px; }
        
        .box-warning { background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 15px; margin: 20px 0; color: #991b1b; font-size: 0.9rem; }
        .box-warning ul { margin: 8px 0 0 20px; padding: 0; }
        .box-warning li { margin-bottom: 4px; font-weight: bold; }

        .des-group { margin-bottom: 20px; }
        .des-group label { display: block; font-weight: bold; color: #334155; font-size: 0.9rem; margin-bottom: 6px; }
        .des-group select, .des-group input[type="text"] { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; box-sizing: border-box; }

        .btn-demander-code { width: 100%; background: #dc2626; color: #ffffff; border: none; padding: 12px; font-size: 1rem; font-weight: bold; border-radius: 6px; cursor: pointer; transition: background 0.2s; }
        .btn-demander-code:hover { background: #b91c1c; }

        .btn-valider-final { width: 100%; background: #991b1b; color: #ffffff; border: none; padding: 14px; font-size: 1.05rem; font-weight: bold; border-radius: 6px; cursor: pointer; }
        .btn-valider-final:hover { background: #7f1d1d; }

        .msg-err { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; text-align: center; border: 1px solid #fecaca; }
        .msg-ok { background: #dcfce7; color: #166534; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; text-align: center; border: 1px solid #bbf7d0; }
        
        .code-box { background: #f8fafc; border: 2px dashed #cbd5e1; padding: 20px; border-radius: 8px; margin-top: 25px; text-align: center; }
        .input-code-otp { width: 200px !important; text-align: center; font-size: 1.5rem !important; letter-spacing: 4px; font-weight: bold; margin: 10px auto; }
    </style>
</head>
<body>

    <?php include 'partials/_nav_membre.php'; ?>

    <div class="des-page-wrapper">
        <div class="des-card">
            <h2 class="des-titre">⚠️ Demande de désinscription définitive</h2>

            <?php if (!empty($msg_erreur)): ?>
                <div class="msg-err"><?= $msg_erreur ?></div>
            <?php endif; ?>

            <?php if (!empty($msg_succes)): ?>
                <div class="msg-ok"><?= $msg_succes ?></div>
            <?php endif; ?>

            <div class="box-warning">
                <strong>Attention : Cette action est irréversible !</strong><br>
                En fermant votre compte, l'intégralité de vos données ci-dessous sera définitivement effacée de nos serveurs :
                <ul>
                    <li><?= $stats_purge['annonces'] ?> Annonce(s) publiée(s)</li>
                    <li><?= $stats_purge['bannieres'] ?> Bannière(s) active(s)</li>
                    <li><?= $stats_purge['listes_envie'] ?> Élément(s) dans vos listes d'envies</li>
                    <li><?= $stats_purge['recherches'] ?> Recherche(s) enregistrée(s)</li>
                    <li><?= $stats_purge['chats'] ?> Conversation(s) de messagerie</li>
                </ul>
            </div>

            <form method="POST" action="desinscription.php">
                
                <div class="des-group">
                    <label for="motif_desinteret">Motif de votre départ (optionnel) :</label>
                    <select name="motif_desinteret" id="motif_desinteret" <?= $code_envoye ? 'disabled' : '' ?>>
                        <option value="Non précisé">-- Choisissez un motif (optionnel) --</option>
                        <option value="J'ai vendu tous mes objets">J'ai vendu tous mes objets</option>
                        <option value="Je n'utilise plus le service">Je n'utilise plus le service</option>
                        <option value="Je trouve le site trop difficile à utiliser">Je trouve le site trop difficile à utiliser</option>
                        <option value="Inquiet pour mes données personnelles">Inquiet pour mes données personnelles</option>
                        <option value="Autre raison">Autre raison</option>
                    </select>
                </div>

                <div class="des-group" style="background:#f8fafc; padding:12px; border-radius:6px; border:1px solid #e2e8f0;">
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer; margin:0;">
                        <input type="checkbox" name="confirmer_desabonnement" value="1" required <?= $code_envoye ? 'checked' : '' ?> style="width:20px; height:20px; accent-color:#dc2626;">
                        <span style="color:#0f172a; font-weight:bold;">Oui, je veux désabonner mon compte définitivement</span>
                    </label>
                </div>

                <?php if (!$code_envoye): ?>
                    <button type="submit" name="action_demander_code" class="btn-demander-code">
                        📩 Envoyer mon code de désinscription par courriel
                    </button>
                <?php endif; ?>

                <?php if ($code_envoye): ?>
                    <div class="code-box">
                        <h3 style="margin-top:0; color:#0f172a;">Entrez le code reçu par courriel</h3>
                        <p style="font-size:0.85rem; color:#64748b; margin-bottom:15px;">Saisissez le code à 6 chiffres transmis à votre adresse courriel pour valider la suppression.</p>

                        <input type="text" name="code_verification" class="input-code-otp" maxlength="6" placeholder="123456" required autocomplete="off">
                        
                        <div style="margin-top:15px;">
                            <button type="submit" name="action_valider_desinscription" onclick="return confirm('Dernière confirmation : Êtes-vous ABSOLUMENT certain de vouloir effacer définitivement votre compte et toutes ses données ?');" class="btn-valider-final">
                                🗑️ Confirmer et Supprimer mon compte définitivement
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

            </form>

            <div style="text-align:center; margin-top:20px;">
                <a href="index.php" style="color:#64748b; font-weight:bold; text-decoration:none; font-size:0.88rem;">◄ Annuler et retourner à l'accueil</a>
            </div>

        </div>
    </div>

</body>
</html>
