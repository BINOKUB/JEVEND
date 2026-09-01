<?php
// =============================================================================
// NOM DU SCRIPT : nous_joindre.php
// REVISION     : 1.8 - Intégration Verrou BDD + Formulaire Sécurisé
// SCRIPT COMPLET ET SUIVI
// =============================================================================
session_start();
require_once 'config.php';

// 1. HORODATAGE DE L'ARRIVÉE (Détection de vitesse automatisée)
if (empty($_SESSION['form_time_start'])) {
    $_SESSION['form_time_start'] = time();
}

// 2. JETON UNIQUE DE SESSION
if (empty($_SESSION['token_human'])) {
    $_SESSION['token_human'] = bin2hex(random_bytes(16));
}

$msg_joindre_succes = "";
$msg_joindre_erreur = "";

// 3. VÉRIFICATION DE L'ÉTAT DU FORMULAIRE EN BDD
$formulaire_actif = true;
if (isset($bdd)) {
    try {
        $stmt_cfg = $bdd->prepare("SELECT valeur FROM jevend_config WHERE cle = 'nous_joindre_actif'");
        $stmt_cfg->execute();
        $res_cfg = $stmt_cfg->fetchColumn();
        if ($res_cfg !== false && $res_cfg === '0') {
            $formulaire_actif = false;
        }
    } catch (PDOException $e) { }
}

// 4. DÉTECTION DU STATUT UTILISATEUR
$id_membre_connecte = $_SESSION['id_utilisateur'] ?? null;
$est_connecte = false;

$nom_champ = "visiteur";
$mail_defaut = "";
$entreprise_defaut = "";
$nom_usage_defaut = "";

if ($id_membre_connecte && isset($bdd)) {
    try {
        $stmt_m = $bdd->prepare("SELECT id_utilisateur, nom, courriel, nom_entreprise FROM jevend_utilisateurs WHERE id_utilisateur = ?");
        $stmt_m->execute([(int)$id_membre_connecte]);
        $data_m = $stmt_m->fetch(PDO::FETCH_ASSOC);

        if ($data_m) {
            $est_connecte = true;
            $nom_champ = !empty($data_m['nom']) ? $data_m['nom'] : "membre_" . $data_m['id_utilisateur'];
            $mail_defaut = $data_m['courriel'] ?? '';
            $entreprise_defaut = $data_m['nom_entreprise'] ?? '';
            $nom_usage_defaut = $data_m['nom'] ?? '';
        }
    } catch (PDOException $e) { }
}

// 5. TRAITEMENT DE LA SOUMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_nous_joindre']) && $formulaire_actif) {
    
    // A. Piège Honeypot
    $honeypot = $_POST['website'] ?? '';

    // B. Validation du Jeton JS
    $token_soumis = $_POST['token_human_check'] ?? '';
    $est_humain = ($token_soumis === $_SESSION['token_human']);

    // C. Validation du temps de remplissage (minimum 2 secondes)
    $temps_saisie = time() - ($_SESSION['form_time_start'] ?? time());
    $trop_rapide = ($temps_saisie < 2);

    // Données nettoyées & Sécurisées
    $no_de_membre = !empty($_POST['no_de_membre']) ? (int)$_POST['no_de_membre'] : null;
    $nom_post = htmlspecialchars(trim($_POST['nom'] ?? 'visiteur'), ENT_QUOTES, 'UTF-8');
    $nom_usage = htmlspecialchars(trim($_POST['nom_usage_jevend'] ?? ''), ENT_QUOTES, 'UTF-8');
    $mail = filter_var(trim($_POST['mail'] ?? ''), FILTER_VALIDATE_EMAIL);
    $entreprise = htmlspecialchars(trim($_POST['entreprise'] ?? ''), ENT_QUOTES, 'UTF-8');
    $sujet = htmlspecialchars(trim($_POST['sujet_titre'] ?? ''), ENT_QUOTES, 'UTF-8');
    
    // NETTOYAGE ET FILTRAGE DU CHAMP TEXTE (Anti-XSS + Détection de Liens)
    $texte = htmlspecialchars(trim($_POST['texte'] ?? ''), ENT_QUOTES, 'UTF-8');
    $nombre_liens = preg_match_all('/https?:\/\//i', $texte);

    if (!$est_connecte || empty($nom_post)) {
        $nom_post = "visiteur";
    }

    // VÉRIFICATIONS STRICTES DE SÉCURITÉ
    if (!empty($honeypot)) {
        // Robot piégé silencieusement
        $msg_joindre_succes = "Votre message a été transmis avec succès !";
    } elseif ($trop_rapide) {
        $msg_joindre_erreur = "Soumission trop rapide détectée. Prenez le temps de vérifier vos informations.";
    } elseif (!$est_humain) {
        $msg_joindre_erreur = "Veuillez cocher la case 'Je ne suis pas un robot'.";
    } elseif (!$mail || empty($sujet) || empty($texte)) {
        $msg_joindre_erreur = "Veuillez remplir correctement tous les champs obligatoires (Courriel, Sujet et Message).";
    } elseif (mb_strlen($texte) < 10) {
        $msg_joindre_erreur = "Votre message est trop court (minimum 10 caractères).";
    } elseif (mb_strlen($texte) > 2000) {
        $msg_joindre_erreur = "Votre message est trop long (maximum 2000 caractères).";
    } elseif ($nombre_liens > 1) {
        $msg_joindre_erreur = "Pour des raisons de sécurité, les messages contenant plusieurs liens Web ne sont pas autorisés.";
    } else {
        try {
            $sql_ins = "INSERT INTO jevend_nous_joindre 
                        (nom, nom_usage_jevend, mail, no_de_membre, entreprise, sujet_titre, texte) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt_ins = $bdd->prepare($sql_ins);
            $stmt_ins->execute([$nom_post, $nom_usage, $mail, $no_de_membre, $entreprise, $sujet, $texte]);

            $msg_joindre_succes = "Merci ! Votre demande a été transmise à notre service client.";
            
            // Réinitialisation des jetons et champs
            $_SESSION['token_human'] = bin2hex(random_bytes(16));
            unset($_SESSION['form_time_start']);
            $_POST['texte'] = "";
            $_POST['sujet_titre'] = "";
        } catch (PDOException $e) {
            $msg_joindre_erreur = "Erreur SQL : " . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nous Joindre — jevend.com</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .nj-page-container { max-width: 750px; margin: 30px auto; padding: 20px; }
        .nj-card { background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .nj-titre { margin-top: 0; color: #0f172a; font-size: 1.5rem; text-align: center; margin-bottom: 25px; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; }
        .nj-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        @media (max-width: 600px) { .nj-grid { grid-template-columns: 1fr; } }
        .nj-group { margin-bottom: 18px; }
        .nj-group label { display: block; font-size: 0.9rem; font-weight: bold; color: #334155; margin-bottom: 6px; }
        .nj-group input, .nj-group textarea { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; color: #0f172a; box-sizing: border-box; background: #f8fafc; }
        .nj-group input:focus, .nj-group textarea:focus { border-color: #2563eb; background: #ffffff; outline: none; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
        .nj-honeypot { display: none !important; }
        
       /* WIDGET CAPTCHA MAISON RESPONSIVE */
.nj-captcha-custom { 
    display: flex; 
    align-items: center; 
    justify-content: space-between; 
    background: #f8fafc; 
    border: 1px solid #cbd5e1; 
    padding: 12px 18px; 
    border-radius: 6px; 
    width: 100%;             /* S'adapte à la largeur du parent */
    max-width: 310px;        /* Ne dépasse jamais 310px sur PC */
    box-sizing: border-box;  /* Inclut le padding dans le calcul de largeur */
    margin: 0 auto 20px auto; 
    box-shadow: 0 2px 4px rgba(0,0,0,0.02); 
}

        .nj-captcha-label { display: flex; align-items: center; gap: 12px; font-size: 0.95rem; font-weight: 600; color: #1e293b; cursor: pointer; user-select: none; }
        .nj-captcha-label input[type="checkbox"] { width: 22px; height: 22px; cursor: pointer; accent-color: #2563eb; }
        .nj-captcha-badge { text-align: center; font-size: 0.7rem; color: #64748b; font-weight: bold; border-left: 1px solid #e2e8f0; padding-left: 12px; }

        .btn-nj-envoi { width: 100%; background: #2563eb; color: #ffffff; border: none; padding: 12px; font-size: 1rem; font-weight: bold; border-radius: 6px; cursor: pointer; transition: background 0.2s; }
        .btn-nj-envoi:hover { background: #1d4ed8; }
        .msg-ok { background: #dcfce7; color: #166534; padding: 12px; border-radius: 6px; margin-bottom: 20px; text-align: center; font-weight: bold; border: 1px solid #bbf7d0; }
        .msg-err { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 20px; text-align: center; font-weight: bold; border: 1px solid #fecaca; }
        .badge-statut-usr { display: inline-block; background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; margin-bottom: 15px; }
    </style>
</head>
<body class="admin-body">
<?php include 'partials/_nav_publique.php'; ?>

    <div class="nj-page-container">
        <div class="nj-card">
            <h2 class="nj-titre">✉️ Nous Joindre — Assistance Clientèle</h2>

            <?php if (!$formulaire_actif): ?>
                <div class="msg-err" style="background:#fef3c7; color:#92400e; border-color:#fde68a; font-size:1.05rem; padding:15px; text-align:center;">
                    ⚠️ Le service de messagerie directe est actuellement fermé. Veuillez réessayer ultérieurement.
                </div>
            <?php else: ?>

                <?php if ($est_connecte): ?>
                    <div style="text-align:center;">
                        <span class="badge-statut-usr">👤 Connecté en tant que : <?= htmlspecialchars($nom_champ) ?> (ID #<?= (int)$id_membre_connecte ?>)</span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($msg_joindre_succes)): ?>
                    <div class="msg-ok"><?= $msg_joindre_succes ?></div>
                <?php endif; ?>

                <?php if (!empty($msg_joindre_erreur)): ?>
                    <div class="msg-err"><?= $msg_joindre_erreur ?></div>
                <?php endif; ?>

                <form method="POST" action="nous_joindre.php">
                    <input type="text" name="website" class="nj-honeypot" tabindex="-1" autocomplete="off">
                    <input type="hidden" name="no_de_membre" value="<?= $id_membre_connecte ? (int)$id_membre_connecte : '' ?>">
                    <input type="hidden" name="nom" value="<?= htmlspecialchars($nom_champ) ?>">
                    
                    <input type="hidden" name="token_human_check" id="token_human_check" value="">

                    <div class="nj-grid">
                        <div class="nj-group">
                            <label for="nj_mail">Votre adresse courriel *</label>
                            <input type="email" id="nj_mail" name="mail" maxlength="150" value="<?= htmlspecialchars($mail_defaut) ?>" required placeholder="ex: votre@courriel.com">
                        </div>

                        <div class="nj-group">
                            <label for="nj_nom_usage">Nom d'usage (sur l'annonce / profil)</label>
                            <input type="text" id="nj_nom_usage" maxlength="100" name="nom_usage_jevend" value="<?= htmlspecialchars($nom_usage_defaut) ?>" placeholder="Ex: Jean M.">
                        </div>
                    </div>

                    <div class="nj-group">
                        <label for="nj_entreprise">Entreprise / Commerce (optionnel)</label>
                        <input type="text" id="nj_entreprise" maxlength="150" name="entreprise" value="<?= htmlspecialchars($entreprise_defaut) ?>" placeholder="Ex: Garage Matane Inc.">
                    </div>

                    <div class="nj-group">
                        <label for="nj_sujet">Sujet de votre demande *</label>
                        <input type="text" id="nj_sujet" name="sujet_titre" maxlength="150" value="<?= isset($_POST['sujet_titre']) ? htmlspecialchars($_POST['sujet_titre']) : '' ?>" required placeholder="Ex: Question concernant mon compte, publication...">
                    </div>

                    <div class="nj-group">
                        <label for="nj_texte">Message détaillé * <span style="font-size:0.8rem; color:#64748b;">(entre 10 et 2000 caractères)</span></label>
                        <textarea id="nj_texte" name="texte" rows="6" required minlength="10" maxlength="2000" placeholder="Décrivez votre demande avec le plus de précisions possible..."><?php echo isset($_POST['texte']) ? htmlspecialchars($_POST['texte']) : ''; ?></textarea>
                    </div>

                    <!-- WIDGET CAPTCHA MAISON -->
                    <div class="nj-captcha-custom">
                        <label class="nj-captcha-label">
                            <input type="checkbox" id="chk_human" onchange="validerHumain(this)">
                            <span>Je ne suis pas un robot</span>
                        </label>
                        <div class="nj-captcha-badge">
                            🛡️<br>Sécurité
                        </div>
                    </div>

                    <button type="submit" name="action_nous_joindre" class="btn-nj-envoi">🚀 Envoyer mon message</button>
                </form>

            <?php endif; ?>
        </div>
    </div>

    <script>
    function validerHumain(checkbox) {
        const inputHidden = document.getElementById('token_human_check');
        if (checkbox.checked) {
            inputHidden.value = "<?= $_SESSION['token_human'] ?>";
        } else {
            inputHidden.value = "";
        }
    }
    </script>
</body>
</html>
