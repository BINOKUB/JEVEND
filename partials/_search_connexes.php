<?php
// =============================================================================
// NOM DU SCRIPT : partials/_search_connexes.php
// REVISION     : 2.0 - Filtrage régional strict / Global + Exclusion d'IDs + Bannières d'incitation
// DESCRIPTION  : Gère l'affichage des suggestions connexes ou d'une bannière publicitaire aléatoire.
// =============================================================================

$suggestions_annonces = [];
$titre_section_connexes = "💡 Ces articles pourraient aussi vous intéresser :";
$afficher_banniere_incitation = false;
$fichier_banniere_a_afficher = '';

try {
    if (!empty($recherche) || ($cat_selectionnee ?? 0) > 0 || !empty($ville_selectionnee)) {
        
        // 1. Requête de base pour les suggestions
        $sql_sug = "
            SELECT a.id_annonces, a.titre_objet_nettoye, a.prix, a.image_courante, v.nom_ville AS vendeur_ville_nom
            FROM jevend_annonces a
            JOIN jevend_utilisateurs u ON a.id_utilisateur = u.id_utilisateur
            JOIN jevend_villes v ON u.id_ville = v.id_ville
            WHERE a.statut = 'actif'
        ";
        $params_sug = [];

        // 2. EXCLUSION SÉCURISÉE DES ANNONCES DÉJÀ AFFICHÉES DANS LA PAGE PRINCIPALE
        $tableau_exclus = [];
        if (isset($ids_exclus) && is_array($ids_exclus)) {
            $tableau_exclus = $ids_exclus;
        } elseif (isset($ids_deja_affiches) && is_array($ids_deja_affiches)) {
            $tableau_exclus = $ids_deja_affiches;
        }

        if (!empty($tableau_exclus)) {
            $ids_securises = array_map('intval', $tableau_exclus);
            if (!empty($ids_securises)) {
                $placeholders = implode(',', array_fill(0, count($ids_securises), '?'));
                $sql_sug .= " AND a.id_annonces NOT IN ($placeholders)";
                $params_sug = array_merge($params_sug, $ids_securises);
            }
        }

        // 3. GESTION GÉOGRAPHIQUE : VILLE SPÉCIFIQUE (MÊME RÉGION) VS TOUTES LES VILLES
        if (!empty($ville_selectionnee) && (int)$ville_selectionnee > 0) {
            // Récupérer la région de la ville sélectionnée
            $stmt_reg = $bdd->prepare("SELECT id_region FROM jevend_villes WHERE id_ville = ?");
            $stmt_reg->execute([(int)$ville_selectionnee]);
            $id_region_cible = $stmt_reg->fetchColumn();

            if ($id_region_cible) {
                // Restreindre aux autres villes de la MÊME région, en excluant la ville exacte
                $sql_sug .= " AND v.id_region = ? AND u.id_ville != ?";
                $params_sug[] = $id_region_cible;
                $params_sug[] = (int)$ville_selectionnee;
                $titre_section_connexes = "💡 Disponibles dans votre région :";
            } else {
                // Secours si la région n'est pas trouvée : exclure juste la ville
                $sql_sug .= " AND u.id_ville != ?";
                $params_sug[] = (int)$ville_selectionnee;
                $titre_section_connexes = "💡 Disponibles dans les environs :";
            }
        }

        // 4. FILTRE PAR MOT-CLÉ (RACINE DE RECHERCHE)
        if (!empty($recherche)) {
            $racine_recherche = mb_substr($recherche, 0, 4, 'UTF-8');
            $sql_sug .= " AND a.titre_objet_nettoye LIKE ?";
            $params_sug[] = '%' . $racine_recherche . '%';
        }

        // 5. FILTRE PAR CATÉGORIE
        if (!empty($cat_selectionnee) && (int)$cat_selectionnee > 0) {
            $sql_sug .= " AND a.id_categorie = ?";
            $params_sug[] = (int)$cat_selectionnee;
        }

        $sql_sug .= " ORDER BY RAND() LIMIT 4";
        
        $stmt_sug = $bdd->prepare($sql_sug);
        $stmt_sug->execute($params_sug);
        $suggestions_annonces = $stmt_sug->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $suggestions_annonces = [];
}

// 6. SI AUCUNE CORRESPONDANCE : CHOIX ALÉATOIRE D'UNE BANNIÈRE D'INCITATION
if (empty($suggestions_annonces)) {
    $afficher_banniere_incitation = true;
    $liste_fichiers_bannieres = [
        '_search_bann-r_connexe.php',
        '_search_bann-s_connexe.php',
        '_search_bann-p_connexe.php'
    ];
    // Sélection aléatoire parmi les 3 types
    $fichier_banniere_a_afficher = $liste_fichiers_bannieres[array_rand($liste_fichiers_bannieres)];
}
?>

<?php if (!empty($suggestions_annonces)): ?>
<!-- AFFICHAGE DES ARTICLES CONNEXES -->
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

<?php elseif ($afficher_banniere_incitation): ?>
<!-- AFFICHAGE DE LA BANNIÈRE D'INCITATION SÉLECTIONNÉE ALÉATOIREMENT -->
<div class="conteneur-recherches-connexes">
    <?php 
        $chemin_banniere_partielle = __DIR__ . '/' . $fichier_banniere_a_afficher;
        if (file_exists($chemin_banniere_partielle)) {
            include $chemin_banniere_partielle;
        }
    ?>
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

/* Styles pour les bannières textuelles d'incitation */
.banniere-incitation-connexe {
    padding: 15px 20px;
    border-radius: 6px;
    text-align: center;
}
.banniere-incitation-connexe.reg {
    background: #f1f5f9;
    border: 1px dashed #94a3b8;
    color: #334155;
}
.banniere-incitation-connexe.sup {
    background: #f3e8ff;
    border: 1px dashed #d8b4fe;
    color: #581c87;
}
.banniere-incitation-connexe.prem {
    background: #eff6ff;
    border: 1px dashed #bfdbfe;
    color: #1e3a8a;
}
.banniere-texte-interne {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    font-size: 0.9rem;
}
.btn-incitation {
    display: inline-block;
    padding: 6px 14px;
    background: #0f172a;
    color: #ffffff;
    border-radius: 4px;
    text-decoration: none;
    font-weight: bold;
    font-size: 0.8rem;
    transition: background 0.2s;
}
.btn-incitation:hover {
    background: #2563eb;
}

@media (max-width: 600px) {
    .grille-suggestions-objets { grid-template-columns: 1fr; }
}
</style>
