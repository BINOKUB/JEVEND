<?php
// =============================================================================
// SCRIPT      : chat_live_ajax.php
// PROJET      : JEVEND | BRANCHE : main
// REVISION    : 2.0 | AUTEUR : Dan | DATE : 2026-08-29
// DESC        : Traitement AJAX Chat Live (Sécurité IP, Verrouillage & Fil continu)
// NOM DU SCRIPT: chat_live_ajax.php
// =============================================================================

require_once 'config.php';

// Inclusion sécurisée du module de sécurité selon son emplacement
if (file_exists('admin_modules/_chat_live_security.php')) {
    require_once 'admin_modules/_chat_live_security.php';
} elseif (file_exists('_chat_live_security.php')) {
    require_once '_chat_live_security.php';
}

header('Content-Type: application/json; charset=UTF-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ACTION 0 : Récupérer le mode actuel
if ($action === 'get_mode_actuel') {
    try {
        $stmt_cfg = $bdd->query("SELECT mode_chat FROM jevend_chat_live_config WHERE id = 1 LIMIT 1");
        $config = $stmt_cfg->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['succes' => true, 'mode_chat' => $config['mode_chat'] ?? 'statique']);
    } catch (PDOException $e) {
        echo json_encode(['succes' => false, 'mode_chat' => 'statique']);
    }
    exit;
}

// ACTION 1 : Obtenir 5 questions FAQ aléatoires
if ($action === 'get_faq_lot') {
    try {
        $stmt = $bdd->query("SELECT id, question, reponse FROM jevend_faq WHERE actif = 1 ORDER BY RAND() LIMIT 5");
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['succes' => true, 'questions' => $questions]);
    } catch (PDOException $e) {
        echo json_encode(['succes' => false, 'message' => 'Erreur SQL']);
    }
    exit;
}

// ACTION 2 : Soumission d'une question ou poursuite (Avec capture IP)
if ($action === 'creer_ticket') {
    $question_texte = trim($_POST['question_texte'] ?? '');
    $code_ticket_existant = trim($_POST['code_ticket'] ?? '');
    $ip_client = function_exists('get_client_ip_chat') ? get_client_ip_chat() : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

    if (empty($question_texte)) {
        echo json_encode(['succes' => false, 'message' => 'Veuillez saisir un message.']);
        exit;
    }

    if (mb_strlen($question_texte) > 150) {
        $question_texte = mb_substr($question_texte, 0, 150);
    }

    // Poursuite d'un ticket existant
    if (!empty($code_ticket_existant)) {
        try {
            $stmt_chk = $bdd->prepare("SELECT id_ticket, question_texte, statut FROM jevend_chat_tickets_live WHERE code_ticket = ? LIMIT 1");
            $stmt_chk->execute([$code_ticket_existant]);
            $tck = $stmt_chk->fetch(PDO::FETCH_ASSOC);

            if ($tck && $tck['statut'] !== 'ferme') {
                $nouveau_fil = $tck['question_texte'] . "\n[Client]: " . $question_texte;
                $stmt_up = $bdd->prepare("UPDATE jevend_chat_tickets_live SET question_texte = ?, ip_visiteur = ?, statut = 'en_attente' WHERE id_ticket = ?");
                $stmt_up->execute([$nouveau_fil, $ip_client, $tck['id_ticket']]);

                echo json_encode(['succes' => true, 'code_ticket' => $code_ticket_existant, 'verrouille' => true]);
                exit;
            }
        } catch (PDOException $e) {}
    }

    // Création d'un nouveau ticket
    $est_connecte = isset($_SESSION['id_utilisateur']) && (int)$_SESSION['id_utilisateur'] > 0;
    $id_membre = $est_connecte ? (int)$_SESSION['id_utilisateur'] : null;
    $type_visiteur = $est_connecte ? 'connecte' : 'anonyme';
    $session_id = session_id();

    $prefixe = $est_connecte ? 'C-' : 'A-';
    $code_ticket = $prefixe . rand(10000, 99999);

    try {
        $stmt_ins = $bdd->prepare("
            INSERT INTO jevend_chat_tickets_live 
            (code_ticket, type_visiteur, id_membre, session_id, ip_visiteur, question_texte, statut, date_creation) 
            VALUES (?, ?, ?, ?, ?, ?, 'en_attente', NOW())
        ");
        $stmt_ins->execute([$code_ticket, $type_visiteur, $id_membre, $session_id, $ip_client, $question_texte]);

        echo json_encode(['succes' => true, 'code_ticket' => $code_ticket, 'verrouille' => true]);
    } catch (PDOException $e) {
        echo json_encode(['succes' => false, 'message' => 'Erreur lors de l\'enregistrement.']);
    }
    exit;
}

// ACTION 3 : Vérification du statut et verrouillage
if ($action === 'verifier_reponse_ticket') {
    $code_ticket = trim($_POST['code_ticket'] ?? '');

    if (empty($code_ticket)) {
        echo json_encode(['succes' => false]);
        exit;
    }

    try {
        $stmt_chk = $bdd->prepare("SELECT question_texte, reponse_admin, statut FROM jevend_chat_tickets_live WHERE code_ticket = ? LIMIT 1");
        $stmt_chk->execute([$code_ticket]);
        $ticket = $stmt_chk->fetch(PDO::FETCH_ASSOC);

        if ($ticket) {
            $est_verrouille = ($ticket['statut'] === 'en_attente');
            echo json_encode([
                'succes' => true, 
                'statut' => $ticket['statut'],
                'verrouille' => $est_verrouille,
                'question_texte' => $ticket['question_texte'],
                'reponse_admin' => $ticket['reponse_admin']
            ]);
        } else {
            echo json_encode(['succes' => false]);
        }
    } catch (PDOException $e) {
        echo json_encode(['succes' => false]);
    }
    exit;
}

// ACTION 4 : Récupérer la liste mise à jour des tickets pour l'admin
if ($action === 'get_admin_tickets_html') {
    try {
        $stmt_tck = $bdd->query("SELECT * FROM jevend_chat_tickets_live ORDER BY id_ticket DESC LIMIT 30");
        $tickets = $stmt_tck->fetchAll(PDO::FETCH_ASSOC);

        ob_start();
        if (empty($tickets)): ?>
            <div style="text-align: center; color: #94a3b8; padding: 25px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 6px; font-size: 0.88rem;">
                Aucun ticket enregistré pour le moment.
            </div>
        <?php else: ?>
            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; text-align: left;">
                <thead>
                    <tr style="background: #0f172a; color: #ffffff;">
                        <th style="padding: 10px;">Code / Statut</th>
                        <th style="padding: 10px;">Visiteur</th>
                        <th style="padding: 10px;">Échanges / Questions</th>
                        <th style="padding: 10px;">Date</th>
                        <th style="padding: 10px; text-align: center;">Actions & Réponses</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $t): ?>
                        <tr style="border-bottom: 1px solid #e2e8f0; vertical-align: top; background: <?= ($t['statut'] === 'ferme') ? '#f8fafc' : '#ffffff' ?>;">
                            
                            <td style="padding: 10px;">
                                <strong style="color: #2563eb;"><?= htmlspecialchars($t['code_ticket']) ?></strong><br>
                                <?php if ($t['statut'] === 'ferme'): ?>
                                    <span style="background: #e2e8f0; color: #475569; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; font-weight: bold;">🔒 FERMÉ</span>
                                <?php elseif ($t['statut'] === 'repondu'): ?>
                                    <span style="background: #dcfce7; color: #15803d; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; font-weight: bold;">✔ RÉPONDU</span>
                                <?php else: ?>
                                    <span style="background: #fef3c7; color: #d97706; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; font-weight: bold;">⏳ EN ATTENTE</span>
                                <?php endif; ?>
                            </td>

                            <td style="padding: 10px;">
                                <?php if ($t['type_visiteur'] === 'connecte'): ?>
                                    <span style="background: #dcfce7; color: #15803d; padding: 3px 6px; border-radius: 4px; font-size: 0.75rem; font-weight: bold;">Membre #<?= (int)$t['id_membre'] ?></span>
                                <?php else: ?>
                                    <span style="background: #f1f5f9; color: #475569; padding: 3px 6px; border-radius: 4px; font-size: 0.75rem; font-weight: bold;">Anonyme</span>
                                <?php endif; ?>
                                <?php if (!empty($t['ip_visiteur'])): ?>
                                    <br><small style="color: #94a3b8; font-size: 0.68rem;">IP: <?= htmlspecialchars($t['ip_visiteur']) ?></small>
                                <?php endif; ?>
                            </td>

                            <td style="padding: 10px; max-width: 260px; white-space: pre-wrap; font-size: 0.82rem;">
                                <?= htmlspecialchars($t['question_texte']) ?>
                            </td>

                            <td style="padding: 10px; font-size: 0.78rem; color: #64748b;">
                                <?= date('d/m/Y H:i', strtotime($t['date_creation'])) ?>
                            </td>

                            <td style="padding: 10px; text-align: center;">
                                <?php if (!empty($t['reponse_admin'])): ?>
                                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 8px; border-radius: 4px; text-align: left; margin-bottom: 6px; font-size: 0.8rem;">
                                        <strong>Réponse transmise :</strong><br>
                                        <?= htmlspecialchars($t['reponse_admin']) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($t['statut'] !== 'ferme'): ?>
                                    <form method="POST" style="display: flex; gap: 5px; flex-direction: column; margin-bottom: 6px;">
                                        <input type="hidden" name="action_repondre_ticket" value="1">
                                        <input type="hidden" name="id_ticket" value="<?= $t['id_ticket'] ?>">
                                        <textarea name="reponse_admin" placeholder="Répondre..." required style="padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.8rem; height: 40px; resize: vertical;"><?= htmlspecialchars($t['reponse_admin'] ?? '') ?></textarea>
                                        <button type="submit" style="background: #2563eb; color: #fff; border: none; padding: 4px; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 0.78rem;">
                                            <?= !empty($t['reponse_admin']) ? 'Mettre à jour la réponse' : 'Envoyer la réponse' ?>
                                        </button>
                                    </form>

                                    <form method="POST" style="display: inline-block; width: 100%; margin-bottom: 4px;">
                                        <input type="hidden" name="action_cloturer_ticket" value="1">
                                        <input type="hidden" name="id_ticket" value="<?= $t['id_ticket'] ?>">
                                        <button type="submit" onclick="return confirm('Clôturer cette discussion ?');" style="background: #64748b; color: #fff; border: none; padding: 4px 8px; border-radius: 4px; font-size: 0.72rem; font-weight: bold; cursor: pointer; width: 100%;">
                                            🔒 Clôturer la discussion
                                        </button>
                                    </form>

                                    <button type="button" onclick="transfererVersFAQ(<?= htmlspecialchars(json_encode($t['question_texte'])) ?>, <?= htmlspecialchars(json_encode($t['reponse_admin'] ?? '')) ?>)" style="background: #f59e0b; color: #fff; border: none; padding: 4px 8px; border-radius: 4px; font-size: 0.72rem; font-weight: bold; cursor: pointer; width: 100%;">
                                        ↗ Transférer vers l'Éditeur FAQ
                                    </button>
                                <?php else: ?>
                                    <form method="POST" style="display: inline-block; width: 100%;">
                                        <input type="hidden" name="action_supprimer_ticket" value="1">
                                        <input type="hidden" name="id_ticket" value="<?= $t['id_ticket'] ?>">
                                        <button type="submit" onclick="return confirm('Supprimer définitivement ce ticket ?');" style="background: #ef4444; color: #fff; border: none; padding: 6px 10px; border-radius: 4px; font-size: 0.78rem; font-weight: bold; cursor: pointer; width: 100%;">
                                            🗑️ Supprimer le ticket
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>

                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif;
        $html = ob_get_clean();

        echo json_encode(['succes' => true, 'html' => $html]);
    } catch (PDOException $e) {
        echo json_encode(['succes' => false]);
    }
    exit;
}

echo json_encode(['succes' => false, 'message' => 'Action non reconnue.']);
exit;
