<?php
// =============================================================================
// NOM DU SCRIPT : partials/_zone_recherche_liste.php
// DESCRIPTION  : Module d'affichage du listing des demandes de "La Zone Je Cherche"
// =============================================================================
?>
<div style="max-width: 1000px; margin: 0 auto 12px auto; padding: 0 15px; color: #64748b; font-size: 0.9rem; display: flex; justify-content: space-between; align-items: center;">
    <span>🎯 <strong><?= $total_recherches ?></strong> demande(s) active(s) en flux direct</span>
    <span style="font-size: 0.8rem; color: #94a3b8;">💡 Cliquez sur une ligne pour voir les détails</span>
</div>

<?php if (!empty($recherches)): ?>
    <div class="flux-compact-container">
        <?php foreach ($recherches as $index => $r): ?>
            <?php 
                $image_ref = !empty($r['image_reference']) && file_exists('uploads/' . $r['image_reference']) 
                    ? 'uploads/' . $r['image_reference'] 
                    : null;

                $dt_exp = new DateTime($r['date_expiration']);
                $diff = $maintenant->diff($dt_exp);
                $jours_restants = $diff->days;
            ?>
            <div class="ligne-demande-card">
                <!-- EN-TÊTE DE LA LIGNE (CLIQUABLE POUR DÉPLIER) -->
                <div class="ligne-entete-bar" onclick="toggleDetails(<?= $r['id_recherche'] ?>)">
                    <div class="ligne-infos-principales">
                        <span class="badge-cat-compact"><?= htmlspecialchars($r['nom_categorie']) ?></span>
                        <h3 class="titre-ligne-compact"><?= htmlspecialchars($r['titre_recherche']) ?></h3>
                    </div>

                    <div class="ligne-meta-droite">
                        <span class="badge-ville-compact">📍 <?= htmlspecialchars($r['nom_ville']) ?></span>
                        <div>
                            <?php if (!empty($r['budget_max']) && $r['budget_max'] > 0): ?>
                                <span class="badge-budget-compact"><?= number_format((float)$r['budget_max'], 2, ',', ' ') ?> $</span>
                            <?php else: ?>
                                <span style="font-size: 0.78rem; color: #64748b; font-weight: bold;">Budget ouvert</span>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn-toggle-deplier" id="btn-txt-<?= $r['id_recherche'] ?>">➕ Détails</button>
                    </div>
                </div>

                <!-- CONTENU DÉPLIABLE -->
                <div class="ligne-details-contenu" id="details-<?= $r['id_recherche'] ?>">
                    <div class="grille-details-interne">
                        <div class="vignette-apercu-ligne">
                            <?php if ($image_ref): ?>
                                <img src="<?= htmlspecialchars($image_ref) ?>" alt="Référence">
                            <?php else: ?>
                                <span style="font-size: 2.2rem;">🎯</span>
                            <?php endif; ?>
                        </div>

                        <div>
                            <div class="meta-auteur-Ligne" style="margin-bottom: 8px;">
                                <span style="color: #2563eb; font-weight: bold;">👤 Acheteur : <?= htmlspecialchars($r['nom_acheteur']) ?></span>
                                <span>•</span>
                                <span>🕒 Expire dans : <?= $jours_restants ?> jour(s)</span>
                            </div>
                            <div class="texte-description-detail">
                                <?= !empty($r['description']) ? htmlspecialchars($r['description']) : '<i>Aucune description détaillée fournie.</i>' ?>
                            </div>
                        </div>

                        <div>
                            <?php if ($id_utilisateur_connecte && (int)$id_utilisateur_connecte === (int)$r['id_utilisateur']): ?>
                                <span class="btn-repondre-compact" style="background-color: #e2e8f0; color: #94a3b8; cursor: not-allowed; display: inline-block; text-align: center; border: 1px solid #cbd5e1; opacity: 0.8;" title="C'est votre propre demande">
                                    🔒 Votre demande
                                </span>
                            <?php else: ?>
                                <a href="details_recherche.php?id=<?= $r['id_recherche'] ?>" class="btn-repondre-compact">
                                    💬 J'ai cet objet !
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div style="max-width: 800px; margin: 40px auto; background: #ffffff; border: 1px solid #cbd5e1; padding: 40px; border-radius: 8px; text-align: center; color: #64748b;">
        <div style="font-size: 3rem; margin-bottom: 10px;">🎯</div>
        <h3 style="color: #0f172a; margin: 0 0 8px 0;">Aucune demande ne correspond à vos critères</h3>
        <p style="margin: 0 0 20px 0; font-size: 0.95rem;">Soyez le premier à publier un besoin dans cette section !</p>
        <a href="poster_recherche.php" class="btn-banner-post">🎯 Poster une recherche</a>
    </div>
<?php endif; ?>
