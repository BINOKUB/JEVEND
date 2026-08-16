<?php
// =============================================================================
// NOM DU SCRIPT : zone_cherche.php
// REVISION : 1.3 - Séparation du listing dans partials/_zone_recherche_liste.php
// DESCRIPTION : Moteur de recherche et filtres pour La Zone Je Cherche.
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

// RÉCUPÉRATION DES FILTRES (MOTEUR DE RECHERCHE)
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
    <link rel="stylesheet" href="zone_recherche.css?V=1">
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

        <!-- INCLUSION DU LISTING MODULAIRE -->
        <?php include 'partials/_zone_recherche_liste.php'; ?>

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
