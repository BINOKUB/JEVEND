<?php
// =============================================================================
// NOM DU SCRIPT : chat_ajax_handler.php
// REVISION     : 2.3 - Gestion AJAX Annonces épurée (Zéro ligne vide de frappe)
// =============================================================================
session_start();
require_once 'config.php';

header('Content-Type: application/json');

$id_utilisateur = $_SESSION['id_utilisateur'] ?? null;
if (!$id_utilisateur) {
    echo json_encode(['status' => 'error', 'message' => 'Non connecté']);
    exit();
}

$action = $_GET['action'] ?? '';
$id_annonce = (int)($_GET['id_annonce'] ?? 0);
$id_interlocuteur = (int)($_GET['avec'] ?? 0);

if ($id_annonce <= 0 || $id_interlocuteur <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Paramètres invalides']);
    exit();
}

// -----------------------------------------------------------------------------
// ACTION 1 : Signal "Je suis en train d'écrire" (Propre, sans création de lignes)
// -----------------------------------------------------------------------------
if ($action === 'signal_frappe') {
    try {
        $stmt = $bdd->prepare("
            UPDATE jevend_chat 
            SET a_tape_a = NOW() 
            WHERE id_annonce = ? 
              AND id_expediteur = ? 
              AND id_destinataire = ?
            ORDER BY date_envoi DESC LIMIT 1
        ");
        $stmt->execute([$id_annonce, $id_utilisateur, $id_interlocuteur]);
        echo json_encode(['status' => 'ok']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error']);
    }
    exit();
}

// -----------------------------------------------------------------------------
// ACTION 2 : Récupération des messages + Indicateur de frappe de l'autre
// -----------------------------------------------------------------------------
if ($action === 'fetch_messages') {
    try {
        // 1. Charger l'historique (uniquement les vrais messages non vides)
        $stmt_msgs = $bdd->prepare("
            SELECT c.*, u.nom AS expediteur_nom 
            FROM jevend_chat c
            JOIN jevend_utilisateurs u ON c.id_expediteur = u.id_utilisateur
            WHERE c.id_annonce = ? 
              AND ((c.id_expediteur = ? AND c.id_destinataire = ?) 
               OR (c.id_expediteur = ? AND c.id_destinataire = ?))
              AND c.message != '' AND c.message IS NOT NULL
            ORDER BY c.date_envoi ASC
        ");
        $stmt_msgs->execute([$id_annonce, $id_utilisateur, $id_interlocuteur, $id_interlocuteur, $id_utilisateur]);
        $messages = $stmt_msgs->fetchAll(PDO::FETCH_ASSOC);

        // 2. Marquer comme lus
        $stmt_lu = $bdd->prepare("
            UPDATE jevend_chat 
            SET lu = 1 
            WHERE id_annonce = ? AND id_destinataire = ? AND id_expediteur = ?
        ");
        $stmt_lu->execute([$id_annonce, $id_utilisateur, $id_interlocuteur]);

        // 3. Vérifier si l'interlocuteur est en train d'écrire (< 4 secondes)
        $stmt_frappe = $bdd->prepare("
            SELECT COUNT(*) 
            FROM jevend_chat c
            WHERE c.id_annonce = ? 
              AND c.id_expediteur = ? 
              AND c.id_destinataire = ?
              AND c.a_tape_a >= NOW() - INTERVAL 4 SECOND
        ");
        $stmt_frappe->execute([$id_annonce, $id_interlocuteur, $id_utilisateur]);
        $l_autre_ecrit = ($stmt_frappe->fetchColumn() > 0);

        // Rendu HTML des bulles de chat
        $html_messages = "";
        foreach ($messages as $m) {
            $est_moi = ($m['id_expediteur'] == $id_utilisateur);
            $nom_expediteur = htmlspecialchars($m['expediteur_nom']);
            $date_f = date('d/m H:i', strtotime($m['date_envoi']));
            $msg_txt = htmlspecialchars($m['message']);

            $wrapper_class = $est_moi ? 'msg-wrapper-me' : 'msg-wrapper-other';
            $bubble_class  = $est_moi ? 'msg-me' : 'msg-other';

            $html_messages .= "
                <div class='chat-msg-wrapper {$wrapper_class}'>
                    <div class='chat-avatar' title='{$nom_expediteur}'>👤</div>
                    <div class='msg-content-block'>
                        <span class='msg-author-name'>{$nom_expediteur}</span>
                        <div class='msg-bubble {$bubble_class}'>
                            $msg_txt
                            <span class='msg-time'>$date_f</span>
                        </div>
                    </div>
                </div>
            ";
        }

        echo json_encode([
            'status' => 'ok',
            'html' => $html_messages,
            'total_count' => count($messages),
            'autre_ecrit' => $l_autre_ecrit
        ]);

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
}
