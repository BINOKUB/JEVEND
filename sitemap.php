<?php
// =============================================================================
// NOM DU SCRIPT : sitemap.php
// REVISION     : 1.0 - Générateur Sitemap XML Dynamique
// SCRIPT COMPLET ET SUIVI
// =============================================================================
require_once 'config.php';

// Entête HTTP strict pour indiquer au navigateur/robot qu'il s'agit de XML
header("Content-Type: application/xml; charset=utf-8");

// URL de base de ton site
$base_url = "https://jevend.com/";

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    <!-- 1. PAGES STATIQUES PRINCIPALES -->
    <url>
        <loc><?= $base_url ?>index.php</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?= $base_url ?>nous_joindre.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
    <url>
        <loc><?= $base_url ?>connexion.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.3</priority>
    </url>
    <url>
        <loc><?= $base_url ?>inscription.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.3</priority>
    </url>

    <!-- 2. ANNONCES ACTIVES (DYNAMIQUES DEPUIS LA BDD) -->
    <?php
    if (isset($bdd)) {
        try {
            // Sélection des annonces uniquement 'actif'
            $stmt = $bdd->query("
                SELECT id_annonces, date_creation 
                FROM jevend_annonces 
                WHERE statut = 'actif' 
                ORDER BY date_creation DESC
            ");

            while ($annonce = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $id = (int)$annonce['id_annonces'];
                // Format ISO 8601 exigé par Google (Ex: 2026-09-03)
                $date_iso = date('Y-m-d', strtotime($annonce['date_creation']));
                ?>
    <url>
        <loc><?= $base_url ?>fiche_annonce.php?id=<?= $id ?></loc>
        <lastmod><?= $date_iso ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
                <?php
            }
        } catch (PDOException $e) { }
    }
    ?>

</urlset>
