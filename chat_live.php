<?php
// =============================================================================
// SCRIPT      : chat_live.php
// PROJET      : JEVEND | BRANCHE : main
// REVISION    : 2.1 | AUTEUR : Dan | DATE : 2026-08-29
// DESC        : Interface Public Chat Live (Fix du formulaire persistant en mode Statique)
// NOM DU SCRIPT: chat_live.php
// =============================================================================

if (!isset($bdd)) {
    require_once 'config.php';
}

// Inclusion sécurisée du module de sécurité selon son emplacement
if (file_exists('admin_modules/_chat_live_security.php')) {
    require_once 'admin_modules/_chat_live_security.php';
} elseif (file_exists('_chat_live_security.php')) {
    require_once '_chat_live_security.php';
}

try {
    $stmt_mode = $bdd->query("SELECT mode_chat FROM jevend_chat_live_config WHERE id = 1 LIMIT 1");
    $config_live = $stmt_mode->fetch(PDO::FETCH_ASSOC);
    $mode_chat = $config_live['mode_chat'] ?? 'statique';
} catch (PDOException $e) {
    $mode_chat = 'statique';
}

if ($mode_chat === 'off') {
    return;
}

$message_bienvenue = function_exists('get_chat_welcome_message') 
    ? get_chat_welcome_message($bdd) 
    : "Bonjour ! Choisissez une question ci-dessous ou posez la vôtre.";
?>

<!-- BOUTON FLOTTANT BULLE -->
<div id="btn-chat-live-trigger" onclick="toggleChatLiveWindow()" style="position: fixed; bottom: 25px; right: 25px; z-index: 9999; background: #2563eb; color: #ffffff; width: 55px; height: 55px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 14px rgba(0,0,0,0.25); cursor: pointer; transition: transform 0.2s;">
    <span style="font-size: 1.6rem;">💬</span>
</div>

<!-- FENÊTRE DU CHAT LIVE -->
<div id="window-chat-live" style="display: none; position: fixed; bottom: 90px; right: 25px; z-index: 9999; width: 350px; max-width: 92vw; height: 460px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.18); flex-direction: column; overflow: hidden; font-family: Arial, sans-serif;">
    
    <!-- EN-TÊTE AVEC BADGE D'ÉTAT DYNAMIQUE -->
    <div style="background: #0f172a; color: #ffffff; padding: 12px 15px; display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; flex-direction: column; gap: 2px;">
            <div style="font-weight: bold; font-size: 0.92rem; display: flex; align-items: center; gap: 8px;">
                <span>🤖</span> Assistant Jevend
            </div>
            <div id="badge-etat-live-container" style="font-size: 0.72rem; color: #cbd5e1; display: flex; align-items: center; gap: 5px;">
                <?php if ($mode_chat === 'live'): ?>
                    <span style="color: #22c55e;">🟢</span> <strong>Admin en ligne</strong>
                <?php else: ?>
                    <span style="color: #f59e0b;">🟡</span> <strong>Assistant FAQ (Auto)</strong>
                <?php endif; ?>
            </div>
        </div>
        <button onclick="toggleChatLiveWindow()" style="background: none; border: none; color: #ffffff; font-size: 1.1rem; cursor: pointer; padding: 0 5px;">✕</button>
    </div>

    <!-- ZONE CENTRALE D'ÉCHANGE -->
    <div id="chat-live-body" style="flex: 1; padding: 12px; overflow-y: auto; background: #f8fafc; display: flex; flex-direction: column; gap: 10px;">
        
        <!-- SALUTATION DYNAMIQUE MEMBRE / ANONYME -->
        <div style="background: #e2e8f0; color: #0f172a; padding: 10px; border-radius: 8px; font-size: 0.84rem; line-height: 1.4;">
            <?= $message_bienvenue ?>
        </div>

        <!-- CONTENEUR DES 5 QUESTIONS FAQ -->
        <div id="container-faq-questions" style="display: flex; flex-direction: column; gap: 6px; margin-top: 5px;">
            <!-- Rempli en AJAX -->
        </div>

        <!-- ZONE DE RÉPONSE FAQ AFFICHÉE -->
        <div id="box-reponse-faq" style="display: none; background: #ffffff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 12px; font-size: 0.83rem; color: #1e3a8a; line-height: 1.4; margin-top: 5px;">
        </div>

    </div>

    <!-- PIED DE FENÊTRE -->
    <div style="padding: 10px; border-top: 1px solid #cbd5e1; background: #ffffff; display: flex; flex-direction: column; gap: 8px;">
        
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <button id="btn-resort-faq" onclick="chargerLotFAQLive()" style="background: #f1f5f9; color: #1e293b; border: 1px solid #cbd5e1; padding: 5px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                🔄 Mélanger questions FAQ
            </button>

            <button id="btn-toggle-question-form" onclick="afficherFormulaireTicketLive()" style="background: none; border: none; color: #2563eb; font-size: 0.78rem; font-weight: bold; cursor: pointer; text-decoration: underline; <?= ($mode_chat === 'live') ? 'display:none;' : 'display:inline-block;' ?>">
                Une question ?
            </button>

            <span id="compteur-char-live" style="font-size: 0.75rem; color: #64748b; <?= ($mode_chat === 'live') ? 'display:inline-block;' : 'display:none;' ?>">0/150</span>
        </div>

        <!-- FORMULAIRE DE SAISIE AVEC VERROUILLAGE DÉFENSIF -->
        <div id="form-ticket-live-wrapper" style="display: <?= ($mode_chat === 'live') ? 'block' : 'none' ?>;">
            <div style="display: flex; gap: 6px;">
                <input type="text" id="input-ticket-live" maxlength="150" placeholder="Écrivez votre message..." style="flex: 1; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.8rem; outline: none;" oninput="actualiserCompteurLive(this)" onkeypress="if(event.key==='Enter') soumettreTicketLive()">
                <button id="btn-send-ticket-live" onclick="soumettreTicketLive()" style="background: #2563eb; color: #ffffff; border: none; padding: 8px 12px; border-radius: 6px; font-weight: bold; font-size: 0.8rem; cursor: pointer;">Envoyer</button>
            </div>
        </div>

    </div>

</div>

<script>
let windowChatOuverte = false;
let ticketActifCode = localStorage.getItem('jevend_last_ticket') || null;
let ecouteTicketTimer = null;
let modeActuelChat = "<?= $mode_chat ?>";
let formulaireTicketForceOuvert = false; // Témoin d'ouverture manuelle en mode statique

function toggleChatLiveWindow() {
    const win = document.getElementById('window-chat-live');
    windowChatOuverte = !windowChatOuverte;
    win.style.display = windowChatOuverte ? 'flex' : 'none';

    if (windowChatOuverte) {
        if (document.getElementById('container-faq-questions').children.length === 0) {
            chargerLotFAQLive();
        }
        if (ticketActifCode) {
            verifierReponseTicketLive();
        }
    }
}

function chargerLotFAQLive() {
    const container = document.getElementById('container-faq-questions');
    container.innerHTML = '<div style="text-align:center; color:#94a3b8; font-size:0.8rem; padding:10px;">Chargement...</div>';
    document.getElementById('box-reponse-faq').style.display = 'none';

    fetch('chat_live_ajax.php?action=get_faq_lot')
        .then(res => res.json())
        .then(data => {
            container.innerHTML = '';
            if (data.succes && data.questions.length > 0) {
                data.questions.forEach(q => {
                    const btn = document.createElement('button');
                    btn.style.cssText = "background: #ffffff; border: 1px solid #cbd5e1; padding: 8px 10px; border-radius: 6px; text-align: left; font-size: 0.8rem; color: #1e293b; cursor: pointer; transition: background 0.2s;";
                    btn.textContent = q.question;
                    btn.onclick = () => afficherReponseFAQLive(q.reponse);
                    container.appendChild(btn);
                });
            } else {
                container.innerHTML = '<div style="font-size:0.8rem; color:#64748b;">Aucune question FAQ disponible.</div>';
            }
        });
}

function afficherReponseFAQLive(reponseHtml) {
    const box = document.getElementById('box-reponse-faq');
    box.innerHTML = '<strong>Réponse :</strong><br>' + reponseHtml;
    box.style.display = 'block';
    
    const body = document.getElementById('chat-live-body');
    body.scrollTop = body.scrollHeight;
}

function afficherFormulaireTicketLive() {
    formulaireTicketForceOuvert = true; // Verrouille l'affichage pour éviter la fermeture auto par le timer
    document.getElementById('form-ticket-live-wrapper').style.display = 'block';
    document.getElementById('compteur-char-live').style.display = 'inline-block';
    document.getElementById('input-ticket-live').focus();
}

function actualiserCompteurLive(input) {
    document.getElementById('compteur-char-live').textContent = input.value.length + '/150';
}

function verrouillerChampSaisie(verrouiller, messagePlaceholder = "Écrivez votre message...") {
    const input = document.getElementById('input-ticket-live');
    const btn = document.getElementById('btn-send-ticket-live');
    
    if (input && btn) {
        input.disabled = verrouiller;
        btn.disabled = verrouiller;
        btn.style.opacity = verrouiller ? '0.5' : '1';
        btn.style.cursor = verrouiller ? 'not-allowed' : 'pointer';
        input.placeholder = verrouiller ? "En attente de la réponse..." : messagePlaceholder;
    }
}

function soumettreTicketLive() {
    const input = document.getElementById('input-ticket-live');
    const txt = input.value.trim();

    if (!txt || input.disabled) return;

    const bodyData = new URLSearchParams();
    bodyData.append('action', 'creer_ticket');
    bodyData.append('question_texte', txt);
    if (ticketActifCode) {
        bodyData.append('code_ticket', ticketActifCode);
    }

    verrouillerChampSaisie(true);

    fetch('chat_live_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: bodyData
    })
    .then(res => res.json())
    .then(data => {
        if (data.succes) {
            ticketActifCode = data.code_ticket;
            localStorage.setItem('jevend_last_ticket', ticketActifCode);

            const body = document.getElementById('chat-live-body');
            let confirmBox = document.getElementById('ticket-confirmation-box');
            if (!confirmBox) {
                confirmBox = document.createElement('div');
                confirmBox.id = 'ticket-confirmation-box';
                confirmBox.style.cssText = "background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 10px; border-radius: 8px; font-size: 0.82rem; margin-top: 10px;";
                body.appendChild(confirmBox);
            }
            confirmBox.innerHTML = '<strong>✔ Message transmis !</strong> (Billet N° <strong style="color:#2563eb;">' + data.code_ticket + '</strong>)';
            
            input.value = '';
            actualiserCompteurLive(input);
            body.scrollTop = body.scrollHeight;

            if (modeActuelChat === 'statique') {
                formulaireTicketForceOuvert = false;
                document.getElementById('form-ticket-live-wrapper').style.display = 'none';
                document.getElementById('compteur-char-live').style.display = 'none';
            }

            demarrerEcouteTicketLive();
        } else {
            verrouillerChampSaisie(false);
            alert(data.message || 'Erreur lors de l\'envoi');
        }
    });
}

function verifierReponseTicketLive() {
    if (!ticketActifCode) return;

    const bodyData = new URLSearchParams();
    bodyData.append('action', 'verifier_reponse_ticket');
    bodyData.append('code_ticket', ticketActifCode);

    fetch('chat_live_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: bodyData
    })
    .then(res => res.json())
    .then(data => {
        if (data.succes) {
            const body = document.getElementById('chat-live-body');

            verrouillerChampSaisie(data.verrouille);

            if (data.statut === 'ferme') {
                if (ecouteTicketTimer) clearInterval(ecouteTicketTimer);
                localStorage.removeItem('jevend_last_ticket');
                ticketActifCode = null;
                formulaireTicketForceOuvert = false;

                verrouillerChampSaisie(false);

                const confirmBox = document.getElementById('ticket-confirmation-box');
                if (confirmBox) confirmBox.remove();

                const closedDiv = document.createElement('div');
                closedDiv.style.cssText = "background: #f1f5f9; color: #475569; padding: 10px; border-radius: 8px; font-size: 0.82rem; margin-top: 8px; border: 1px solid #cbd5e1;";
                closedDiv.innerHTML = '<strong>🔒 Discussion fermée par l\'administration.</strong>';
                body.appendChild(closedDiv);
                body.scrollTop = body.scrollHeight;

                if (modeActuelChat === 'statique') {
                    document.getElementById('form-ticket-live-wrapper').style.display = 'none';
                    document.getElementById('compteur-char-live').style.display = 'none';
                }
                return;
            }

            if (data.reponse_admin) {
                const confirmBox = document.getElementById('ticket-confirmation-box');
                if (confirmBox) confirmBox.remove();

                let repDiv = document.getElementById('box-reponse-admin-ticket');
                if (!repDiv) {
                    repDiv = document.createElement('div');
                    repDiv.id = 'box-reponse-admin-ticket';
                    repDiv.style.cssText = "background: #2563eb; color: #ffffff; padding: 10px 12px; border-radius: 8px; font-size: 0.84rem; margin-top: 8px; line-height: 1.4;";
                    body.appendChild(repDiv);
                }
                repDiv.innerHTML = '<strong>💬 Réponse de l\'Administration :</strong><br>' + data.reponse_admin;
                body.scrollTop = body.scrollHeight;
            }
        }
    });
}

function demarrerEcouteTicketLive() {
    if (ecouteTicketTimer) clearInterval(ecouteTicketTimer);
    ecouteTicketTimer = setInterval(verifierReponseTicketLive, 4000);
}

function synchroniserModeChatLive() {
    fetch('chat_live_ajax.php?action=get_mode_actuel')
        .then(res => res.json())
        .then(data => {
            if (data.succes) {
                modeActuelChat = data.mode_chat;
                const containerBadge = document.getElementById('badge-etat-live-container');
                const triggerBtn = document.getElementById('btn-chat-live-trigger');
                const win = document.getElementById('window-chat-live');
                const formWrapper = document.getElementById('form-ticket-live-wrapper');
                const btnToggleQuestion = document.getElementById('btn-toggle-question-form');
                const compteurChar = document.getElementById('compteur-char-live');

                if (modeActuelChat === 'off') {
                    if (triggerBtn) triggerBtn.style.display = 'none';
                    if (win) win.style.display = 'none';
                } else {
                    if (triggerBtn) triggerBtn.style.display = 'flex';
                    if (containerBadge) {
                        if (modeActuelChat === 'live') {
                            containerBadge.innerHTML = '<span style="color: #22c55e;">🟢</span> <strong>Admin en ligne</strong>';
                            if (formWrapper) formWrapper.style.display = 'block';
                            if (btnToggleQuestion) btnToggleQuestion.style.display = 'none';
                            if (compteurChar) compteurChar.style.display = 'inline-block';
                        } else {
                            containerBadge.innerHTML = '<span style="color: #f59e0b;">🟡</span> <strong>Assistant FAQ (Auto)</strong>';
                            if (btnToggleQuestion) btnToggleQuestion.style.display = 'inline-block';
                            
                            // Ne masque le champ QUE si le client n'a pas cliqué explicitement sur "Une question ?"
                            if (formWrapper && !ticketActifCode && !formulaireTicketForceOuvert) {
                                formWrapper.style.display = 'none';
                            }
                            if (compteurChar && !ticketActifCode && !formulaireTicketForceOuvert) {
                                compteurChar.style.display = 'none';
                            }
                        }
                    }
                }
            }
        });
}

synchroniserModeChatLive();
setInterval(synchroniserModeChatLive, 4000);

if (ticketActifCode) {
    demarrerEcouteTicketLive();
}
</script>
