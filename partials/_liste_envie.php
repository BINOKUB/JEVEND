<?php
// =============================================================================
// NOM DU SCRIPT : partials/_liste_envie.php
// REVISION : 3.0 - Affichage compact et hiérarchisé par catégories pliées (Accordéon)
// =============================================================================

// Regroupement des favoris par catégorie
$favoris_par_categorie = [];
if (!empty($liste_favoris)) {
    foreach ($liste_favoris as $favori) {
        $nom_cat = !empty($favori['categorie_nom']) ? $favori['categorie_nom'] : 'Autres catégories';
        $favoris_par_categorie[$nom_cat][] = $favori;
    }
    // Trier les catégories par ordre alphabétique
    ksort($favoris_par_categorie);
}
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
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach ($favoris_par_categorie as $nom_categorie => $items_favoris): ?>
                
                <!-- ACCORDÉON DE CATÉGORIE (Plié par défaut) -->
                <details style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                    <summary style="padding: 14px 18px; font-weight: bold; color: #1e293b; background: #f8fafc; cursor: pointer; display: flex; justify-content: space-between; align-items: center; user-select: none;">
                        <span>📁 <?= htmlspecialchars($nom_categorie) ?></span>
                        <span style="background: #e2e8f0; color: #334155; padding: 2px 10px; border-radius: 12px; font-size: 0.8rem;">
                            <?= count($items_favoris) ?> article(s)
                        </span>
                    </summary>

                    <div style="padding: 10px 15px; display: flex; flex-direction: column; gap: 8px; background: #ffffff;">
                        <?php foreach ($items_favoris as $favori): ?>
                            <?php 
                            // SÉCURITÉ : Annonce supprimée de la base
                            if (empty($favori['id_annonces'])): 
                            ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 6px; font-size: 0.85rem;">
                                    <span style="color: #94a3b8; font-style: italic;">📦 Vitrine indisponible (Supprimée par le vendeur)</span>
                                    <span style="color: #dc2626; font-weight: bold;">Indisponible</span>
                                </div>
                                <?php continue; endif; ?>

                            <?php 
                            // Analyse du statut et des prix
                            $statut_annonce = $favori['statut'] ?? 'actif';
                            $est_vendu = ($statut_annonce === 'vendu' || ($favori['statut_vente'] ?? '') === 'vendu');
                            
                            $prix_actuel = (float)($favori['prix'] ?? 0);
                            $titre_annonce = htmlspecialchars(stripslashes(html_entity_decode($favori['titre_objet_nettoye'] ?? 'Sans titre', ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8');
                            
                            // Formattage de la date d'ajout dans les favoris
                            $date_ajout = !empty($favori['date_ajout']) ? date('Y-m-d', strtotime($favori['date_ajout'])) : '';
                            ?>

                            <!-- LIGNE DE L'ITEM COMPACT -->
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; gap: 15px; flex-wrap: wrap;">
                                
                                <!-- Infos principales -->
                                <div style="flex: 1; min-width: 220px; display: flex; flex-direction: column; gap: 2px;">
                                    <a href="details.php?id=<?= $favori['id_annonces'] ?>" style="font-weight: bold; color: #1e3a8a; text-decoration: none; font-size: 0.95rem;">
                                        <?= $titre_annonce ?>
                                    </a>
                                    <span style="font-size: 0.78rem; color: #64748b;">
                                        Ajouté le : <?= $date_ajout ?> | Vendeur : <strong><?= htmlspecialchars($favori['vendeur_nom'] ?? 'Inconnu') ?></strong>
                                    </span>
                                </div>

                                <!-- Prix & Statut -->
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <div style="text-align: right;">
                                        <span style="font-weight: bold; color: #16a34a; font-size: 0.95rem;">
                                            <?= $prix_actuel > 0 ? number_format($prix_actuel, 2, ',', ' ') . ' $' : 'Sur demande' ?>
                                        </span>
                                    </div>

                                    <div>
                                        <?php if ($est_vendu): ?>
                                            <span style="background: #fee2e2; color: #991b1b; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold;">🔴 Vendu</span>
                                        <?php else: ?>
                                            <span style="background: #dcfce7; color: #15803d; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold;">🟢 Disponible</span>
                                        <?php endif; ?>
                                    </div>

                                    <div>
                                        <a href="details.php?id=<?= $favori['id_annonces'] ?>" style="background: #2563eb; color: #ffffff; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 0.8rem; white-space: nowrap;">
                                            👁️ Voir
                                        </a>
                                    </div>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>
                </details>

            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
