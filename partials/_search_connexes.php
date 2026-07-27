<?php
// =============================================================================
// NOM DU SCRIPT : partials/_search_connexes.php
// REVISION : 1.3 - Prise en charge des suggestions dans d'autres régions/villes
// DESCRIPTION : Si une ville est sélectionnée, cherche 4 offres similaires hors de la ville.
// =============================================================================

$suggestions_annonces = [];
$titre_section_connexes = "💡 Ces articles pourraient aussi vous intéresser :";

try {
    if (!empty($recherche) || $cat_selectionnee > 0 || !empty($ville_selectionnee)) {
        $sql_sug = "
            SELECT a.id_annonces, a.titre_objet_nettoye, a.prix, a.image_courante, v.nom_ville AS vendeur_ville_nom
            FROM jevend_annonces a
            JOIN jevend_utilisateurs u ON a.id_utilisateur = u.id_utilisateur
            JOIN jevend_villes v ON u.id_ville = v.id_ville
            WHERE a.statut = 'actif'
        ";
        $params_sug = [];

        // SI UNE VILLE EST SÉLECTIONNÉE, ON CHERCHE DANS LES VILLES VOISINES
        if (!empty($ville_selectionnee) && $ville_selectionnee > 0) {
            $sql_sug .= " AND u.id_ville != :ville_exclue";
            $params_sug[':ville_exclue'] = $ville_selectionnee;
            $titre_section_connexes = "💡 Disponibles dans les villes voisines :";
        }

        if (!empty($recherche)) {
            $racine_recherche = mb_substr($recherche, 0, 4, 'UTF-8');
            $sql_sug .= " AND a.titre_objet_nettoye LIKE :racine";
            $params_sug[':racine'] = '%' . $racine_recherche . '%';
        }

        if ($cat_selectionnee > 0) {
            $sql_sug .= " AND a.id_categorie = :cat";
            $params_sug[':cat'] = $cat_selectionnee;
        }

        $sql_sug .= " ORDER BY RAND() LIMIT 4";
        $stmt_sug = $bdd->prepare($sql_sug);
        $stmt_sug->execute($params_sug);
        $suggestions_annonces = $stmt_sug->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {}
?>

<?php if (!empty($suggestions_annonces)): ?>
<div class="conteneur-recherches-connexes">
    <h4 class="titre-connexes"><?= htmlspecialchars($titre_section_connexes) ?></h4>
    <div class="grille-suggestions-objets">
        <?php foreach ($suggestions_annonces as $sug_obj): ?>
            <a href="details.php?id=<?= $sug_obj['id_annonces'] ?>" class="item-suggestion-objet">
                <?php if (!empty($sug_obj['image_courante']) && file_exists("uploads/" . $sug_obj['image_courante'])): ?>
                    <img src="uploads/<?= htmlspecialchars($sug_obj['image_courante']) ?>" alt="Mini">
                <?php else: ?>
                    <div style="width:40px;height:40px;background:#e2e8f0;display:flex;align-items:center;justify-content:center;border-radius:4px;font-size:0.8rem;">📸</div>
                <?php endif; ?>
                <div class="info-sug-texte">
                    <span class="titre-sug-obj"><?= htmlspecialchars($sug_obj['titre_objet_nettoye']) ?></span>
                    <span class="prix-sug-obj">
                        <?= number_format($sug_obj['prix'], 2, ',', ' ') ?> $
                        <small style="color:#64748b; font-weight:normal; margin-left:4px;">(📍 <?= htmlspecialchars($sug_obj['vendeur_ville_nom'] ?? '') ?>)</small>
                    </span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<style>
.conteneur-recherches-connexes {
    max-width: 850px;
    margin: 40px auto 20px auto;
    padding: 20px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
}
.titre-connexes {
    color: #334155;
    font-size: 0.95rem;
    margin: 0 0 12px 0;
    font-weight: bold;
}
.grille-suggestions-objets {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}
.item-suggestion-objet {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 8px 12px;
    color: #1e293b;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all 0.15s ease;
}
.item-suggestion-objet:hover {
    background: #e2e8f0;
    border-color: #2563eb;
}
.item-suggestion-objet img {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 4px;
    flex-shrink: 0;
}
.info-sug-texte {
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.titre-sug-obj {
    font-size: 0.85rem;
    font-weight: bold;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: #1e293b;
}
.prix-sug-obj {
    font-size: 0.8rem;
    color: #16a34a;
    font-weight: bold;
}

@media (max-width: 600px) {
    .grille-suggestions-objets { grid-template-columns: 1fr; }
}
</style>
