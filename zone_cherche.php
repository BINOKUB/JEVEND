<?php
// =============================================================================
// NOM DU SCRIPT : zone_cherche.php
// REVISION : 1.2 - Grisement du bouton "J'ai cet objet" pour l'auteur de la demande
// DESCRIPTION : Catalogue optimisé pour un fort volume de demandes de la communauté.
// =============================================================================
session_start();
require_once 'config.php';

$id_utilisateur_connecte = $_SESSION['id_utilisateur'] ?? null;

// EXTRACTION DES CATÉGORIES
$categories = [];
try {
    $stmt_cat = $bdd->query("SELECT id_categorie, nom_fr FROM jevend_categories ORDER BY nom_fr ASC");
    $categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { }

// EXTRACTION DES VILLES
$villes = [];
try {
    $stmt_villes = $bdd->query("SELECT id_ville, nom_ville FROM jevend_villes ORDER BY nom_ville ASC");
    $villes = $stmt_villes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { }

// RÉCUPÉRATION DES FILTRES
$recherche_mot = trim($_GET['q'] ?? '');
$cat_filtre    = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$ville_filtre  = isset($_GET['ville']) ? (int)$_GET['ville'] : 0;

// REQUÊTE SQL DES RECHERCHES ACTIVES
$recherches = [];
$total_recherches = 0;

try {
    $sql = "
        SELECT r.*, v.nom_ville, c.nom_fr AS nom_categorie, u.nom AS nom_acheteur
        FROM jevend_recherches r
        JOIN jevend_villes v ON r.id_ville = v.id_ville
        JOIN jevend_categories c ON r.id_categorie = c.id_categorie
        JOIN jevend_utilisateurs u ON r.id_utilisateur = u.id_utilisateur
        WHERE r.statut = 'actif' AND r.date_expiration > NOW()
    ";
    
    $params = [];

    if (!empty($recherche_mot)) {
        $sql .= " AND (r.titre_recherche LIKE :q OR r.description LIKE :q)";
        $params[':q'] = '%' . $recherche_mot . '%';
    }
    if ($cat_filtre > 0) {
        $sql .= " AND r.id_categorie = :cat";
        $params[':cat'] = $cat_filtre;
    }
    if ($ville_filtre > 0) {
        $sql .= " AND r.id_ville = :ville";
        $params[':ville'] = $ville_filtre;
    }

    $sql .= " ORDER BY r.date_creation DESC";

    $stmt = $bdd->prepare($sql);
    $stmt->execute($params);
    $recherches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_recherches = count($recherches);

} catch (PDOException $e) {
    $erreur_sql = $e->getMessage();
}

$maintenant = new DateTime();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La Zone Je Cherche — jevend.com</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="zone_recherche.css">
</head>
<body class="admin-body">

    <?php include 'partials/_nav_publique.php'; ?>
    <?php include 'partials/_ticker_je_cherche.php'; ?>

    <div class="banner-zone-cherche">
        <h1>🎯 La Zone "Je Cherche"</h1>
        <p>Les membres de votre région recherchent ces objets. Vous possédez ce qu'il leur faut ? Proposez-leur directement !</p>
        <a href="poster_recherche.php" class="btn-banner-post">🎯 Publier une demande gratuite</a>
    </div>

    <!-- BARRE DE FILTRES -->
    <form action="zone_cherche.php" method="GET" class="barre-filtres-cherche">
        <input type="text" name="q" placeholder="Que cherchez-vous ?" value="<?= htmlspecialchars($recherche_mot) ?>">

        <select name="ville">
            <option value="0">📍 Toutes les villes</option>
            <?php foreach ($villes as $v): ?>
                <option value="<?= $v['id_ville'] ?>" <?= $v['id_ville'] == $ville_filtre ? 'selected' : '' ?>>
                    📍 <?= htmlspecialchars($v['nom_ville']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="cat">
            <option value="0">🌐 Toutes les catégories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id_categorie'] ?>" <?= $cat['id_categorie'] == $cat_filtre ? 'selected' : '' ?>>
                    📁 <?= htmlspecialchars($cat['nom_fr']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn-filtre-submit">🔍 Filtrer</button>
    </form>

    <div class="admin-conteneur">

        <?php if (isset($_GET['succes']) && $_GET['succes'] == 1): ?>
            <div style="max-width: 1000px; margin: 0 auto 20px auto; background-color: #f0fdf4; color: #166534; padding: 15px; border-radius: 8px; font-weight: bold; text-align: center; border: 1px solid #bbf7d0;">
                🚀 Votre demande a été publiée avec succès dans La Zone ! Les vendeurs de votre région pourront vous contacter.
            </div>
        <?php endif; ?>

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

    </div>

    <script>
        function toggleDetails(id) {
            const bloc = document.getElementById('details-' + id);
            const btn = document.getElementById('btn-txt-' + id);
            
            if (bloc.classList.contains('ouvert')) {
                bloc.classList.remove('ouvert');
                btn.textContent = '➕ Détails';
            } else {
                bloc.classList.add('ouvert');
                btn.textContent = '➖ Fermer';
            }
        }
    </script>

</body>
</html>
