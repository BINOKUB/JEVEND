<?php
// =============================================================================
// NOM DU SCRIPT : actualite.php
// REVISION : 1.6 - Boîte d'archives compacte (Limit 10, Random, exclusion article actif)
// =============================================================================
session_start();
require_once 'config.php';
date_default_timezone_set('America/Montreal');

$date_demandée = $_GET['date'] ?? date('Y-m-d');

try {
    $stmt = $bdd->prepare("SELECT * FROM jevend_journal WHERE date_publication = ?");
    $stmt->execute([$date_demandée]);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$article) {
        $stmt_dernier = $bdd->query("SELECT * FROM jevend_journal ORDER BY date_publication DESC LIMIT 1");
        $article = $stmt_dernier->fetch(PDO::FETCH_ASSOC);
    }

    // Récupération de 3 annonces pour la colonne de gauche
    $stmt_annonces = $bdd->query("
        SELECT a.*, u.nom AS vendeur_nom 
        FROM jevend_annonces a
        JOIN jevend_utilisateurs u ON a.id_utilisateur = u.id_utilisateur
        WHERE a.statut = 'actif'
        ORDER BY RAND()
        LIMIT 3
    ");
    $annonces_pige = $stmt_annonces->fetchAll(PDO::FETCH_ASSOC);

    // Récupération des archives : exclusion de l'article affiché, ordre aléatoire, limité à 10
    $current_date = $article['date_publication'] ?? date('Y-m-d');
    $stmt_archives = $bdd->prepare("
        SELECT date_publication, titre_regional 
        FROM jevend_journal 
        WHERE date_publication != ? 
        ORDER BY RAND() 
        LIMIT 10
    ");
    $stmt_archives->execute([$current_date]);
    $archives = $stmt_archives->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $article = null;
    $annonces_pige = [];
    $archives = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Le Journal de la Région — jevend.com</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="style_news.css?v=1.5">
    <style>
        /* Style de la boîte d'archives compacte et défilante */
        .archives-scroll-box {
            max-height: 220px;
            overflow-y: auto;
            padding-right: 4px;
            box-sizing: border-box;
        }
        .archives-scroll-box::-webkit-scrollbar {
            width: 4px;
        }
        .archives-scroll-box::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
            border-radius: 4px;
        }
        .archives-scroll-box::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .archives-scroll-box::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>
<body class="admin-body">

    <?php include 'partials/_nav_publique.php'; ?>

    <div class="journal-container">

        <!-- COLONNE GAUCHE : ANNONCES PIGÉES ALEATOIREMENT -->
        <div class="col-gauche">
            <h3>🎯 Trésors de la région</h3>
            <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 15px;">Sélection instantanée dans notre catalogue.</p>
            
            <?php if (!empty($annonces_pige)): ?>
                <?php foreach ($annonces_pige as $ann): ?>
                    <div class="annonce-pige-card" style="padding: 0; overflow: hidden; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 15px;">
                        
                        <!-- ENCART PUB DIAGONAL -->
                        <div class="card-pub-placeholder">
                            <span>Annoncez sur jevend.com</span>
                        </div>

                        <div style="padding: 12px;">
                            <strong style="display: block; color: #0f172a; font-size: 0.95rem; margin-bottom: 4px;"><?= htmlspecialchars($ann['titre_objet_nettoye']) ?></strong>
                            <p class="prix-annonce" style="color: #16a34a; font-weight: bold; font-size: 1rem; margin: 0 0 6px 0;"><?= number_format($ann['prix'], 2, ',', ' ') ?> $</p>
                            <a href="details.php?id=<?= $ann['id_annonces'] ?>" style="color: #2563eb; font-size: 0.85rem; font-weight: 600; text-decoration: none;">Voir l'annonce →</a>
                        </div>

                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="font-size: 0.85rem;">Aucune annonce active pour le moment.</p>
            <?php endif; ?>
        </div>

        <!-- COLONNE CENTRALE : LA DOUBLE NOUVELLE DU JOUR -->
        <div class="col-centre">
            <div class="entete-incitatif">
                <h3 style="margin:0 0 5px 0;">Vous aimez notre contenu régional ?</h3>
                <p style="margin:0; font-size:0.9rem;">Rejoignez la plateforme, publiez vos annonces et gagnez en visibilité dès aujourd'hui.</p>
                <a href="connexion.php">Publier une annonce / S'inscrire</a>
            </div>

            <?php if ($article): ?>
                <span style="background: #0284c7; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; text-transform: uppercase;">
                    Édition du : <?= htmlspecialchars($article['date_publication']) ?>
                </span>

                <div class="section-nouvelle" style="margin-top: 15px;">
                    <h2>📰 <?= htmlspecialchars($article['titre_regional']) ?></h2>
                    <div><?= nl2br($article['texte_regional']) ?></div>
                </div>

                <div class="section-nouvelle">
                    <h2 style="color: #dc2626;">🚨 Alerte Arnaques & Brouteurs</h2>
                    <h3><?= htmlspecialchars($article['titre_arnaque']) ?></h3>
                    <div><?= nl2br($article['texte_arnaque']) ?></div>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px;">
                    <h2>Aucune publication pour aujourd'hui</h2>
                    <p>Revenez très vite pour découvrir la une du jour !</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- COLONNE DROITE : MINI-JEU & ARCHIVES -->
        <div class="col-droite">
            <h3>🎮 Coin Détente (Tic Tac Toe)</h3>
            <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 15px;">Détendez-vous entre deux lectures.</p>
            
            <div id="mini-jeu-container" style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 6px; text-align: center; margin-bottom: 25px;">
               
        <!-- Inclusion propre du Mini-Jeu Tic-Tac-Toe -->
            <div style="margin-bottom: 25px;">
                <?php include 'GAMES/tictacto.php'; ?>
            </div>

            </div>

            <h3 style="font-size: 1rem; border-top: 1px solid #e2e8f0; padding-top: 15px;">📚 Anciennes Nouvelles</h3>
            <div class="archives-scroll-box">
                <?php if (!empty($archives)): ?>
                    <?php foreach ($archives as $arch): ?>
                        <div style="margin-bottom: 10px; padding-bottom: 6px; border-bottom: 1px solid rgba(0,0,0,0.05);">
                            <a href="actualite.php?date=<?= $arch['date_publication'] ?>" style="color: #2563eb; text-decoration: none; font-size: 0.85rem; display: block; font-weight: 600;">
                                [<?= htmlspecialchars($arch['date_publication']) ?>] <?= htmlspecialchars($arch['titre_regional']) ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="font-size: 0.85rem; color: #64748b;">Aucune archive disponible.</p>
                <?php endif; ?>
            </div>
        </div>

    </div>

</body>
</html>
