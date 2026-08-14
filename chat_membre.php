<?php
// =============================================================================
// NOM DU SCRIPT : chat_membre.php
// REVISION     : 2.1 - Signal de frappe instantané + Indicateur déplacé sur la zone d'écriture
// =============================================================================
session_start();
require_once 'config.php';

// 1. PROTECTION ACCÈS : Utilisateur connecté obligatoire
$id_utilisateur = $_SESSION['id_utilisateur'] ?? null;
if (!$id_utilisateur) {
    header("Location: connexion.php");
    exit();
}

// 2. VERIFICATION DES ANNONCES ACTIVES (Éligibilité)
$stmt_check_annonces = $bdd->prepare("
    SELECT COUNT(*) 
    FROM jevend_annonces 
    WHERE id_utilisateur = ? AND statut = 'actif'
");
$stmt_check_annonces->execute([$id_utilisateur]);
if ($stmt_check_annonces->fetchColumn() < 1) {
    header("Location: espace_membre.php");
    exit();
}

$id_annonce = isset($_GET['id_annonce']) ? (int)$_GET['id_annonce'] : 0;

if ($id_annonce <= 0) {
    header("Location: espace_membre.php");
    exit();
}

// 3. EXTRACTION DES INFOS DE L'ANNONCE
$stmt_ann = $bdd->prepare("
    SELECT a.*, u.id_utilisateur AS vendeur_id, u.nom AS vendeur_nom 
    FROM jevend_annonces a
    JOIN jevend_utilisateurs u ON a.id_utilisateur = u.id_utilisateur
    WHERE a.id_annonces = ?
");
$stmt_ann->execute([$id_annonce]);
$annonce = $stmt_ann->fetch();

if (!$annonce) {
    header("Location: espace_membre.php");
    exit();
}

$id_vendeur = (int)$annonce['vendeur_id'];
$est_vendeur = ($id_utilisateur === $id_vendeur);
$id_interlocuteur = $est_vendeur ? (int)($_GET['avec'] ?? 0) : $id_vendeur;

// TRAITEMENT DU POST (Envoi de message classique en secours)
$erreur = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_envoyer_msg'])) {
    $msg = trim($_POST['message'] ?? '');
    
    $stmt_count = $bdd->prepare("SELECT COUNT(*) FROM jevend_chat WHERE id_annonce = ?");
    $stmt_count->execute([$id_annonce]);
    $total_echanges = (int)$stmt_count->fetchColumn();

    if (empty($msg)) {
        $erreur = "Votre message ne peut pas être vide.";
    } elseif (mb_strlen($msg) > 350) {
        $erreur = "Le message dépasse la limite de 350 caractères.";
    } elseif ($total_echanges >= 10) {
        $erreur = "La limite de 10 messages pour cette annonce a été atteinte.";
    } elseif ($id_interlocuteur <= 0) {
        $erreur = "Interlocuteur invalide.";
    } else {
        $stmt_ins = $bdd->prepare("
            INSERT INTO jevend_chat (id_annonce, id_expediteur, id_destinataire, message, date_envoi)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt_ins->execute([$id_annonce, $id_utilisateur, $id_interlocuteur, $msg]);
        
        header("Location: chat_membre.php?id_annonce=" . $id_annonce . ($est_vendeur ? "&avec=" . $id_interlocuteur : ""));
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat : <?= htmlspecialchars($annonce['titre_objet_nettoye']) ?> — jevend.com</title>
    <link rel="stylesheet" href="chat_membre.css">
</head>
<body class="admin-body">

    <?php include 'partials/_nav_membre.php'; ?>

    <div class="chat-box-container">
        <div class="chat-header">
            <h3>💬 Chat : <?= htmlspecialchars($annonce['titre_objet_nettoye']) ?></h3>
            <p id="quotaText">Quota de conversation en cours...</p>
        </div>

        <div class="chat-warning-bar">
            🔒 Échange temporaire rattaché à l'annonce. Les messages sont effacés après 30 jours.
        </div>

        <div class="chat-body" id="chatWindow">
            <div style="text-align: center; color: #94a3b8; margin: auto; font-size: 0.85rem;">
                Chargement de la conversation...
            </div>
        </div>

        <div class="chat-footer">
            <?php if (!empty($erreur)): ?>
                <div style="color: #dc2626; font-size: 0.85rem; margin-bottom: 8px; text-align: center;"><?= htmlspecialchars($erreur) ?></div>
            <?php endif; ?>

            <!-- INDICATEUR 'EN TRAIN D'ÉCRIRE...' DÉPLACÉ JUSTE AU-DESSUS DU CHAMP DE TEXTE -->
            <div id="typingIndicator" style="font-size: 0.82rem; color: #2563eb; font-style: italic; font-weight: bold; margin-bottom: 6px; display: none;">
                ✍️ L'interlocuteur est en train d'écrire un message...
            </div>

            <form id="chatForm" action="" method="POST">
                <input type="hidden" name="action_envoyer_msg" value="1">
                <textarea id="chatInput" name="message" class="chat-input" rows="2" maxlength="350" placeholder="Écrivez votre message (350 caract. max)..." required></textarea>
                <button type="submit" class="btn-chat-send">Envoyer le message</button>
            </form>
        </div>
    </div>

    <script>
        const idAnnonce = <?= $id_annonce ?>;
        const idInterlocuteur = <?= $id_interlocuteur ?>;
        const chatWindow = document.getElementById('chatWindow');
        const chatInput = document.getElementById('chatInput');
        const typingIndicator = document.getElementById('typingIndicator');
        const quotaText = document.getElementById('quotaText');

        let dernierCountMsg = -1;
        let tempsInactivite = 0;
        const LIMITE_INACTIVITE = 180; // 3 minutes d'inactivité max

        // --- 1. REINITIALISATION DU TIMER D'INACTIVITE ---
        function reinitialiserInactivite() {
            tempsInactivite = 0;
        }

        window.addEventListener('mousemove', reinitialiserInactivite);
        window.addEventListener('keypress', reinitialiserInactivite);
        window.addEventListener('touchstart', reinitialiserInactivite);
        window.addEventListener('scroll', reinitialiserInactivite);

        setInterval(() => {
            tempsInactivite++;
            if (tempsInactivite >= LIMITE_INACTIVITE) {
                alert("⏱️ Session de tchat fermée pour inactivité. Vous allez être réorienté vers votre espace membre.");
                window.location.href = "espace_membre.php";
            }
        }, 1000);

        // --- 2. SIGNAL "JE SUIS EN TRAIN D'ÉCRIRE" (Déclenchement immédiat) ---
        chatInput.addEventListener('input', () => {
            reinitialiserInactivite();
            fetch(`chat_ajax_handler.php?action=signal_frappe&id_annonce=${idAnnonce}&avec=${idInterlocuteur}`)
                .catch(e => console.error(e));
        });

        // --- 3. RAFRAÎCHISSEMENT AUTOMATIQUE DES MESSAGES (Toutes les 2.5 sec) ---
        function rafraichirChat() {
            fetch(`chat_ajax_handler.php?action=fetch_messages&id_annonce=${idAnnonce}&avec=${idInterlocuteur}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'ok') {
                        chatWindow.innerHTML = data.html || "<div style='text-align: center; color: #94a3b8; margin: auto;'>Aucun message pour le moment.</div>";
                        
                        if (data.total_count !== dernierCountMsg) {
                            chatWindow.scrollTop = chatWindow.scrollHeight;
                            dernierCountMsg = data.total_count;
                        }

                        quotaText.textContent = `Quota de conversation : ${data.total_count} / 10 messages max`;

                        if (data.autre_ecrit) {
                            typingIndicator.style.display = 'block';
                        } else {
                            typingIndicator.style.display = 'none';
                        }
                    }
                })
                .catch(err => console.error("Erreur direct :", err));
        }

        rafraichirChat();
        setInterval(rafraichirChat, 2500);
    </script>
</body>
</html>
