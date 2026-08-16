<!-- partials/_card.php -->
<?php
// =============================================================================
// NOM DU SCRIPT : partials/_card.php
// REVISION     : 1.8 - Prise en charge automatique des Prix Spéciaux (Vente Flash)
// DESCRIPTION  : Détection en direct de la validité de date_fin_promo.
//                Affichage du prix promo + badge VENTE FLASH si valide,
//                ou bascule automatique sur le prix régulier si expiré.
// =============================================================================

// Détection de l'ID réel en BDD avec fallback de sécurité
$id_annonce_affichage = $annonce['id_annonces'] ?? $annonce['id_annonce'] ?? $annonce['id'] ?? 'N/A';

// DÉTECTION EN DIRECT DU PRIX SPÉCIAL (VENTE FLASH)
$a_promo = false;
$prix_promo_affiche = 0;
$temps_promo_texte = "";

if (!empty($annonce['prix_promo']) && !empty($annonce['date_fin_promo'])) {
    try {
        $dt_fin_p = new DateTime($annonce['date_fin_promo']);
        $dt_now_p = new DateTime();
        if ($dt_now_p < $dt_fin_p) {
            $a_promo = true;
            $prix_promo_affiche = (float)$annonce['prix_promo'];
            $diff_p = $dt_now_p->diff($dt_fin_p);
            $h_rest = ($diff_p->days * 24) + $diff_p->h;
            $temps_promo_texte = "Reste " . $h_rest . "h " . $diff_p->i . "m !";
        }
    } catch (Exception $e) {
        $a_promo = false;
    }
}
?>
<div class="form-bloc" style="max-width: 100%; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; padding: 20px; margin-bottom: 20px; border: <?= $a_promo ? '2px solid #dc2626' : '1px solid #e2e8f0' ?>;">
    
    <div>
        <!-- En-tête de la carte : Categorie et Statut (Aéré) -->
        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; color: #64748b; margin-bottom: 10px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">
            <span>📦 <?php echo htmlspecialchars($annonce['categorie_nom'] ?? 'Général'); ?></span>
            <span>Statut : <strong style="color: <?php echo (($annonce['statut'] ?? '') === 'actif') ? '#16a34a' : '#dc2626'; ?>;"><?php echo htmlspecialchars($annonce['statut'] ?? 'inactif'); ?></strong></span>
        </div>

        <!-- Titre nettoyé avec badge ID intégré juste devant -->
        <h3 style="margin: 5px 0 10px 0; color: #1e293b; font-size: 1.15rem; font-weight: bold; line-height: 1.4;">
            <span style="display: inline-block; background-color: #e2e8f0; color: #334155; font-size: 0.75rem; font-weight: 700; padding: 2px 7px; border-radius: 4px; font-family: monospace; margin-right: 6px; vertical-align: middle;" title="ID Référence en Base de Données">
                #<?php echo htmlspecialchars((string)$id_annonce_affichage); ?>
            </span>
            <span style="vertical-align: middle;">
                <?php echo htmlspecialchars(stripslashes(html_entity_decode($annonce['titre_objet_nettoye'] ?? '', ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8'); ?>
            </span>
        </h3>

        <!-- Description nettoyée du double encodage -->
        <p style="font-size: 0.9rem; color: #475569; margin-bottom: 15px; line-height: 1.4;">
            <?php 
            $desc_decodee = stripslashes(html_entity_decode($annonce['description_service'] ?? '', ENT_QUOTES, 'UTF-8'));
            echo nl2br(htmlspecialchars(substr($desc_decodee, 0, 120))); 
            ?>...
        </p>

        <!-- Affichage du prix (Régulier ou Vente Flash) -->
        <div style="margin-bottom: 15px;">
            <?php if ($a_promo): ?>
                <div style="font-size: 0.8rem; font-weight: bold; color: #ffffff; background-color: #dc2626; padding: 4px 8px; border-radius: 4px; margin-bottom: 8px; display: inline-block;">
                    🔥 VENTE FLASH (<?= $temps_promo_texte ?>)
                </div>
                <div>
                    <del style="color: #94a3b8; font-size: 1rem; margin-right: 8px;">
                        <?= number_format((float)($annonce['prix'] ?? 0), 2, ',', ' ') ?> $
                    </del>
                    <span style="font-size: 1.3rem; font-weight: bold; color: #dc2626;">
                        <?= number_format($prix_promo_affiche, 2, ',', ' ') ?> $
                    </span>
                </div>
            <?php else: ?>
                <div style="font-size: 1.2rem; font-weight: bold; color: #2563eb;">
                    <?php echo (isset($annonce['prix']) && $annonce['prix'] !== null) ? number_format((float)$annonce['prix'], 2, ',', ' ') . ' $' : 'Prix sur demande / Service'; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- AJOUT DU MODULE FOMO -->
    <?php include 'partials/_indicateur_fomo.php';  // POUR LES LISTE D'ENVIE'?>

    <!-- MODULE DES STATISTIQUES ENTIÈREMENT RESTAURÉ -->
    <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; margin-bottom: 15px;">
        <div style="font-size: 0.8rem; font-weight: bold; color: #475569; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.05em;">
            📈 Performances de l'annonce :
        </div>
        <div style="display: flex; justify-content: space-between; text-align: center; font-size: 0.85rem;">
            <div>
                <div style="font-weight: bold; color: #1e293b;"><?php echo (int)($annonce['nb_vues_visiteurs'] ?? 0); ?></div>
                <div style="color: #64748b; font-size: 0.75rem;">Vues Public</div>
            </div>
            <div style="border-left: 1px solid #cbd5e1; height: 25px; margin: 0 5px;"></div>
            <div>
                <div style="font-weight: bold; color: #1e293b;"><?php echo (int)($annonce['nb_vues_membres'] ?? 0); ?></div>
                <div style="color: #64748b; font-size: 0.75rem;">Vues Membres</div>
            </div>
            <div style="border-left: 1px solid #cbd5e1; height: 25px; margin: 0 5px;"></div>
            <div>
                <div style="font-weight: bold; color: #2563eb;"><?php echo (int)($annonce['nb_clics_contact'] ?? 0); ?></div>
                <div style="color: #64748b; font-size: 0.75rem;">Clics Contact</div>
            </div>
        </div>
    </div>

    <!-- ZONE ANTI-ENVAHISSEMENT DU VENDEUR (BOUTON DE TRANQUILLITÉ) -->
    <div style="margin-bottom: 15px;" class="zone-action-vente">
        <?php if (isset($annonce['statut_vente']) && $annonce['statut_vente'] === 'vendu'): ?>
            <div style="background-color: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; text-align: center; padding: 8px; border-radius: 4px; font-weight: bold; font-size: 0.85rem;">
                🟥 Objet marqué comme VENDU
            </div>
        <?php else: ?>
            <button onclick="marquerObjetVendu(<?php echo (int)$id_annonce_affichage; ?>, this)" style="display: block; width: 100%; background-color: #dc2626; color: #ffffff; border: none; padding: 8px 12px; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 0.85rem; text-align: center; transition: background-color 0.2s;">
                🏷️ Marquer comme vendu
            </button>
        <?php endif; ?>
    </div>

    <!-- BOUTONS D'ACTIONS IMBRIQUÉS ET SYMÉTRIQUES (50/50) -->
    <div style="border-top: 1px solid #e2e8f0; padding-top: 12px; display: flex; gap: 10px;">
        <a href="modifier_annonce.php?id=<?php echo (int)$id_annonce_affichage; ?>" style="display: block; width: 50%; text-align: center; padding: 8px 12px; font-size: 0.85rem; text-decoration: none; font-weight: bold; background-color: #475569; color: #ffffff; border-radius: 4px; transition: background-color 0.2s;">
            ✏️ Modifier
        </a>
        <a href="retirer_annonce.php?id=<?php echo (int)$id_annonce_affichage; ?>" onclick="return confirm('Êtes-vous certain de vouloir retirer définitivement cette vitrine de la circulation ?');" style="display: block; width: 50%; text-align: center; padding: 8px 12px; font-size: 0.85rem; text-decoration: none; font-weight: bold; background-color: #dc2626; color: #ffffff; border-radius: 4px; transition: background-color 0.2s;">
            🗑️ Retirer
        </a>
    </div>

</div>
