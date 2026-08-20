<?php
// =============================================================================
// NOM DU SCRIPT : facture_pub.php
// REVISION : 1.6 - Facture isolée dédiée au client (sans interférence ticker global)
// =============================================================================
session_start();
require_once 'config.php';

// Fonction intelligente pour retrouver la racine (Local vs Prod)
function getBaseUrl() {
    $protocole = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $hote = $_SERVER['HTTP_HOST'];
    $dossier_script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $dossier_racine = str_replace('/admin_modules', '', $dossier_script);
    $dossier_racine = rtrim($dossier_racine, '/');
    return $protocole . $hote . $dossier_racine;
}

// 1. CHARGER LES PARAMÈTRES DEPUIS LA BDD (jevend_parametres)
$params = [];
try {
    $stmt_p = $bdd->query("SELECT cle_parametre, valeur_parametre FROM jevend_parametres");
    while ($row = $stmt_p->fetch(PDO::FETCH_ASSOC)) {
        $params[$row['cle_parametre']] = $row['valeur_parametre'];
    }
} catch (Exception $e) {
    $params['mode_paiement_pro'] = 'simulation';
}

$mode_paiement = $params['mode_paiement_pro'] ?? 'simulation';

$token = $_GET['token'] ?? '';
$erreur = "";
$pub = null;
$action_paiement = $_GET['paiement'] ?? '';

if (empty($token)) {
    $erreur = "Aucun jeton de sécurité valide n'a été fourni.";
} else {
    try {
        // Récupérer les informations du bandeau et du client via le token unique[cite: 3]
        $stmt = $bdd->prepare("
            SELECT b.*, c.nom_prenom, c.site_web, c.cel, c.tel 
            FROM jevend_bandeau_sponsorise b
            JOIN jevend_sponsorise_client c ON b.id_client = c.id_client
            WHERE b.token_paiement = ?
        ");
        $stmt->execute([$token]);
        $pub = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pub) {
            $erreur = "Ce lien de facturation est introuvable ou a expiré.";
        } else {
            // Si le paiement est validé par Stripe, on active la pub ![cite: 3]
            if ($action_paiement === 'succes' && $pub['statut'] === 'en_attente_paiement') {
                $stmt_act = $bdd->prepare("UPDATE jevend_bandeau_sponsorise SET statut = 'actif' WHERE id_bandeau = ?");
                $stmt_act->execute([$pub['id_bandeau']]);
                $pub['statut'] = 'actif';
            }
        }
    } catch (PDOException $e) {
        $erreur = "Erreur technique lors du chargement de la facture.";
    }
}

// 2. TRAITEMENT DU CLIC SUR LE BOUTON DE PAIEMENT (Appel réel à l'API Stripe)
if (isset($_POST['lancer_paiement']) && $pub) {
    
    if ($mode_paiement === 'live') {
        $stripe_secret_key = $params['stripe_sk_live'] ?? '';
    } else {
        $stripe_secret_key = $params['stripe_sk_test'] ?? '';
    }

    if (empty($stripe_secret_key)) {
        $erreur_paiement = "Erreur : Aucune clé secrète Stripe n'est configurée dans la base de données pour le mode " . $mode_paiement . ".";
    } else {
        $montant_centimes = (int)($pub['montant_paye'] * 100);
        $url_retour = getBaseUrl() . '/facture_pub.php?token=' . $token;

        $stripe_data = [
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'cad',
                    'unit_amount' => $montant_centimes,
                    'product_data' => [
                        'name' => 'Bandeau Publicitaire Jevend.com (' . $pub['nom_prenom'] . ')',
                    ],
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $url_retour . '&paiement=succes',
            'cancel_url' => $url_retour . '&paiement=annule',
            'metadata' => ['id_bandeau' => $pub['id_bandeau']]
        ];

        // Appel cURL vers l'API Stripe pour générer la session de paiement[cite: 3]
        $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $stripe_secret_key . ':');
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($stripe_data));
        $response = curl_exec($ch);
        curl_close($ch);

        $session_stripe = json_decode($response, true);

        if (isset($session_stripe['url'])) {
            header("Location: " . $session_stripe['url']);
            exit;
        } else {
            $erreur_paiement = "Impossible de contacter l'API Stripe. Vérifiez votre clé secrète de test dans la table.";
        }
    }
}

// Gestion des couleurs pour le rendu individuel de l'aperçu du client
$bg_colors = [
    'rouge' => 'linear-gradient(135deg, #dc2626, #b91c1c)',
    'bleu nuit' => 'linear-gradient(135deg, #1e3a8a, #172554)',
    'noir' => '#0f172a',
    'blanc' => '#ffffff'
];
$text_colors = [
    'blanc' => '#ffffff',
    'noir' => '#0f172a',
    'vert fluo' => '#4ade80'
];

$style_bg = $bg_colors[$pub['fond_couleur'] ?? 'rouge'] ?? '#dc2626';
$style_color = $text_colors[$pub['couleur_police'] ?? 'blanc'] ?? '#ffffff';
$border_style = (($pub['fond_couleur'] ?? '') === 'blanc') ? 'border: 1px solid #cbd5e1;' : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture Publicitaire — Jevend.com</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .facture-container {
            max-width: 750px;
            margin: 30px auto;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            padding: 30px;
        }
        .aperçu-bandeau-box {
            text-align: center;
            padding: 14px 20px;
            font-weight: bold;
            font-size: 1rem;
            border-radius: 6px;
            margin-top: 10px;
            margin-bottom: 15px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="admin-body" style="background-color: #f8fafc; padding: 20px; margin: 0;">

    <?php if ($erreur): ?>
        <div class="facture-container" style="text-align: center; color: #991b1b; background: #fef2f2; border-color: #fecaca;">
            <h3>⚠️ Oups ! Une erreur est survenue</h3>
            <p><?= htmlspecialchars($erreur) ?></p>
            <a href="index.php" style="display: inline-block; margin-top: 15px; background: #0f172a; color: #fff; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: bold;">← Retour à l'accueil</a>
        </div>
    <?php else: ?>

        <div class="facture-container">
            
            <!-- EN-TÊTE FACTURE -->
            <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 25px;">
                <div>
                    <h2 style="margin: 0; color: #0f172a; font-size: 1.5rem;">Jevend.com</h2>
                    <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.85rem;">Bon de commande et affichage publicitaire</p>
                </div>
                <div style="text-align: right;">
                    <?php if ($pub['statut'] === 'actif'): ?>
                        <span style="background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 4px; font-size: 0.8rem; font-weight: bold;">Statut : Payé & Actif</span>
                    <?php else: ?>
                        <span style="background: #fef9c3; color: #854d0e; padding: 4px 10px; border-radius: 4px; font-size: 0.8rem; font-weight: bold;">Statut : En attente de paiement</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- APERÇU INDIVIDUEL DU BANDEAU DE CE CLIENT -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 20px; border-radius: 8px; margin-bottom: 25px;">
                <h4 style="margin: 0 0 8px 0; color: #1e293b; font-size: 0.95rem;">👁️ Aperçu de votre bandeau publicitaire :</h4>
                <div class="aperçu-bandeau-box" style="background: <?= $style_bg ?>; color: <?= $style_color ?>; <?= $border_style ?>">
                    <?php if (!empty($pub['url_redirection'])): ?>
                        <a href="<?= htmlspecialchars($pub['url_redirection']) ?>" target="_blank" style="color: <?= $style_color ?>; text-decoration: underline;">
                            📢 <?= htmlspecialchars($pub['message']) ?>
                        </a>
                    <?php else: ?>
                        📢 <?= htmlspecialchars($pub['message']) ?>
                    <?php endif; ?>
                </div>
                <p style="margin: 0; font-size: 0.78rem; color: #64748b; text-align: center;">Ce message apparaîtra dynamiquement sur le site une fois le paiement validé.</p>
            </div>

            <!-- MESSAGE DE SUCCÈS SI PAYÉ -->
            <?php if ($pub['statut'] === 'actif'): ?>
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 15px; border-radius: 6px; text-align: center; margin-bottom: 25px; font-weight: bold;">
                    🎉 Paiement validé avec succès ! Votre publicité est désormais active et en ligne sur Jevend.com.
                </div>
            <?php endif; ?>

            <!-- COORDONNÉES DE L'ANNONCEUR -->
            <div style="margin-bottom: 25px; background: #f8fafc; padding: 15px; border-radius: 6px; border: 1px solid #e2e8f0;">
                <h4 style="margin: 0 0 8px 0; color: #1e293b; font-size: 0.95rem;">👤 Coordonnées de l'annonceur</h4>
                <div style="font-size: 0.9rem; color: #334155; line-height: 1.5;">
                    <strong><?= htmlspecialchars($pub['nom_prenom']) ?></strong><br>
                    <?php if (!empty($pub['cel'])): ?>Cellulaire : <?= htmlspecialchars($pub['cel']) ?><br><?php endif; ?>
                    <?php if (!empty($pub['site_web'])): ?>Site Web : <a href="<?= htmlspecialchars($pub['site_web']) ?>" target="_blank" style="color: #2563eb;"><?= htmlspecialchars($pub['site_web']) ?></a><br><?php endif; ?>
                </div>
            </div>

            <!-- TABLEAU RÉCAPITULATIF -->
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 25px; font-size: 0.9rem;">
                <thead>
                    <tr style="background: #f1f5f9; color: #334155; border-bottom: 1px solid #cbd5e1;">
                        <th style="padding: 10px; text-align: left;">Description du service</th>
                        <th style="padding: 10px; text-align: center;">Période de diffusion</th>
                        <th style="padding: 10px; text-align: right;">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-bottom: 1px solid #e2e8f0;">
                        <td style="padding: 12px; color: #0f172a;">
                            <strong>Emplacement Publicitaire Premium (Bandeau d'en-tête)</strong><br>
                            <span style="font-size: 0.8rem; color: #64748b;">Message : "<?= htmlspecialchars($pub['message']) ?>"</span>
                        </td>
                        <td style="padding: 12px; text-align: center; color: #475569; font-size: 0.85rem;">
                            Du <?= date('d/m/Y', strtotime($pub['date_debut'])) ?><br>au <?= date('d/m/Y', strtotime($pub['date_fin'])) ?>
                        </td>
                        <td style="padding: 12px; text-align: right; font-weight: bold; color: #16a34a; font-size: 1.1rem;">
                            <?= number_format((float)$pub['montant_paye'], 2, ',', ' ') ?> $
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- SECTION PAIEMENT STRIPE -->
            <?php if ($pub['statut'] !== 'actif'): ?>
                <div style="text-align: center; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 20px; border-radius: 8px;">
                    <h4 style="margin: 0 0 10px 0; color: #166534; font-size: 1.1rem;">💳 Règlement sécurisé via Stripe</h4>
                    
                    <?php if (isset($erreur_paiement)): ?>
                        <div style="color: #991b1b; background: #fef2f2; padding: 8px; border-radius: 4px; margin-bottom: 15px; font-size: 0.85rem;">
                            <?= htmlspecialchars($erreur_paiement) ?>
                        </div>
                    <?php endif; ?>

                    <p style="font-size: 0.88rem; color: #15803d; margin: 0 0 15px 0;">
                        Cliquez sur le bouton pour procéder au paiement de votre campagne publicitaire.
                    </p>

                    <form action="" method="POST">
                        <button type="submit" name="lancer_paiement" style="background-color: #16a34a; color: #fff; border: none; padding: 12px 25px; border-radius: 6px; font-weight: bold; font-size: 1rem; cursor: pointer;">
                            🔒 Payer <?= number_format((float)$pub['montant_paye'], 2, ',', ' ') ?> $ avec Stripe
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <div style="text-align: center; margin-top: 25px;">
                <a href="index.php" style="color: #64748b; text-decoration: none; font-size: 0.85rem; font-weight: bold;">
                    ← Retour au site Jevend.com
                </a>
            </div>
        </div>

    <?php endif; ?>

</body>
</html>
