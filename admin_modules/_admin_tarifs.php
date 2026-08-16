<?php
// =============================================================================
// MODULE ADMIN : _admin_tarifs.php
// REVISION : 3.0 - Gestion des Tarifs + Sélecteur de Mode Paiement + Clés Stripe
// NOM DU SCRIPT : admin_modules/_admin_tarifs.php
// =============================================================================

if (!isset($_SESSION['id_utilisateur']) || $_SESSION['courriel'] !== 'douimet61@gmail.com') {
    exit('Accès refusé.');
}

$message_tarif = "";

// Traitement de la mise à jour globale (Tarifs + Configuration Stripe)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_maj_tarifs'])) {
    try {
        $bdd->beginTransaction();

        // 1. Mise à jour de la publicité régulière (Membres Ordinaires -> par jour)
        $stmt_reg = $bdd->prepare("UPDATE jevend_tarifs_publicites SET prix_par_jour = ?, duree_min_jours = ? WHERE type_produit = 'reguliere'");
        $stmt_reg->execute([
            (float)$_POST['prix_reguliere'], 
            (int)$_POST['min_reguliere']
        ]);

        // 2. Mise à jour des forfaits PRO (Comptes Marchands -> par mois)
        $stmt_pro = $bdd->prepare("UPDATE jevend_tarifs_pro SET prix_mensuel = ?, duree_max_mois = ?, date_mise_a_jour = CURRENT_TIMESTAMP WHERE type_forfait = ?");
        
        // Premium PRO
        $stmt_pro->execute([
            (float)$_POST['prix_pro_premium'], 
            (int)$_POST['max_pro_premium'], 
            'premium'
        ]);

        // Suprême PRO
        $stmt_pro->execute([
            (float)$_POST['prix_pro_supreme'], 
            (int)$_POST['max_pro_supreme'], 
            'supreme'
        ]);

        // 3. Mise à jour des Paramètres Système (Mode de paiement & Clés Stripe)
        $params_to_update = [
            'mode_paiement_pro' => $_POST['mode_paiement_pro'] ?? 'simulation',
            'stripe_pk_test'    => trim($_POST['stripe_pk_test'] ?? ''),
            'stripe_sk_test'    => trim($_POST['stripe_sk_test'] ?? ''),
            'stripe_pk_live'    => trim($_POST['stripe_pk_live'] ?? ''),
            'stripe_sk_live'    => trim($_POST['stripe_sk_live'] ?? '')
        ];

        $stmt_param = $bdd->prepare("
            INSERT INTO jevend_parametres (cle_parametre, valeur_parametre) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE valeur_parametre = VALUES(valeur_parametre)
        ");

        foreach ($params_to_update as $cle => $valeur) {
            $stmt_param->execute([$cle, $valeur]);
        }

        $bdd->commit();
        $message_tarif = "✅ Tous les tarifs et la configuration des paiements Stripe ont été enregistrés avec succès !";
    } catch (PDOException $e) {
        $bdd->rollBack();
        $message_tarif = "❌ Erreur de sauvegarde : " . $e->getMessage();
    }
}

// Extraction des tarifs actuels
$stmt_tarif_reg = $bdd->query("SELECT * FROM jevend_tarifs_publicites WHERE type_produit = 'reguliere'");
$t_reg = $stmt_tarif_reg->fetch(PDO::FETCH_ASSOC) ?: ['prix_par_jour' => 1.00, 'duree_min_jours' => 10];

$stmt_tarifs_pro = $bdd->query("SELECT * FROM jevend_tarifs_pro");
$tarifs_pro_raw = $stmt_tarifs_pro->fetchAll(PDO::FETCH_ASSOC);

$t_pro = [];
foreach ($tarifs_pro_raw as $row) {
    $t_pro[$row['type_forfait']] = $row;
}

// Extraction des paramètres système
$stmt_params = $bdd->query("SELECT cle_parametre, valeur_parametre FROM jevend_parametres");
$params_raw = $stmt_params->fetchAll(PDO::FETCH_KEY_PAIR);

$mode_paiement_actuel = $params_raw['mode_paiement_pro'] ?? 'simulation';
$stripe_pk_test       = $params_raw['stripe_pk_test'] ?? '';
$stripe_sk_test       = $params_raw['stripe_sk_test'] ?? '';
$stripe_pk_live       = $params_raw['stripe_pk_live'] ?? '';
$stripe_sk_live       = $params_raw['stripe_sk_live'] ?? '';
?>

<div class="admin-bloc-vide" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 25px; box-sizing: border-box;">
    <h2 style="margin-top: 0; color: #1e3a8a; display: flex; align-items: center; gap: 10px;">
        🏷️ Configuration des Tarifs & Passerelle de Paiement
    </h2>
    <p style="color: #64748b; font-size: 0.9rem; margin-top: -5px; margin-bottom: 20px;">
        Gérez indépendamment les tarifs journaliers, les forfaits mensuels marchands et les clés d'API Stripe.
    </p>

    <?php if (!empty($message_tarif)): ?>
        <div style="padding: 12px 15px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; font-size: 0.9rem; <?= strpos($message_tarif, '✅') !== false ? 'background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;' : 'background: #fef2f2; color: #991b1b; border: 1px solid #fecaca;' ?>">
            <?= htmlspecialchars($message_tarif) ?>
        </div>
    <?php endif; ?>

    <form action="panneau.php" method="POST">
        <input type="hidden" name="action_maj_tarifs" value="1">

        <!-- SECTION 1 : TARIFS MEMBRES PARTICULIERS / ORDINAIRES -->
        <div style="margin-bottom: 30px;">
            <h3 style="color: #0f172a; font-size: 1.1rem; border-bottom: 2px solid #cbd5e1; padding-bottom: 8px; margin-top: 0;">
                📣 1. Publicité Particulière (Table: <code>jevend_tarifs_publicites</code>)
            </h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 15px;">
                <div style="border: 1px solid #cbd5e1; border-radius: 8px; padding: 20px; background: #f8fafc;">
                    <h4 style="margin-top: 0; color: #334155; font-size: 1rem; margin-bottom: 12px;">
                        Bannière Régulière (Facturation journalière)
                    </h4>
                    <div style="margin-bottom: 12px;">
                        <label style="display: block; font-weight: bold; font-size: 0.85rem; color: #475569; margin-bottom: 4px;">Prix par jour ($ CAD) :</label>
                        <input type="number" step="0.05" min="0" name="prix_reguliere" value="<?= htmlspecialchars($t_reg['prix_par_jour'] ?? '1.00') ?>" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: bold; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: bold; font-size: 0.85rem; color: #475569; margin-bottom: 4px;">Durée minimale (jours) :</label>
                        <input type="number" min="1" name="min_reguliere" value="<?= htmlspecialchars($t_reg['duree_min_jours'] ?? '10') ?>" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 2 : TARIFS COMPTES MARCHANDS PRO -->
        <div style="margin-bottom: 30px;">
            <h3 style="color: #0f172a; font-size: 1.1rem; border-bottom: 2px solid #cbd5e1; padding-bottom: 8px;">
                🏢 2. Abonnements Marchands PRO (Table: <code>jevend_tarifs_pro</code>)
            </h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 15px;">
                
                <!-- FORFAIT PREMIUM PRO -->
                <div style="border: 2px solid #3b82f6; border-radius: 8px; padding: 20px; background: #ffffff; box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.1);">
                    <h4 style="margin-top: 0; color: #2563eb; font-size: 1rem; border-bottom: 2px solid #93c5fd; padding-bottom: 8px;">
                        ⚡ Forfait Premium PRO (Grille Pavés)
                    </h4>
                    <div style="margin-bottom: 12px;">
                        <label style="display: block; font-weight: bold; font-size: 0.85rem; color: #475569; margin-bottom: 4px;">Prix mensuel ($ CAD / mois) :</label>
                        <input type="number" step="0.50" min="0" name="prix_pro_premium" value="<?= htmlspecialchars($t_pro['premium']['prix_mensuel'] ?? '49.00') ?>" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: bold; box-sizing: border-box; color: #1e40af;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: bold; font-size: 0.85rem; color: #475569; margin-bottom: 4px;">Durée maximale du contrat (mois) :</label>
                        <input type="number" min="1" max="12" name="max_pro_premium" value="<?= htmlspecialchars($t_pro['premium']['duree_max_mois'] ?? '6') ?>" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                    </div>
                </div>

                <!-- FORFAIT SUPRÊME PRO -->
                <div style="border: 2px solid #7c3aed; border-radius: 8px; padding: 20px; background: #ffffff; box-shadow: 0 4px 6px -1px rgba(124, 58, 237, 0.1);">
                    <h4 style="margin-top: 0; color: #7c3aed; font-size: 1rem; border-bottom: 2px solid #c4b5fd; padding-bottom: 8px;">
                        👑 Forfait Suprême PRO (En-tête Carrousel)
                    </h4>
                    <div style="margin-bottom: 12px;">
                        <label style="display: block; font-weight: bold; font-size: 0.85rem; color: #475569; margin-bottom: 4px;">Prix mensuel ($ CAD / mois) :</label>
                        <input type="number" step="0.50" min="0" name="prix_pro_supreme" value="<?= htmlspecialchars($t_pro['supreme']['prix_mensuel'] ?? '129.00') ?>" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: bold; box-sizing: border-box; color: #581c87;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: bold; font-size: 0.85rem; color: #475569; margin-bottom: 4px;">Durée maximale du contrat (mois) :</label>
                        <input type="number" min="1" max="12" name="max_pro_supreme" value="<?= htmlspecialchars($t_pro['supreme']['duree_max_mois'] ?? '3') ?>" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                    </div>
                </div>

            </div>
        </div>

        <!-- SECTION 3 : CONFIGURATION DE LA PASSERELLE STRIPE -->
        <div style="margin-bottom: 30px; padding: 20px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px;">
            <h3 style="color: #0f172a; font-size: 1.1rem; border-bottom: 2px solid #94a3b8; padding-bottom: 8px; margin-top: 0; display: flex; align-items: center; gap: 8px;">
                💳 3. Moteur & Clés de Paiement Stripe (Table: <code>jevend_parametres</code>)
            </h3>

            <!-- CHOIX DU MODE -->
            <div style="margin-bottom: 20px; max-width: 450px;">
                <label for="mode_paiement_pro" style="display: block; font-weight: bold; font-size: 0.9rem; color: #1e293b; margin-bottom: 6px;">
                    Moteur de traitement actif sur le site :
                </label>
                <select name="mode_paiement_pro" id="mode_paiement_pro" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #64748b; font-weight: bold; background: #ffffff; color: #0f172a;">
                    <option value="simulation" <?= $mode_paiement_actuel === 'simulation' ? 'selected' : '' ?>>
                        🧪 Mode Simulation Local (Validation instantanée BDD)
                    </option>
                    <option value="stripe" <?= $mode_paiement_actuel === 'stripe' ? 'selected' : '' ?>>
                        🚀 Mode Production Stripe Réel (API Stripe Checkout)
                    </option>
                </select>
            </div>

            <!-- GRILLE DES CLÉS API STRIPE -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
                
                <!-- CLÉS DE TEST -->
                <div style="border: 1px dashed #f59e0b; background: #fffbeb; padding: 15px; border-radius: 6px;">
                    <h4 style="margin-top: 0; color: #b45309; font-size: 0.95rem; margin-bottom: 10px;">
                        🧪 Clés API Stripe (Environnement de TEST)
                    </h4>
                    <div style="margin-bottom: 10px;">
                        <label style="display: block; font-size: 0.8rem; font-weight: bold; color: #78350f;">Publishable Key (pk_test_...) :</label>
                        <input type="text" name="stripe_pk_test" value="<?= htmlspecialchars($stripe_pk_test) ?>" placeholder="pk_test_..." style="width: 100%; padding: 6px 10px; border: 1px solid #fcd34d; border-radius: 4px; font-family: monospace; font-size: 0.85rem; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: bold; color: #78350f;">Secret Key (sk_test_...) :</label>
                        <input type="password" id="sk_test_input" name="stripe_sk_test" value="<?= htmlspecialchars($stripe_sk_test) ?>" placeholder="sk_test_..." style="width: 100%; padding: 6px 10px; border: 1px solid #fcd34d; border-radius: 4px; font-family: monospace; font-size: 0.85rem; box-sizing: border-box;">
                    </div>
                </div>

                <!-- CLÉS EN DIRECT (PRODUCTION) -->
                <div style="border: 1px dashed #10b981; background: #ecfdf5; padding: 15px; border-radius: 6px;">
                    <h4 style="margin-top: 0; color: #047857; font-size: 0.95rem; margin-bottom: 10px;">
                        🚀 Clés API Stripe (Environnement LIVE / Prod)
                    </h4>
                    <div style="margin-bottom: 10px;">
                        <label style="display: block; font-size: 0.8rem; font-weight: bold; color: #065f46;">Publishable Key (pk_live_...) :</label>
                        <input type="text" name="stripe_pk_live" value="<?= htmlspecialchars($stripe_pk_live) ?>" placeholder="pk_live_..." style="width: 100%; padding: 6px 10px; border: 1px solid #6ee7b7; border-radius: 4px; font-family: monospace; font-size: 0.85rem; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: bold; color: #065f46;">Secret Key (sk_live_...) :</label>
                        <input type="password" id="sk_live_input" name="stripe_sk_live" value="<?= htmlspecialchars($stripe_sk_live) ?>" placeholder="sk_live_..." style="width: 100%; padding: 6px 10px; border: 1px solid #6ee7b7; border-radius: 4px; font-family: monospace; font-size: 0.85rem; box-sizing: border-box;">
                    </div>
                </div>

            </div>
        </div>

        <button type="submit" style="background-color: #16a34a; color: #ffffff; border: none; padding: 12px 24px; font-size: 1rem; font-weight: bold; border-radius: 6px; cursor: pointer; transition: background 0.2s ease;">
            💾 Enregistrer toutes les modifications
        </button>
    </form>
</div>
