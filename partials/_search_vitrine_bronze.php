<?php
// =============================================================================
// NOM DU SCRIPT : partials/_search_vitrine_bronze.php
// REVISION : 1.5 - Liaison stricte via jevend_bannieres_actives et id_annonce
// =============================================================================

$resultats_bronze = [];

try {
    // On interroge DIRECTEMENT les campagnes actives enregistrées pour le type 'bronze'
    $sql_bronze = "
        SELECT b.id_banniere, b.texte_banniere, 
               a.id_annonces, a.titre_objet_nettoye, a.prix, a.image_courante,
               u.nom AS vendeur_nom
        FROM jevend_bannieres_actives b
        JOIN jevend_utilisateurs u ON b.id_utilisateur = u.id_utilisateur
        JOIN jevend_annonces a ON b.id_annonce = a.id_annonces
        WHERE b.statut_affichage = 'active' 
          AND b.type_banniere = 'bronze'
          AND a.statut = 'actif'
          AND (a.statut_vente IS NULL OR a.statut_vente != 'vendu')
    ";

    $params_b = [];
    if (!empty($cat_selectionnee) && $cat_selectionnee > 0) {
        $sql_bronze .= " AND a.id_categorie = :cat_b";
        $params_b[':cat_b'] = $cat_selectionnee;
    }

    $sql_bronze .= " ORDER BY RAND() LIMIT 4";

    $stmt_bronze = $bdd->prepare($sql_bronze);
    $stmt_bronze->execute($params_b);
    $resultats_bronze = $stmt_bronze->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Silence en cas d'erreur
}
?>

<?php if (!empty($resultats_bronze)): ?>
    <div style="max-width: 850px; margin: 0 auto;">
        <h4 style="color: #64748b; margin: 0 0 10px 0; font-size: 0.9rem;">📌 Vitrines Bronze commanditées</h4>
        
        <div class="grille-vitrine-bronze">
            <?php foreach ($resultats_bronze as $bronze): ?>
                <?php 
                    $img_bronze = !empty($bronze['image_courante']) ? "uploads/" . $bronze['image_courante'] : ''; 
                    $titre_affichage = !empty($bronze['texte_banniere']) ? $bronze['texte_banniere'] : $bronze['titre_objet_nettoye'];
                ?>
                <a href="details.php?id=<?= $bronze['id_annonces'] ?>" class="carte-bronze-lien">
                    <div class="carte-bronze">
                        <div class="carte-bronze-img-container">
                            <span class="badge-bronze-sponsor">BRONZE</span>
                            <?php if (!empty($bronze['image_courante']) && file_exists($img_bronze)): ?>
                                <img src="<?= htmlspecialchars($img_bronze) ?>" alt="Aperçu">
                            <?php else: ?>
                                <div style="font-size: 2rem; color: #cbd5e1;">📸</div>
                            <?php endif; ?>
                        </div>
                        <div class="carte-bronze-info">
                            <h4 class="carte-bronze-titre" title="<?= htmlspecialchars($titre_affichage) ?>">
                                <?= htmlspecialchars($titre_affichage) ?>
                            </h4>
                            <div class="carte-bronze-prix"><?= number_format($bronze['prix'], 2, ',', ' ') ?> $</div>
                            <div class="carte-bronze-vendeur">👤 <?= htmlspecialchars($bronze['vendeur_nom']) ?></div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        
        <hr style="border: 0; height: 1px; background: #e2e8f0; margin-bottom: 25px;">
    </div>
<?php endif; ?>
