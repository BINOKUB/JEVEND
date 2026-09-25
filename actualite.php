<?php
// =============================================================================
// NOM DU SCRIPT : actualite.php
// REVISION : 1.12 - Exclusion de la chronique active dans les archives de gauche
// =============================================================================
session_start();
require_once 'config.php';
date_default_timezone_set('America/Montreal');

// 1. Enregistrement des statistiques de visite de la page
try {
    $ip_visiteur = $_SERVER['REMOTE_ADDR'] ?? '';
    $referent_visiteur = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'Accès direct';
    
    $stmt_stat = $bdd->prepare("INSERT INTO jevend_stats (page, ip, referent, date_visite) VALUES ('actualite', ?, ?, NOW())");
    $stmt_stat->execute([$ip_visiteur, $referent_visiteur]);
} catch (Exception $e) {
    // Échec silencieux pour ne jamais bloquer l'affichage de la page
}

$date_demandée = $_GET['date'] ?? date('Y-m-d');
$id_memoire_demande = $_GET['memoire_id'] ?? null;

try {
    // Récupération de l'article du journal quotidien
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

    // Récupération des archives des actualités (pour la colonne de droite)
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

    // Récupération de la bannière publicitaire 728x90 active
    $stmt_b728 = $bdd->query("SELECT * FROM jevend_banniere_728 WHERE statut = 'actif' ORDER BY id DESC LIMIT 1");
    $banniere_728 = $stmt_b728->fetch(PDO::FETCH_ASSOC);

    // Gestion de la chronique "Mémoire & Mystères" (demandée par ID ou la plus récente par défaut)
    if ($id_memoire_demande) {
        $stmt_memoire = $bdd->prepare("SELECT * FROM jevend_memoires WHERE id_memoire = ?");
        $stmt_memoire->execute([$id_memoire_demande]);
        $chronique = $stmt_memoire->fetch(PDO::FETCH_ASSOC);
    }
    if (empty($chronique)) {
        $stmt_memoire = $bdd->query("SELECT * FROM jevend_memoires ORDER BY id_memoire DESC LIMIT 1");
        $chronique = $stmt_memoire->fetch(PDO::FETCH_ASSOC);
    }

    // Récupération de la liste des archives "Mémoire & Mystères" en EXCLUANT la chronique affichée au centre
    $id_chronique_active = $chronique['id_memoire'] ?? 0;
    $stmt_memoires_archives = $bdd->prepare("SELECT id_memoire, titre, date_creation FROM jevend_memoires WHERE id_memoire != ? ORDER BY id_memoire DESC LIMIT 15");
    $stmt_memoires_archives->execute([$id_chronique_active]);
    $memoires_archives = $stmt_memoires_archives->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $article = null;
    $annonces_pige = [];
    $archives = [];
    $banniere_728 = null;
    $chronique = null;
    $memoires_archives = [];
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
        /* Style de la bannière 728x90 responsive */
        .banniere-728-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .banniere-728-container img {
            max-width: 100%;
            width: 728px;
            height: auto;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body class="admin-body">

    <?php include 'partials/_nav_publique.php'; ?>

    <div class="journal-container">

        <!-- COLONNE GAUCHE : ANNONCES PIGÉES + ARCHIVES MÉMOIRE & MYSTÈRES -->
        <div class="col-gauche">
            <h3>🎯 Trésors de la région</h3>
            <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 15px;">Sélection instantanée dans notre catalogue.</p>
            
            <?php if (!empty($annonces_pige)): ?>
                <?php foreach ($annonces_pige as $ann): ?>
                    <div class="annonce-pige-card" style="padding: 0; overflow: hidden; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 15px;">
                        
                        <div class="card-pub-placeholder">
                            <span>Annoncez sur jevend.com</span>
                        </div>

                        <div style="padding: 12px;">
                            <?php $titre_pige_propre = htmlspecialchars(html_entity_decode($ann['titre_objet_nettoye'], ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>
                            <strong style="display: block; color: #0f172a; font-size: 0.95rem; margin-bottom: 4px;"><?= $titre_pige_propre ?></strong>
                            <p class="prix-annonce" style="color: #16a34a; font-weight: bold; font-size: 1rem; margin: 0 0 6px 0;"><?= number_format($ann['prix'], 2, ',', ' ') ?> $</p>
                            <a href="details.php?id=<?= $ann['id_annonces'] ?>" style="color: #2563eb; font-size: 0.85rem; font-weight: 600; text-decoration: none;">Voir l'annonce →</a>
                        </div>

                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="font-size: 0.85rem;">Aucune annonce active pour le moment.</p>
            <?php endif; ?>

            <!-- BLOC DÉROULANT : ARCHIVES MÉMOIRE & MYSTÈRES -->
            <h3 style="font-size: 1rem; border-top: 1px solid #e2e8f0; padding-top: 15px; margin-top: 25px; color: #b45309;">
                <i class="fas fa-landmark"></i> Mémoire & Mystères (Archives)
            </h3>
            <div class="archives-scroll-box">
                <?php if (!empty($memoires_archives)): ?>
                    <?php foreach ($memoires_archives as $mem): ?>
                        <div style="margin-bottom: 10px; padding-bottom: 6px; border-bottom: 1px solid rgba(0,0,0,0.05);">
                            <a href="actualite.php?memoire_id=<?= $mem['id_memoire'] ?>" style="color: #b45309; text-decoration: none; font-size: 0.85rem; display: block; font-weight: 600;">
                                [<?= date('d/m/Y', strtotime($mem['date_creation'])) ?>] <?= htmlspecialchars($mem['titre']) ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="font-size: 0.85rem; color: #64748b;">Aucune autre chronique archivée.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- COLONNE CENTRALE : BANNIÈRE 728x90 + DOUBLE NOUVELLE + MÉMOIRE -->
        <div class="col-centre">
            
            <!-- AFFICHAGE DE LA BANNIÈRE 728x90 SI ACTIVE -->
            <?php if ($banniere_728 && !empty($banniere_728['image_banniere']) && file_exists("uploads/" . $banniere_728['image_banniere'])): ?>
                <div class="banniere-728-container">
                    <?php if (!empty($banniere_728['lien_url'])): ?>
                        <a href="<?= htmlspecialchars($banniere_728['lien_url']) ?>" target="_blank">
                    <?php endif; ?>
                    <img src="uploads/<?= htmlspecialchars($banniere_728['image_banniere']) ?>" alt="Espace publicitaire 728x90">
                    <?php if (!empty($banniere_728['lien_url'])): ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

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

            <!-- 📜 SECTION : MÉMOIRE & MYSTÈRES DU CORRIDOR (Affichage de la chronique active) -->
            <?php if (!empty($chronique)): ?>
                <div class="section-nouvelle" style="border-left: 4px solid #b45309; background: #fffbeb; margin-top: 30px; padding: 22px 22px 22px 26px;">
                    <div style="font-size: 0.75rem; color: #b45309; text-transform: uppercase; font-weight: bold; margin-bottom: 8px;">
                        <i class="fas fa-landmark"></i> Mémoire & Mystères du Corridor
                    </div>
                    <h2 style="color: #78350f; margin-top: 0;"><?= htmlspecialchars($chronique['titre']) ?></h2>
                    <div style="color: #3f3f46; line-height: 1.6;"><?= nl2br($chronique['contenu']) ?></div>
                    <div style="font-size: 0.8rem; color: #a16207; margin-top: 15px; text-align: right;">
                        Chronique publiée le <?= date('d / m / Y', strtotime($chronique['date_creation'])) ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <!-- COLONNE DROITE : MINI-JEU & ANCIENNES NOUVELLES -->
        <div class="col-droite">
            <h3>🎮 Coin Détente (Tic Tac Toe)</h3>
            <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 15px;">Détendez-vous entre deux lectures.</p>
            
            <div id="mini-jeu-container" style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 6px; text-align: center; margin-bottom: 25px;">
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
