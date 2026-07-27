<?php
// =============================================================================
// NOM DU SCRIPT : partials/_liste_envie.php
// REVISION : 2.0 - Prise en charge des Prix Spéciaux Vendeur (Promotions Flash)
// MODULE UNIQUE
// =============================================================================
?>
<div style="margin-top: 40px; border-top: 2px solid #e2e8f0; padding-top: 30px;">
    <h2 style="margin: 0 0 20px 0; color: #1e3a8a; font-size: 1.5rem;">Ma Liste d'Envie (Mes Favoris)</h2>

    <?php if (empty($liste_favoris)): ?>
        <div class="admin-bloc-vide">
            <p>Votre liste d'envie est vide.</p>
            <p style="font-size: 0.9rem; margin-top: 10px; color: #64748b;">
                Lorsque vous naviguerez sur les vitrines du site, cliquez sur le cœur d'un bien ou d'un service pour le retrouver instantanément ici.
            </p>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
            <?php foreach ($liste_favoris as $favori):
                // SÉCURITÉ : Si l'annonce originale a été supprimée de la dbase
                if (empty($favori['id_annonces'])): 
                ?>
                    <div class="form-bloc" style="max-width: 100%; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; padding: 20px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px;">
                        <div>
                            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: #94a3b8; margin-bottom: 10px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">
                                <span>📦 Inconnu</span>
                                <span style="color: #94a3b8; font-weight: bold;">🗑️ Retiré</span>
                            </div>
                            <h3 style="margin: 5px 0 10px 0; color: #64748b; font-size: 1.1rem; font-weight: bold; font-style: italic;">
                                Vitrine indisponible
                            </h3>
                            <p style="font-size: 0.85rem; color: #94a3b8; margin-bottom: 15px;">
                                Cet article ou ce service a été définitivement retiré par son vendeur.
                            </p>
                        </div>
                        <div style="border-top: 1px solid #e2e8f0; padding-top: 12px;">
                            <button disabled style="display: block; width: 100%; text-align: center; padding: 10px 12px; font-size: 0.9rem; font-weight: bold; background-color: #e2e8f0; color: #94a3b8; border: none; border-radius: 6px; cursor: not-allowed;">Indisponible</button>
                        </div>
                    </div>
                <?php 
                    continue;
                endif;

                // 1. CALCULS DU TEMPS RESTANT D'AFFICHAGE STANDARD
                $fomo_temps = "";
                $couleur_temps = "#475569";
                
                if (!empty($favori['date_creation']) && !empty($favori['date_expiration'])) {
                    try {
                        $date_cree = new DateTime($favori['date_creation']);
                        $date_expire = new DateTime($favori['date_expiration']);
                        $maintenant = new DateTime();
                        $intervalle_total = $date_cree->diff($date_expire)->days;
                        $jours_restants = $maintenant->diff($date_expire)->days;
                        
                        if ($maintenant > $date_expire) {
                            $fomo_temps = "🚨 Expiré";
                            $couleur_temps = "#dc2626";
                        } else {
                            $pourcentage = ($intervalle_total > 0) ? ($jours_restants / $intervalle_total) * 100 : 0;
                            if ($pourcentage <= 25) {
                                $fomo_temps = "🚨 Fin imminente ! (Plus que " . $jours_restants . " jours)";
                                $couleur_temps = "#dc2626";
                            } elseif ($pourcentage <= 50) {
                                $fomo_temps = "⏳ Dépêchez-vous ! (Plus que " . $jours_restants . " jours)";
                                $couleur_temps = "#d97706";
                            } else {
                                $fomo_temps = "🗓️ Reste " . $jours_restants . " jours d'affichage";
                            }
                        }
                    } catch (Exception $e) {
                        $fomo_temps = "🗓️ Durée d'affichage standard";
                    }
                } else {
                    $fomo_temps = "🗓️ Affichage en cours";
                }

                $autres_acheteurs = intval($favori['nb_envies'] ?? 1) - 1;

                // 2. DÉTECTION DU PRIX SPÉCIAL VENDEUR (PROMOTION FLASH)
                $a_promo = false;
                $prix_promo_affiche = 0;
                $temps_promo_texte = "";

                if (!empty($favori['prix_promo']) && !empty($favori['date_fin_promo'])) {
                    $dt_fin_p = new DateTime($favori['date_fin_promo']);
                    $dt_now_p = new DateTime();
                    if ($dt_now_p < $dt_fin_p) {
                        $a_promo = true;
                        $prix_promo_affiche = (float)$favori['prix_promo'];
                        $diff_p = $dt_now_p->diff($dt_fin_p);
                        $h_rest = ($diff_p->days * 24) + $diff_p->h;
                        $temps_promo_texte = "Reste " . $h_rest . "h " . $diff_p->i . "m !";
                    }
                }
                ?>
                <div class="form-bloc" style="max-width: 100%; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; padding: 20px; background: #ffffff; border: <?= $a_promo ? '2px solid #dc2626' : '1px solid #e2e8f0' ?>; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                    
                    <div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: #64748b; margin-bottom: 10px; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                            <span>📦 <?= htmlspecialchars($favori['categorie_nom'] ?? 'Sans catégorie') ?></span>
                            <span style="font-size: 0.8rem; color: #dc2626; font-weight: bold;">❤️ Favori</span>
                        </div>

                        <!-- BADGE DE PRIX SPÉCIAL VENDEUR SI PROMO EN COURS -->
                        <?php if ($a_promo): ?>
                            <div style="font-size: 0.85rem; font-weight: bold; color: #ffffff; background-color: #dc2626; padding: 8px 12px; border-radius: 6px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                                <span>🔥 PRIX SPÉCIAL VENDEUR !</span>
                                <span style="font-size: 0.78rem; background: rgba(255,255,255,0.2); padding: 2px 6px; border-radius: 4px;"><?= $temps_promo_texte ?></span>
                            </div>
                        <?php else: ?>
                            <div style="font-size: 0.8rem; font-weight: bold; color: <?= $couleur_temps ?>; background-color: <?= $couleur_temps === '#dc2626' ? '#fef2f2' : ($couleur_temps === '#d97706' ? '#fffbeb' : '#f8fafc') ?>; padding: 6px 10px; border-radius: 4px; margin-bottom: 12px; border-left: 3px solid <?= $couleur_temps ?>;">
                                <?= $fomo_temps ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($autres_acheteurs > 0): ?>
                            <div style="font-size: 0.8rem; color: #b45309; background-color: #fff7ed; border: 1px dashed #f97316; padding: 6px 10px; border-radius: 4px; margin-bottom: 12px;">
                                🔥 <strong><?= $autres_acheteurs ?> autre(s) acheteur(s)</strong> lorgne(nt) aussi sur cette vitrine !
                            </div>
                        <?php endif; ?>

                        <h3 style="margin: 5px 0 10px 0; color: #1e3a8a; font-size: 1.2rem; font-weight: bold; line-height: 1.3;">
                            <?= htmlspecialchars(stripslashes(html_entity_decode($favori['titre_objet_nettoye'] ?? 'Sans titre', ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8') ?>
                        </h3>

                        <!-- AFFICHAGE DES PRIX (AVEC OU SANS PROMO) -->
                        <div style="margin-bottom: 15px;">
                            <?php if ($a_promo): ?>
                                <del style="color: #94a3b8; font-size: 1rem; margin-right: 8px;">
                                    <?= number_format((float)$favori['prix'], 2, ',', ' ') ?> $
                                </del>
                                <span style="font-size: 1.4rem; font-weight: bold; color: #dc2626;">
                                    <?= number_format($prix_promo_affiche, 2, ',', ' ') ?> $
                                </span>
                            <?php else: ?>
                                <div style="font-size: 1.3rem; font-weight: bold; color: #16a34a;">
                                    <?= (isset($favori['prix']) && $favori['prix'] !== null) ? number_format((float)$favori['prix'], 2, ',', ' ') . ' $' : 'Prix sur demande' ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; margin-bottom: 15px; font-size: 0.85rem;">
                            
                            <div style="margin-bottom: 8px; color: #334155;">
                                👤 Vendeur : <?php if(!empty($favori['vendeur_nom'])): ?>
                                    <a href="store.php?id=<?= $favori['id_utilisateur'] ?>" style="font-weight: bold; color: #2563eb; text-decoration: none;"><strong><?= htmlspecialchars($favori['vendeur_nom']) ?></strong></a>
                                <?php else: ?>
                                    <span style="color:#94a3b8; font-style:italic;">Non spécifié</span>
                                <?php endif; ?>
                            </div>
                            
                            <div style="margin-bottom: 8px; color: #475569; display: flex; align-items: center;">
                                <?php 
                                try {
                                    if (!empty($favori['vendeur_ville_id']) && !empty($id_ville_acheteur)) {
                                        echo obtenirTexteDistance($bdd, $id_ville_acheteur, $favori['vendeur_ville_id'], $favori['vendeur_ville_nom'], $favori['id_utilisateur'], $id_utilisateur);
                                    } else {
                                        echo "📍 Région de " . htmlspecialchars($favori['vendeur_ville_nom'] ?? 'Matane');
                                    }
                                } catch (Throwable $e) {
                                    echo "📍 Région de " . htmlspecialchars($favori['vendeur_ville_nom'] ?? 'Matane');
                                }
                                ?>
                            </div>

                            <?php if (!empty($favori['vendeur_tel'])): ?>
                                <div style="display: flex; gap: 8px; margin-top: 10px;">
                                    <a href="tel:<?= preg_replace('/[^0-9]/', '', $favori['vendeur_tel']) ?>" style="flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 4px; text-decoration: none; background-color: #10b981; color: #ffffff; padding: 8px; border-radius: 4px; font-weight: bold; font-size: 0.8rem; text-align: center;">📞 Appeler</a>
                                    <a href="sms:<?= preg_replace('/[^0-9]/', '', $favori['vendeur_tel']) ?>?body=Bonjour, je suis intéresse par votre annonce <?= rawurlencode($favori['titre_objet_nettoye'] ?? '') ?> sur jevend.com !" style="flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 4px; text-decoration: none; background-color: #059669; color: #ffffff; padding: 8px; border-radius: 4px; font-weight: bold; font-size: 0.8rem; text-align: center;">💬 Texter</a>
                                </div>
                            <?php else: ?>
                                <div style="color: #94a3b8; font-size: 0.75rem; font-style: italic; margin-top: 10px;">📞 Aucun cellulaire configuré</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="border-top: 1px solid #e2e8f0; padding-top: 12px;">
                        <a href="details.php?id=<?= $favori['id_annonces'] ?>" style="display: block; width: 100%; text-align: center; padding: 10px 12px; font-size: 0.9rem; text-decoration: none; font-weight: bold; background-color: <?= $a_promo ? '#dc2626' : '#2563eb' ?>; color: #ffffff; border-radius: 6px; box-sizing: border-box;">👁️ Ouvrir la Vitrine Publique</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
