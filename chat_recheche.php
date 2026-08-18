<?php
// =============================================================================
// NOM DU SCRIPT : chat_recherche.php
// REVISION     : 1.0 - Tchat dédié exclusif au module "Je Cherche"
// =============================================================================
session_start();
require_once 'config.php';

// 1. PROTECTION ACCÈS : Utilisateur connecté obligatoire
$id_utilisateur = $_SESSION['id_utilisateur'] ?? null;
if (!$id_utilisateur) {
    header("Location: connexion.php");
    exit();
}

$id_recherche = isset($_GET['id_recherche']) ? (int)$_GET['id_recherche'] : 0;
$id_interlocuteur = (int)($_GET['avec'] ?? 0);

if ($id_recherche <= 0) {
    header("Location: zone_cherche.php");
    exit();
}

// 2. EXTRACTION DES INFOS DE LA RECHERCHE
$stmt_rech = $bdd->prepare("
    SELECT r.*, u.id_utilisateur AS acheteur_id, u.nom AS acheteur_nom 
    FROM jevend_recherches r
    JOIN jevend_utilisateurs u ON r.id_utilisateur = u.id_utilisateur
    WHERE r.id_recherche = ?
");
$stmt_rech->execute([$id_recherche]);
$recherche = $stmt_rech->fetch();

if (!$recherche) {
    header("Location: zone_cherche.php");
    exit();
}

$id_acheteur = (int)$recherche['acheteur_id'];
$est_acheteur = ($id_utilisateur === $id_acheteur);

// Si l'utilisateur connecté est l'acheteur, il discute avec le vendeur transmis dans l'URL (&avec=...), sinon avec l'acheteur de la recherche
if ($id_interlocuteur <= 0) {
    $id_interlocuteur = $est_acheteur ? 0 : $id_acheteur;
}

if ($id_interlocuteur <= 0) {
    header("Location: zone_cherche.php");
    exit();
}

$titre_contexte = "Recherche : " . $recherche['titre_recherche'];

// TRAITEMENT DU POST (Envoi de message classique de secours)
$erreur = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_envoyer_msg'])) {
    $msg = trim($_POST['message'] ?? '');

    if (empty($msg)) {
        $erreur = "Votre message ne peut pas être vide.";
    } elseif (mb_strlen($msg) > 350) {
        $erreur = "Le message dépasse la limite de 350 caractères.";
    } else {
        // Insertion propre réservée au contexte Je Cherche (id_annonce à NULL)
        $stmt_ins = $bdd->prepare("
            INSERT INTO jevend_chat (id_annonce, id_recherche, id_expediteur, id_destinataire, message, date_envoi)
            VALUES (NULL, ?, ?, ?, ?, NOW())
        ");
        $stmt_ins->execute([
            $id_recherche,
            $id_utilisateur, 
            $id_interlocuteur, 
            $msg
        ]);
        
        header("Location: chat_recherche.php?id_recherche=" . $id_recherche . "&avec=" . $id_interlocuteur);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat : <?= htmlspecialchars($titre_contexte) ?> — jevend.com</title>
    <link rel="stylesheet" href="chat_recherche.css">
</head>
<body class="admin-body">

    <?php include 'partials/_nav_membre.php'; ?>

    <div class="chat-box-container">
        <div class="chat-header">
            <h3>💬 Chat : <?= htmlspecialchars($titre_contexte) ?></h3>
            <p id="quotaText">Chargement...</p>
        </div>

        <div class="chat-warning-bar">
            🔒 Échange sécurisé rattaché à la demande « Je Cherche ». Effacé après 30 jours ou si résolu.
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
        const idRecherche = <?= $id_recherche ?>;
        const idInterlocuteur = <?= $id_interlocuteur ?>;
        const chatWindow = document.getElementById('chatWindow');
        const chatInput = document.getElementById('chatInput');
        const typingIndicator = document.getElementById('typingIndicator');
        const quotaText = document.getElementById('quotaText');

        let dernierCountMsg = -1;

        // --- 1. SIGNAL "JE SUIS EN TRAIN D'ÉCRIRE" ---
        chatInput.addEventListener('input', () => {
            fetch(`chat_recherche_ajax_handler.php?action=signal_frappe&id_recherche=${idRecherche}&avec=${idInterlocuteur}`)
                .catch(e => console.error(e));
        });

        // --- 2. RAFRAÎCHISSEMENT AUTOMATIQUE DES MESSAGES EN TEMPS RÉEL ---
        function rafraichirChat() {
            fetch(`chat_recherche_ajax_handler.php?action=fetch_messages&id_recherche=${idRecherche}&avec=${idInterlocuteur}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'ok') {
                        chatWindow.innerHTML = data.html || "<div style='text-align: center; color: #94a3b8; margin: auto;'>Aucun message pour le moment. Démarrez la discussion !</div>";
                        
                        if (data.total_count !== dernierCountMsg) {
                            chatWindow.scrollTop = chatWindow.scrollHeight;
                            dernierCountMsg = data.total_count;
                        }

                        quotaText.textContent = `Messages échangés : ${data.total_count}`;

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
