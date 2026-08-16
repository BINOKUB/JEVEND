<?php
// =============================================================================
// NOM DU SCRIPT : partials/_prospace_banniere.php
// REVISION     : 3.5 - Refactorisation avec inclusion modulaire des 3 onglets
// DESCRIPTION  : Interface PRO unifiée avec vérification des limites globales 
//                (3 Suprême, 20 Premium) et individuelles (5 Premium par membre).
// =============================================================================
if (!isset($_SESSION['id_utilisateur'])) { 
    exit(); 
}

// AUTO-INITIALISATION SÉCURISÉE DES DONNÉES SI NON TRANSMISES PAR LE PARENT
if (isset($bdd)) {
    $id_user = $_SESSION['id_utilisateur'];

    if (!isset($compte)) {
        $stmt_u = $bdd->prepare("SELECT * FROM jevend_utilisateurs WHERE id_utilisateur = ?");
        $stmt_u->execute([$id_user]);
        $compte = $stmt_u->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    if (!isset($tarifs_pro)) {
        $tarifs_pro = [];
        try {
            $stmt_t = $bdd->query("SELECT type_forfait, prix_mensuel FROM jevend_tarifs_pro");
            while ($t = $stmt_t->fetch(PDO::FETCH_ASSOC)) {
                $tarifs_pro[$t['type_forfait']] = $t['prix_mensuel'];
            }
        } catch (Exception $e) {
            $tarifs_pro = ['supreme' => 129.00, 'premium' => 49.00];
        }
    }

    if (!isset($mes_bannieres_pro)) {
        $stmt_b = $bdd->prepare("SELECT * FROM jevend_bannieres_actives_pro WHERE id_utilisateur = ? ORDER BY id_banniere_pro DESC");
        $stmt_b->execute([$id_user]);
        $mes_bannieres_pro = $stmt_b->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    if (!isset($mes_recus_pro)) {
        $stmt_r = $bdd->prepare("SELECT * FROM jevend_preuve_dachat WHERE id_utilisateur = ? ORDER BY id_preuve DESC");
        $stmt_r->execute([$id_user]);
        $mes_recus_pro = $stmt_r->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
} else {
    $compte = $compte ?? [];
    $tarifs_pro = $tarifs_pro ?? ['supreme' => 129.00, 'premium' => 49.00];
    $mes_bannieres_pro = $mes_bannieres_pro ?? [];
    $mes_recus_pro = $mes_recus_pro ?? [];
}

$msg_succes_url = "";
$msg_alerte_pro = "";

// Traitement des retours d'état (Succès / Erreur)
if (isset($_GET['succes'])) {
    if ($_GET['succes'] === 'banniere_ajoutee' || $_GET['succes'] === 'paiement_stripe_valide') {
        $msg_succes_url = "🎉 Félicitations ! Votre emplacement publicitaire a été réservé et votre paiement a été confirmé avec succès.";
    } elseif ($_GET['succes'] === 'renouvellement_effectue') {
        $msg_succes_url = "🔄 Votre bannière a été renouvelée avec succès ! La date d'échéance a été prolongée.";
    }
} elseif (isset($_GET['erreur'])) {
    if ($_GET['erreur'] === 'image_trop_petite') {
        $msg_alerte_pro = "❌ IMAGE TROP PETITE : Veuillez téléverser une image de meilleure qualité (au moins 800 px de large pour Suprême ou 400 px pour Premium).";
    } elseif ($_GET['erreur'] === 'format_image_invalide') {
        $msg_alerte_pro = "❌ FORMAT INVALIDE : Seuls les fichiers JPG, PNG et WEBP sont acceptés.";
    } elseif ($_GET['erreur'] === 'quota_atteint') {
        $msg_alerte_pro = "⚠️ Premier arrivé, premier vendu ! Tous les emplacements disponibles pour ce forfait sont occupés.";
    } elseif ($_GET['erreur'] === 'paiement_annule') {
        $msg_alerte_pro = "ℹ️ Transaction annulée. Votre carte n'a pas été débitée.";
    }
}

// --- TRAITEMENT DE LA MISE À JOUR D'UNE URL DE REDIRECTION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update_url_banniere'])) {
    $id_bann_mod = (int)$_POST['id_banniere_pro'];
    $nouvelle_url = trim($_POST['nouvelle_url_redirection'] ?? '');

    if (!empty($nouvelle_url) && strpos($nouvelle_url, 'http') !== 0) {
        $nouvelle_url = 'https://' . $nouvelle_url;
    }

    if ($id_bann_mod > 0 && !empty($nouvelle_url)) {
        try {
            $stmt_up_url = $bdd->prepare("UPDATE jevend_bannieres_actives_pro SET url_redirection = ? WHERE id_banniere_pro = ? AND id_utilisateur = ?");
            $stmt_up_url->execute([$nouvelle_url, $id_bann_mod, $_SESSION['id_utilisateur']]);
            $msg_succes_url = "✅ L'adresse de destination de votre bannière a été mise à jour avec succès !";

            $stmt_bann_pro = $bdd->prepare("SELECT * FROM jevend_bannieres_actives_pro WHERE id_utilisateur = ? ORDER BY id_banniere_pro DESC");
            $stmt_bann_pro->execute([$_SESSION['id_utilisateur']]);
            $mes_bannieres_pro = $stmt_bann_pro->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e_up) {
            $msg_succes_url = "❌ Erreur lors de la mise à jour : " . $e_up->getMessage();
        }
    }
}

// Prix mensuels indépendants lus depuis les tarifs sécurisés
$prix_mensuel_supreme = (float)($tarifs_pro['supreme'] ?? 129.00);
$prix_mensuel_premium = (float)($tarifs_pro['premium'] ?? 49.00);

// --- COMPTAGE GLOBAL DU SITE ET DES BANNIÈRES PERSONNELLES ---
$nb_supreme_actifs = 0;
$nb_premium_actifs = 0;
$maintenant = new DateTime();
$bannieres_par_periode = [];

if (!empty($mes_bannieres_pro)) {
    foreach ($mes_bannieres_pro as $b) {
        $date_fin_bann = isset($b['date_fin']) ? new DateTime($b['date_fin']) : new DateTime();
        
        if ($date_fin_bann >= $maintenant) {
            if (($b['type_banniere'] ?? '') === 'supreme') {
                $nb_supreme_actifs++;
            } elseif (($b['type_banniere'] ?? '') === 'premium') {
                $nb_premium_actifs++;
            }
        }

        $date_ref = isset($b['date_debut']) ? new DateTime($b['date_debut']) : (isset($b['date_creation']) ? new DateTime($b['date_creation']) : new DateTime());
        $cle_periode = $date_ref->format('Y-m');

        if (!isset($bannieres_par_periode[$cle_periode])) {
            $bannieres_par_periode[$cle_periode] = [];
        }
        $bannieres_par_periode[$cle_periode][] = $b;
    }

    krsort($bannieres_par_periode);
}

// Comptage global de tout le site (pour les limites maximales Suprême 3 et Premium 20)
$stmt_global_sup = $bdd->query("SELECT COUNT(*) FROM jevend_bannieres_actives_pro WHERE type_banniere = 'supreme' AND date_fin > NOW()");
$nb_supreme_global = (int)$stmt_global_sup->fetchColumn();

$stmt_global_prem = $bdd->query("SELECT COUNT(*) FROM jevend_bannieres_actives_pro WHERE type_banniere = 'premium' AND date_fin > NOW()");
$nb_premium_global = (int)$stmt_global_prem->fetchColumn();
?>

<!-- NAVIGATION PAR ONGLETS -->
<div class="pro-tabs-nav">
    <button class="pro-tab-btn actif" onclick="changerOngletPro('tab-achat')">🛒 Réserver une bannière</button>
    <button class="pro-tab-btn" onclick="changerOngletPro('tab-mes-bannieres')">🖼️ Vos Bannières (<?= count($mes_bannieres_pro) ?>)</button>
    <button class="pro-tab-btn" onclick="changerOngletPro('tab-coordonnees')">📇 Coordonnées & Support</button>
</div>

<!-- INCLUSION MODULAIRE DES 3 FICHIERS D'ONGLETS SÉPARÉS -->
<?php 
include __DIR__ . '/_prospace_banniere_onglet-1.php';
include __DIR__ . '/_prospace_banniere_onglet-2.php';
include __DIR__ . '/_prospace_banniere_onglet-3.php';
?>

<script>
const PRIX_MOIS_SUPREME = <?= $prix_mensuel_supreme ?>;
const PRIX_MOIS_PREMIUM = <?= $prix_mensuel_premium ?>;

function calculerPrixPro() {
    const selectSupreme = document.getElementById('select-duree-supreme');
    if (selectSupreme) {
        const dureeSupreme = parseInt(selectSupreme.value) || 1;
        const totalSupreme = PRIX_MOIS_SUPREME * dureeSupreme;
        document.getElementById('affichage-prix-supreme').innerText = totalSupreme.toFixed(2).replace('.', ',') + ' $';
    }

    const selectPremium = document.getElementById('select-duree-premium');
    if (selectPremium) {
        const dureePremium = parseInt(selectPremium.value) || 1;
        const totalPremium = PRIX_MOIS_PREMIUM * dureePremium;
        document.getElementById('affichage-prix-premium').innerText = totalPremium.toFixed(2).replace('.', ',') + ' $';
    }
}

function changerOngletPro(idTab) {
    document.querySelectorAll('.pro-tab-content').forEach(el => el.classList.remove('actif'));
    document.querySelectorAll('.pro-tab-btn').forEach(el => el.classList.remove('actif'));

    document.getElementById(idTab).classList.add('actif');
    
    const btnActif = Array.from(document.querySelectorAll('.pro-tab-btn')).find(b => b.getAttribute('onclick').includes(idTab));
    if (btnActif) btnActif.classList.add('actif');
}

function gererSelectionForfait() {
    const radioSupreme = document.getElementById('radio-supreme');
    const radioPremium = document.getElementById('radio-premium');

    const cardSupreme = document.getElementById('card-supreme');
    const cardPremium = document.getElementById('card-premium');

    const btnSupreme = document.getElementById('btn-submit-supreme');
    const btnPremium = document.getElementById('btn-submit-premium');

    if (radioSupreme && radioSupreme.checked) {
        if (btnSupreme) {
            btnSupreme.disabled = false;
            btnSupreme.style.opacity = '1';
        }
        if (btnPremium) {
            btnPremium.disabled = true;
            btnPremium.style.opacity = '0.3';
        }
        if (cardSupreme) cardSupreme.style.opacity = '1';
        if (cardPremium) cardPremium.style.opacity = '0.5';
    } else if (radioPremium && radioPremium.checked) {
        if (btnPremium) {
            btnPremium.disabled = false;
            btnPremium.style.opacity = '1';
        }
        if (btnSupreme) {
            btnSupreme.disabled = true;
            btnSupreme.style.opacity = '0.3';
        }
        if (cardPremium) cardPremium.style.opacity = '1';
        if (cardSupreme) cardSupreme.style.opacity = '0.5';
    }
}

document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll('.accordeon-recus-pro').forEach(acc => {
        acc.addEventListener('toggle', function() {
            const icon = this.querySelector('.accordeon-icon');
            if (icon) icon.textContent = this.open ? '−' : '+';
        });
    });
});
</script>
