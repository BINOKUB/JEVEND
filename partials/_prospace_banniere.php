<?php
// =============================================================================
// NOM DU SCRIPT : partials/_prospace_banniere.php
// REVISION     : 3.4 - Complet avec gestion des quotas globaux et messages d'affichage
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
if (isset($_GET['succes']) && $_GET['succes'] === 'banniere_ajoutee') {
    $msg_succes_url = "🎉 Félicitations ! Votre emplacement publicitaire a été réservé et votre visuel est en ligne.";
} elseif (isset($_GET['erreur'])) {
    if ($_GET['erreur'] === 'image_trop_petite') {
        $msg_alerte_pro = "❌ IMAGE TROP PETITE : Veuillez téléverser une image de meilleure qualité (au moins 800 px de large pour Suprême ou 400 px pour Premium).";
    } elseif ($_GET['erreur'] === 'format_image_invalide') {
        $msg_alerte_pro = "❌ FORMAT INVALIDE : Seuls les fichiers JPG, PNG et WEBP sont acceptés.";
    } elseif ($_GET['erreur'] === 'quota_atteint') {
        $msg_alerte_pro = "⚠️ Premier arrivé, premier vendu ! Un autre membre vient de s'attribuer le tout dernier emplacement disponible à la fraction de seconde près. Votre paiement n'a pas été prélevé / Surveillez les expirations pour saisir la prochaine opportunité.";
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

<!-- ======================================================================= -->
<!-- ONGLET 1 : FORMULAIRE D'ACHAT ET TÉLÉVERSEMENT -->
<!-- ======================================================================= -->
<div id="tab-achat" class="pro-tab-content actif">
    <div class="pro-box">
        <h2 style="margin-top: 0; color: #0f172a; font-size: 1.3rem;">
            🚀 Réserver un Emplacement Publicitaire Exclusif
        </h2>
        <p style="color: #64748b; font-size: 0.9rem; margin-top: -5px;">
            Téléversez votre visuel et renseignez le lien web vers lequel diriger les acheteurs lors d'un clic.
        </p>

        <?php if (!empty($msg_succes_url)): ?>
            <div style="background: #dcfce7; color: #166534; padding: 12px 15px; border-radius: 6px; font-weight: bold; margin-bottom: 20px; border: 1px solid #bbf7d0;">
                <?= $msg_succes_url ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($msg_alerte_pro)): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 12px 15px; border-radius: 6px; font-weight: bold; margin-bottom: 20px; border: 1px solid #fecaca;">
                <?= $msg_alerte_pro ?>
            </div>
        <?php endif; ?>

        <form action="traitement_paiement_pro.php" method="POST" enctype="multipart/form-data" id="form-pro-banniere">
            
            <div style="background: #f1f5f9; padding: 20px; border-radius: 8px; border: 1px solid #cbd5e1; margin-bottom: 25px;">
                <h3 style="margin-top: 0; color: #1e293b; font-size: 1rem;">1. Visuel Publicitaire & Lien Cible</h3>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: bold; font-size: 0.85rem; margin-bottom: 5px; color: #0f172a;">
                        🔗 URL de destination lors du clic (Site Web ou Page Facebook) * :
                    </label>
                    <input type="url" name="url_redirection" placeholder="Ex: https://www.mon-commerce.ca ou https://facebook.com/ma-page" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-weight: bold; color: #1e40af;">
                    <small style="color: #64748b; font-size: 0.75rem;">Les visiteurs qui cliquent sur votre bannière seront redirigés directement sur cette adresse.</small>
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: bold; font-size: 0.85rem; margin-bottom: 5px; color: #0f172a;">
                        📸 Image de votre bannière (JPG, PNG, WEBP) * :
                    </label>
                    <input type="file" name="image_banniere" id="input-image-banniere" accept="image/jpeg,image/png,image/webp" required style="width: 100%; padding: 8px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                    
                    <div style="margin-top: 8px; padding: 10px; background: #eff6ff; border-left: 4px solid #2563eb; border-radius: 4px; font-size: 0.8rem; color: #1e3a8a;">
                        💡 <strong>Optimisation automatique :</strong> Téléversez simplement votre visuel. Le serveur effectue le recadrage centré et l'optimisation HD automatiquement.
                    </div>
                </div>

                <div>
                    <label style="display: block; font-weight: bold; font-size: 0.85rem; margin-bottom: 5px; color: #0f172a;">
                        📝 Slogan ou court texte d'accroche (Facultatif) :
                    </label>
                    <input type="text" name="texte_banniere" placeholder="Ex: Rabais de 15% sur présentation de cette pub !" maxlength="120" style="width: 100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                </div>
            </div>

            <h3 style="margin-top: 0; color: #1e293b; font-size: 1rem;">2. Choisir l'Emplacement et le Calculateur de Durée</h3>

            <div class="pro-grid-forfaits">
                
                <!-- FORFAIT SUPRÊME -->
                <div class="card-forfait-pro" id="card-supreme" style="border-color: #7c3aed; transition: all 0.3s ease;">
                    <div>
                        <!-- CASE RADIO D'ACTIVATION DÉDIÉE -->
                        <div style="background: #f3e8ff; padding: 8px 12px; border-radius: 6px; margin-bottom: 12px; border: 1px solid #d8b4fe; display: flex; align-items: center; gap: 8px; cursor: pointer;" onclick="document.getElementById('radio-supreme').click()">
                            <input type="radio" name="choix_forfait" value="supreme" id="radio-supreme" onchange="gererSelectionForfait()" style="transform: scale(1.3); cursor: pointer;" required>
                            <label for="radio-supreme" style="font-weight: bold; color: #581c87; cursor: pointer; font-size: 0.95rem;">
                                👑 Activer le Forfait Suprême
                            </label>
                        </div>

                        <p style="font-size: 0.85rem; color: #64748b;">
                            Carrousel haut de page principal (3 slots max en circulation).
                        </p>
                        
                        <?php if ($nb_supreme_global >= 3): ?>
                            <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 6px; text-align: center; font-weight: bold; font-size: 0.85rem; margin: 20px 0; border: 1px solid #fecaca;">
                                ❌ ESPACE PUBLICITAIRE COMPLET<br>
                                <span style="font-size: 0.75rem; font-weight: normal;">Tous les emplacements Suprêmes (3/3) sont actuellement occupés. Premier arrivé, premier vendu !</span>
                            </div>
                        <?php else: ?>
                            <div style="background: #f3e8ff; padding: 12px; border-radius: 6px; margin: 15px 0; text-align: center;">
                                <div style="font-size: 0.8rem; color: #6b21a8; font-weight: bold; text-transform: uppercase;">Tarif Total du Bloc</div>
                                <div id="affichage-prix-supreme" style="font-size: 1.6rem; font-weight: 800; color: #581c87;">
                                    <?= number_format($prix_mensuel_supreme, 2, ',', ' ') ?> $
                                </div>
                                <small style="color: #7e22ce; font-size: 0.75rem;">(soit <?= number_format($prix_mensuel_supreme, 2, ',', ' ') ?> $ / mois)</small>
                            </div>
                            
                            <label style="display: block; font-weight: bold; font-size: 0.85rem; margin-bottom: 5px;">Durée du contrat :</label>
                            <select name="duree_bloc_supreme" id="select-duree-supreme" onchange="calculerPrixPro()" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #cbd5e1; margin-bottom: 15px; font-weight: bold;">
                                <option value="1">1 Mois</option>
                                <option value="2">2 Mois</option>
                                <option value="3">3 Mois (Maximum)</option>
                            </select>

                            <button type="submit" id="btn-submit-supreme" disabled style="width: 100%; padding: 12px; background: #7c3aed; color: #ffffff; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 0.95rem; opacity: 0.5;">
                                📤 Téléverser & Payer Suprême
                            </button>
                        <?php endif; ?>
                    </div>
                    <div style="font-size: 0.75rem; color: #64748b; text-align: right; margin-top: 10px;">
                        Actives globales : <strong><?= $nb_supreme_global ?> / 3</strong>
                    </div>
                </div>

                <!-- FORFAIT PREMIUM -->
                <div class="card-forfait-pro" id="card-premium" style="border-color: #2563eb; transition: all 0.3s ease;">
                    <div>
                        <!-- CASE RADIO D'ACTIVATION DÉDIÉE -->
                        <div style="background: #eff6ff; padding: 8px 12px; border-radius: 6px; margin-bottom: 12px; border: 1px solid #bfdbfe; display: flex; align-items: center; gap: 8px; cursor: pointer;" onclick="document.getElementById('radio-premium').click()">
                            <input type="radio" name="choix_forfait" value="premium" id="radio-premium" onchange="gererSelectionForfait()" style="transform: scale(1.3); cursor: pointer;" required>
                            <label for="radio-premium" style="font-weight: bold; color: #1e3a8a; cursor: pointer; font-size: 0.95rem;">
                                ⚡ Activer le Forfait Premium
                            </label>
                        </div>

                        <p style="font-size: 0.85rem; color: #64748b;">
                            Pavés rotatifs haute visibilité sous l'en-tête (20 slots max au total).
                        </p>
                        
                        <?php if ($nb_premium_global >= 20): ?>
                            <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 6px; text-align: center; font-weight: bold; font-size: 0.85rem; margin: 20px 0; border: 1px solid #fecaca;">
                                ❌ ESPACE PUBLICITAIRE COMPLET<br>
                                <span style="font-size: 0.75rem; font-weight: normal;">Tous les emplacements Premium du site (20/20) sont actuellement occupés.</span>
                            </div>
                        <?php elseif ($nb_premium_actifs >= 5): ?>
                            <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 6px; text-align: center; font-weight: bold; font-size: 0.85rem; margin: 20px 0; border: 1px solid #fecaca;">
                                ❌ Limite individuelle atteinte<br>
                                <span style="font-size: 0.75rem; font-weight: normal;">Vous possédez déjà 5/5 bannières Premium actives.</span>
                            </div>
                        <?php else: ?>
                            <div style="background: #eff6ff; padding: 12px; border-radius: 6px; margin: 15px 0; text-align: center;">
                                <div style="font-size: 0.8rem; color: #1e40af; font-weight: bold; text-transform: uppercase;">Tarif Total du Bloc</div>
                                <div id="affichage-prix-premium" style="font-size: 1.6rem; font-weight: 800; color: #1e3a8a;">
                                    <?= number_format($prix_mensuel_premium, 2, ',', ' ') ?> $
                                </div>
                                <small style="color: #1d4ed8; font-size: 0.75rem;">(soit <?= number_format($prix_mensuel_premium, 2, ',', ' ') ?> $ / mois)</small>
                            </div>

                            <label style="display: block; font-weight: bold; font-size: 0.85rem; margin-bottom: 5px;">Durée du contrat :</label>
                            <select name="duree_bloc_premium" id="select-duree-premium" onchange="calculerPrixPro()" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #cbd5e1; margin-bottom: 15px; font-weight: bold;">
                                <option value="1">1 Mois</option>
                                <option value="2">2 Mois</option>
                                <option value="3">3 Mois</option>
                                <option value="4">4 Mois</option>
                                <option value="5">5 Mois</option>
                                <option value="6">6 Mois (Maximum)</option>
                            </select>

                            <button type="submit" id="btn-submit-premium" disabled style="width: 100%; padding: 12px; background: #2563eb; color: #ffffff; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 0.95rem; opacity: 0.5;">
                                📤 Téléverser & Payer Premium
                            </button>
                        <?php endif; ?>
                    </div>
                    <div style="font-size: 0.75rem; color: #64748b; text-align: right; margin-top: 10px;">
                        Globales : <strong><?= $nb_premium_global ?> / 20</strong> | Vos actives : <strong><?= $nb_premium_actifs ?> / 5</strong>
                    </div>
                </div>

            </div>

        </form>
    </div>
</div>

<!-- ======================================================================= -->
<!-- ONGLET 2 : GESTION DES BANNIÈRES PRO EN CIRCULATION -->
<!-- ======================================================================= -->
<div id="tab-mes-bannieres" class="pro-tab-content">
    <div class="pro-box">
        <h3 style="margin-top: 0; color: #0f172a;">🖼️ Vos Emplacements Publicitaires</h3>
        
        <?php if (empty($bannieres_par_periode)): ?>
            <div style="text-align: center; padding: 40px 20px; color: #64748b;">
                <div style="font-size: 2.5rem; margin-bottom: 10px;">📢</div>
                <p style="font-size: 1rem; margin: 0;">Vous n'avez aucun bloc publicitaire pour le moment.</p>
                <small>Rendez-vous dans l'onglet "Réserver une bannière" pour lancer votre première campagne.</small>
            </div>
        <?php else: ?>
            <?php foreach ($bannieres_par_periode as $periode => $liste_bannieres): ?>
                <details class="accordeon-periode" open>
                    <summary class="accordeon-header">
                        <span>📅 Période : <strong><?= htmlspecialchars($periode) ?></strong></span>
                        <span class="badge-count"><?= count($liste_bannieres) ?> bannière(s)</span>
                    </summary>
                    <div class="accordeon-content">
                        <?php foreach ($liste_bannieres as $bann): ?>
                            <?php 
                                $date_debut  = isset($bann['date_debut']) ? new DateTime($bann['date_debut']) : new DateTime();
                                $date_fin    = isset($bann['date_fin']) ? new DateTime($bann['date_fin']) : new DateTime('+30 days');
                                
                                $date_butoir = (clone $date_fin)->modify('-10 days');
                                
                                $est_expire      = ($maintenant > $date_fin);
                                $peut_renouveler = ($maintenant >= $date_butoir && !$est_expire);
                                
                                $diff_interval  = $maintenant->diff($date_fin);
                                $jours_restants = $diff_interval->invert ? 0 : $diff_interval->days;

                                $fichier_image_existe = !empty($bann['image_url']) && file_exists($bann['image_url']);
                                $nb_clics = (int)($bann['nb_clics'] ?? 0);
                                $url_destination = $bann['url_redirection'] ?? '#';
                            ?>
                            <div class="card-banniere-active">
                                <div>
                                    <?php if ($fichier_image_existe): ?>
                                        <img src="<?= htmlspecialchars($bann['image_url']) ?>" alt="Bannière" class="preview-banniere-img">
                                    <?php else: ?>
                                        <div class="preview-banniere-img" style="display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 0.75rem; text-align: center; padding: 5px;">
                                            📦 Visuel Archivé<br><small>(Fichier purgé)</small>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div style="flex: 1; min-width: 240px;">
                                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                                        <span style="background: <?= $bann['type_banniere'] === 'supreme' ? '#7c3aed' : '#2563eb' ?>; color: #ffffff; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase;">
                                            BLOC <?= htmlspecialchars($bann['type_banniere'] ?? 'PRO') ?>
                                        </span>
                                        <strong style="color: #0f172a; font-size: 1rem;">
                                            Tarif : <?= number_format((float)($bann['prix_paye'] ?? 0), 2, ',', ' ') ?> $ CAD
                                        </strong>
                                    </div>

                                    <div style="font-size: 0.85rem; color: #475569; line-height: 1.6;">
                                        🔗 <strong>Destination du clic :</strong> <a href="<?= htmlspecialchars($url_destination) ?>" target="_blank" style="color: #2563eb; font-weight: bold; word-break: break-all;"><?= htmlspecialchars($url_destination) ?></a><br>
                                        📅 <strong>Date de début :</strong> <?= $date_debut->format('d/m/Y') ?><br>
                                        ⌛ <strong>Date d'échéance :</strong> <strong style="color: <?= $est_expire ? '#dc2626' : '#16a34a' ?>;"><?= $date_fin->format('d/m/Y') ?></strong> (<?= $est_expire ? 'Expiré' : $jours_restants . ' jours restants' ?>)<br>
                                        🖱️ <strong>Performance :</strong> <span style="color: #2563eb; font-weight: bold; background: #eff6ff; padding: 2px 8px; border-radius: 4px; border: 1px solid #bfdbfe; font-size: 0.8rem;"><?= $nb_clics ?> clic(s) enregistré(s)</span>
                                    </div>
                                </div>

                                <div>
                                    <?php if ($est_expire): ?>
                                        <span style="background: #fee2e2; color: #991b1b; padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; display: inline-block;">
                                            🔴 Expiré (Slot libéré)
                                        </span>
                                    <?php elseif ($peut_renouveler): ?>
                                        <a href="renouveler_pro.php?id=<?= $bann['id_banniere_pro'] ?>" style="background: #16a34a; color: #ffffff; padding: 10px 16px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 0.85rem; display: inline-block;">
                                            🔄 Renouveler en Priorité
                                        </a>
                                    <?php else: ?>
                                        <span style="background: #dcfce7; color: #166534; padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; display: inline-block;">
                                            🟢 Actif (Renouvellement dès le <?= $date_butoir->format('d/m/Y') ?>)
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </details>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- ACCORDÉON SEPARÉ : HISTORIQUE DES REÇUS ET PREUVES D'ACHAT (À VIE) -->
        <details class="accordeon-recus-pro" open>
            <summary>
                <span>🧾 Historique des reçus & preuves d'achat (<?= count($mes_recus_pro) ?>)</span>
                <span class="accordeon-icon">−</span>
            </summary>
            <div class="accordeon-contenu">
                <?php if (empty($mes_recus_pro)): ?>
                    <div style="padding: 15px; color: #64748b; font-style: italic; text-align: center;">
                        Aucun reçu ou preuve d'achat disponible dans votre historique pour le moment.
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto; width: 100%;">
                        <table class="table-recus-pro">
                            <thead>
                                <tr>
                                    <th>N° Transaction</th>
                                    <th>Date d'achat</th>
                                    <th>Emplacement / Description</th>
                                    <th>Durée</th>
                                    <th>Montant payé</th>
                                    <th>Statut de la transaction</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mes_recus_pro as $recu): ?>
                                    <?php 
                                        $date_recu = !empty($recu['date_achat']) ? date('Y-m-d H:i', strtotime($recu['date_achat'])) : date('Y-m-d H:i');
                                        $ref_recu = !empty($recu['no_transaction']) ? htmlspecialchars($recu['no_transaction']) : ("#PRO-" . str_pad((string)$recu['id_preuve'], 5, '0', STR_PAD_LEFT));
                                        $type_txt = strtoupper($recu['type_banniere'] ?? 'PRO');
                                        $duree_txt = ((int)($recu['duree_mois'] ?? 1)) . " mois";
                                        $prix_txt = number_format((float)($recu['prix_paye'] ?? 0), 2, ',', ' ') . " $ CAD";
                                        $desc_txt = !empty($recu['description_achat']) ? htmlspecialchars($recu['description_achat']) : '';
                                        $statut_txt = htmlspecialchars($recu['statut_paiement'] ?? 'Payé');
                                    ?>
                                    <tr>
                                        <td style="font-weight: bold; color: #0f172a; font-family: monospace;">
                                            <?= $ref_recu ?>
                                        </td>
                                        <td><?= $date_recu ?></td>
                                        <td>
                                            <strong>Forfait <?= $type_txt ?></strong>
                                            <?php if (!empty($desc_txt)): ?>
                                                <br><small style="color: #64748b; font-style: italic;">"<?= $desc_txt ?>"</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $duree_txt ?></td>
                                        <td style="font-weight: bold; color: #16a34a; font-size: 0.95rem;">
                                            <?= $prix_txt ?>
                                        </td>
                                        <td>
                                            <span style="background-color: #dcfce7; color: #15803d; padding: 3px 10px; border-radius: 4px; font-weight: bold; font-size: 0.78rem; border: 1px solid #bbf7d0; display: inline-block;">
                                                <?= $statut_txt ?> & Archivé (À vie)
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </details>

    </div>
</div>

<!-- ======================================================================= -->
<!-- ONGLET 3 : COORDONNÉES DU COMPTE, MODIFICATION DES URL ET SUPPORT ADMIN -->
<!-- ======================================================================= -->
<div id="tab-coordonnees" class="pro-tab-content">
    <div class="pro-box">
        <h3 style="margin-top: 0; color: #0f172a;">📇 Coordonnées du Compte & Configuration</h3>

        <?php if (!empty($msg_succes_url)): ?>
            <div style="background: #dcfce7; color: #166534; padding: 12px 15px; border-radius: 6px; font-weight: bold; margin-bottom: 20px; border: 1px solid #bbf7d0;">
                <?= $msg_succes_url ?>
            </div>
        <?php endif; ?>

        <div class="grid-coordonnees">
            
            <!-- 1. INFORMATIONS DU COMPTE MARCHAND -->
            <div class="card-info-box">
                <h4>🏢 Informations du Compte</h4>
                <div style="font-size: 0.9rem; color: #334155; line-height: 1.8;">
                    <strong>Nom d'entreprise / Commerçant :</strong><br>
                    <span style="color: #0f172a; font-weight: bold; font-size: 1rem;"><?= htmlspecialchars($compte['nom_entreprise'] ?? $compte['nom'] ?? 'Non renseigné') ?></span>
                    
                    <div style="margin-top: 10px;">
                        <strong>Courriel associé au compte :</strong><br>
                        <span style="color: #2563eb; font-weight: bold;"><?= htmlspecialchars($compte['courriel'] ?? 'Non renseigné') ?></span>
                    </div>

                    <div style="margin-top: 10px;">
                        <strong>Téléphone / Cellulaire enregistré :</strong><br>
                        <span style="color: #475569; font-weight: bold;"><?= htmlspecialchars($compte['cellulaire'] ?? $compte['telephone_pro'] ?? 'Non renseigné') ?></span>
                    </div>
                </div>
                <div style="margin-top: 15px; font-size: 0.75rem; color: #64748b; background: #ffffff; padding: 8px; border-radius: 4px; border: 1px solid #e2e8f0;">
                    ℹ️ Ces informations servent uniquement aux fins de facturation et de communication avec l'administration du site. Elles ne sont pas exposées publiquement.
                </div>
            </div>

            <!-- 2. MODIFICATION DE L'URL DE REDIRECTION DES BANNIÈRES -->
            <div class="card-info-box">
                <h4>🔗 Modifier la Redirection de vos Bannières</h4>
                <p style="font-size: 0.85rem; color: #64748b; margin-top: -5px;">
                    Mettez à jour l'adresse web (site internet ou page Facebook) vers laquelle rediriger les clients lors d'un clic sur vos publicités.
                </p>

                <?php if (empty($mes_bannieres_pro)): ?>
                    <p style="font-size: 0.85rem; color: #94a3b8; font-style: italic;">
                        Vous n'avez aucune bannière active à modifier pour le moment.
                    </p>
                <?php else: ?>
                    <?php foreach ($mes_bannieres_pro as $b_mod): ?>
                        <form action="" method="POST" style="background: #ffffff; padding: 12px; border-radius: 6px; border: 1px solid #cbd5e1; margin-bottom: 12px;">
                            <input type="hidden" name="action_update_url_banniere" value="1">
                            <input type="hidden" name="id_banniere_pro" value="<?= (int)$b_mod['id_banniere_pro'] ?>">

                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                <span style="font-weight: bold; font-size: 0.8rem; text-transform: uppercase; color: <?= $b_mod['type_banniere'] === 'supreme' ? '#7c3aed' : '#2563eb' ?>;">
                                    Bloc <?= htmlspecialchars($b_mod['type_banniere']) ?> #<?= $b_mod['id_banniere_pro'] ?>
                                </span>
                                <small style="color: #64748b; font-size: 0.75rem;">Échéance : <?= date('d/m/Y', strtotime($b_mod['date_fin'])) ?></small>
                            </div>

                            <div style="display: flex; gap: 8px;">
                                <input type="url" name="nouvelle_url_redirection" value="<?= htmlspecialchars($b_mod['url_redirection'] ?? '') ?>" placeholder="https://..." required style="flex: 1; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem; font-weight: bold; color: #1e40af;">
                                <button type="submit" style="background: #2563eb; color: #ffffff; border: none; padding: 8px 12px; border-radius: 4px; font-weight: bold; font-size: 0.8rem; cursor: pointer;">
                                    💾 Sauvegarder
                                </button>
                            </div>
                        </form>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- 3. ASSISTANCE ET CONTACTS ADMINISTRATEUR -->
            <div class="card-info-box" style="background: #0f172a; color: #ffffff;">
                <h4 style="color: #ffffff; border-bottom-color: #334155;">📞 Support & Administration</h4>
                <p style="font-size: 0.85rem; color: #cbd5e1;">
                    Besoin d'aide pour adapter votre visuel publicitaire ou pour toute question concernant votre abonnement marchand ?
                </p>

                <div style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1); margin-top: 15px;">
                    <div style="font-weight: bold; color: #38bdf8; font-size: 0.95rem; margin-bottom: 10px;">
                        👨‍💼 Administrateur du Réseau :
                    </div>
                    
                    <div style="font-size: 0.9rem; margin-bottom: 8px;">
                        📱 <strong>Cellulaire / SMS :</strong><br>
                        <a href="tel:4184299029" style="color: #ffffff; font-weight: bold; font-size: 1.1rem; text-decoration: none;">418-429-9029</a>
                    </div>

                    <div style="font-size: 0.9rem;">
                        ✉️ <strong>Courriel direct :</strong><br>
                        <a href="mailto:douimet61@gmail.com" style="color: #38bdf8; font-weight: bold; text-decoration: none;">douimet61@gmail.com</a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

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
