<?php
// =============================================================================
// NOM DU SCRIPT : partials/_search_annonce.php
// REVISION : 1.3 - Habillage Prestige Bronze avec 5 étoiles pour les annonces sponsorisées
// DESCRIPTION : Contour bronze 2px, fond teinté doré, 5 étoiles bronze en haut à droite.
// =============================================================================

$maintenant = new DateTime();
?>
<div class="liste-resultats-container">
    <?php foreach ($resultats as $annonce): ?>
        <?php 
            $fichier_image = !empty($annonce['image_courante']) ? $annonce['image_courante'] : '';
            $chemin_complet_image = "uploads/" . $fichier_image; 
            $est_sponsorise = isset($annonce['a_banniere']) && $annonce['a_banniere'] == 1;

            // DÉTECTION EN DIRECT DU PRIX SPÉCIAL (VENTE FLASH)
            $a_promo = false;
            $prix_promo_affiche = 0;
            $temps_promo_texte = "";

            if (!empty($annonce['prix_promo']) && !empty($annonce['date_fin_promo'])) {
                try {
                    $dt_fin_p = new DateTime($annonce['date_fin_promo']);
                    if ($maintenant < $dt_fin_p) {
                        $a_promo = true;
                        $prix_promo_affiche = (float)$annonce['prix_promo'];
                        $diff_p = $maintenant->diff($dt_fin_p);
                        $h_rest = ($diff_p->days * 24) + $diff_p->h;
                        $temps_promo_texte = "Reste " . $h_rest . "h " . $diff_p->i . "m !";
                    }
                } catch (Exception $e) { $a_promo = false; }
            }

            // CONFIGURATION DU STYLE ET DU CONTOUR
            $style_carte = "position: relative;";
            if ($a_promo) {
                $style_carte .= " border: 2px solid #dc2626;";
            } elseif ($est_sponsorise) {
                $style_carte .= " border: 2px solid #b45309; background-color: #fffbf0;";
            }
        ?>
        <div class="item-resultat-ligne" style="<?= $style_carte ?>">
            
            <!-- ÉTOILES BRONZE PRESTIGE DANS LE COIN SUPÉRIEUR DROIT -->
            <?php if ($est_sponsorise): ?>
                <div style="position: absolute; top: 6px; right: 12px; font-size: 0.75rem; letter-spacing: 2px; color: #b45309; font-weight: bold; user-select: none;" title="Annonce Prestige Sponsorisée">
                    ⭐⭐⭐⭐⭐
                </div>
            <?php endif; ?>

            <?php if(!empty($annonce['image_courante']) && file_exists($chemin_complet_image)): ?>
                <div class="vignette-recherche-zone">
                    <?php if (isset($annonce['statut_vente']) && $annonce['statut_vente'] === 'vendu'): ?>
                        <div class="mini-badge-vendu-search">🔴 VENDU</div>
                    <?php endif; ?>
                    <img src="<?= htmlspecialchars($chemin_complet_image) ?>" alt="Aperçu">
                </div>
            <?php endif; ?>
            
            <div class="info-resultat-corps">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <h3 class="titre-resultat-annonce" title="<?= htmlspecialchars($annonce['titre_objet_nettoye']) ?>">
                        <?= htmlspecialchars($annonce['titre_objet_nettoye']) ?>
                    </h3>
                    <?php if ($est_sponsorise): ?>
                        <span style="font-size: 0.65rem; font-weight: bold; background: #b45309; color: #ffffff; padding: 2px 6px; border-radius: 4px; text-transform: uppercase;">🚀 PRESTIGE</span>
                    <?php endif; ?>
                </div>

                <div class="meta-resultat-ligne">
                    <span style="font-weight: bold; color: #2563eb;">👤 <?= htmlspecialchars($annonce['vendeur_nom']) ?></span>
                    • <?= obtenirTexteDistance($bdd, $id_ville_acheteur, $annonce['vendeur_ville_id'], $annonce['vendeur_ville_nom'], $annonce['id_utilisateur'], $id_utilisateur_connecte) ?>
                    • 🕒 <?= date('d M', strtotime($annonce['date_creation'])) ?>
                </div>

                <?php if ($a_promo): ?>
                    <div style="font-size: 0.72rem; font-weight: bold; color: #ffffff; background-color: #dc2626; padding: 2px 6px; border-radius: 4px; margin-top: 4px; display: inline-block; width: fit-content;">
                        🔥 VENTE FLASH (<?= $temps_promo_texte ?>)
                    </div>
                <?php endif; ?>
            </div>

            <div class="action-resultat-droite" style="<?= $est_sponsorise ? 'padding-top: 10px;' : '' ?>">
                <div class="prix-resultat">
                    <?php if ($a_promo): ?>
                        <del style="color: #94a3b8; font-size: 0.85rem; margin-right: 6px; font-weight: normal;">
                            <?= number_format((float)$annonce['prix'], 2, ',', ' ') ?> $
                        </del>
                        <span style="color: #dc2626; font-weight: bold;"><?= number_format($prix_promo_affiche, 2, ',', ' ') ?> $</span>
                    <?php else: ?>
                        <?= number_format((float)$annonce['prix'], 2, ',', ' ') ?> $
                    <?php endif; ?>
                </div>
                <a href="details.php?id=<?= $annonce['id_annonces'] ?>" class="btn-action" style="margin:0; padding:6px 12px; font-size:0.8rem; text-decoration:none; width:auto; <?= $a_promo ? 'background-color:#dc2626;' : ($est_sponsorise ? 'background-color:#b45309;' : '') ?>">👁️ Vitrine</a>
            </div>
        </div>
    <?php endforeach; ?>
</div>
