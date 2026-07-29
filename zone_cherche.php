<?php
// =============================================================================
// NOM DU SCRIPT : zone_cherche.php
// REVISION : 1.1 - Flux compact haute densité (Lignes dépliables)
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
    <style>
        .banner-zone-cherche {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #ffffff;
            padding: 35px 20px;
            text-align: center;
            border-bottom: 4px solid #f59e0b;
        }
        .banner-zone-cherche h1 {
            margin: 0 0 10px 0;
            font-size: 2.2rem;
            font-weight: 900;
            color: #ffffff;
        }
        .banner-zone-cherche p {
            margin: 0 0 20px 0;
            color: #94a3b8;
            font-size: 1.05rem;
            max-width: 650px;
            margin-left: auto;
            margin-right: auto;
        }
        .btn-banner-post {
            background-color: #f59e0b;
            color: #0f172a;
            text-decoration: none;
            padding: 10px 22px;
            border-radius: 25px;
            font-weight: 800;
            font-size: 0.95rem;
            display: inline-block;
            transition: all 0.15s ease;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }
        .btn-banner-post:hover {
            background-color: #d97706;
            color: #ffffff;
            transform: translateY(-2px);
        }

        .barre-filtres-cherche {
            max-width: 1000px;
            margin: -25px auto 30px auto;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 12px 18px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        .barre-filtres-cherche input,
        .barre-filtres-cherche select {
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 0.9rem;
            outline: none;
            flex: 1;
            min-width: 140px;
        }
        .btn-filtre-submit {
            background-color: #0f172a;
            color: #ffffff;
            border: none;
            padding: 9px 18px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background 0.15s ease;
        }
        .btn-filtre-submit:hover { background-color: #1e293b; }

        /* CONTENEUR DU FLUX COMPACT */
        .flux-compact-container {
            max-width: 1000px;
            margin: 0 auto 50px auto;
            padding: 0 15px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        /* LIGNE DE DEMANDE COMPACTE */
        .ligne-demande-card {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-left: 5px solid #f59e0b;
            border-radius: 6px;
            transition: all 0.15s ease;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }
        .ligne-demande-card:hover {
            border-color: #94a3b8;
            border-left-color: #d97706;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .ligne-entete-bar {
            padding: 12px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            cursor: pointer;
            background-color: #ffffff;
        }

        .ligne-infos-principales {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-grow: 1;
            flex-wrap: wrap;
        }

        .badge-cat-compact {
            background: #f1f5f9;
            color: #475569;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 4px;
            white-space: nowrap;
            border: 1px solid #e2e8f0;
        }

        .titre-ligne-compact {
            font-size: 0.98rem;
            font-weight: bold;
            color: #0f172a;
            margin: 0;
        }

        .ligne-meta-droite {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-shrink: 0;
            font-size: 0.85rem;
        }

        .badge-ville-compact {
            color: #0284c7;
            font-weight: 600;
        }

        .badge-budget-compact {
            color: #16a34a;
            font-weight: 800;
            background: #f0fdf4;
            padding: 3px 8px;
            border-radius: 4px;
            border: 1px solid #bbf7d0;
        }

        .btn-toggle-deplier {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            color: #334155;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.15s;
        }
        .btn-toggle-deplier:hover {
            background: #e2e8f0;
        }

        /* CONTENU DÉPLIABLE (ACCORDÉON) */
        .ligne-details-contenu {
            display: none;
            padding: 20px;
            background-color: #f8fafc;
            border-top: 1px dashed #cbd5e1;
        }
        .ligne-details-contenu.ouvert {
            display: block;
        }

        .grille-details-interne {
            display: grid;
            grid-template-columns: 120px 1fr auto;
            gap: 20px;
            align-items: center;
        }

        .vignette-apercu-ligne {
            width: 120px;
            height: 120px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .vignette-apercu-ligne img {
            max-width: 100%;
            max-height: 120px;
            object-fit: contain;
        }

        .texte-description-detail {
            font-size: 0.9rem;
            color: #334155;
            line-height: 1.5;
            white-space: pre-line;
            margin-bottom: 10px;
        }

        .meta-auteur-Ligne {
            font-size: 0.82rem;
            color: #64748b;
            display: flex;
            gap: 12px;
        }

        .btn-repondre-compact {
            background-color: #16a34a;
            color: #ffffff;
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 6px;
            font-size: 0.88rem;
            font-weight: bold;
            white-space: nowrap;
            transition: background 0.15s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: inline-block;
            text-align: center;
        }
        .btn-repondre-compact:hover { background-color: #15803d; }

        @media (max-width: 768px) {
            .ligne-entete-bar { flex-direction: column; align-items: flex-start; gap: 8px; }
            .ligne-meta-droite { width: 100%; justify-content: space-between; border-top: 1px solid #f1f5f9; padding-top: 6px; }
            .grille-details-interne { grid-template-columns: 1fr; }
            .vignette-apercu-ligne { width: 100%; height: 180px; }
            .btn-repondre-compact { width: 100%; }
        }
    </style>
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
                                    <a href="details_recherche.php?id=<?= $r['id_recherche'] ?>" class="btn-repondre-compact">
                                        💬 J'ai cet objet !
                                    </a>
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
