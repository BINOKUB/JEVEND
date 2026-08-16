<?php
// =============================================================================
// NOM DU SCRIPT : store.php
// REVISION : 1.5 - Intégration complète de l'affichage des Ventes Flash (Prix Spécial Vendeur)
// =============================================================================
session_start();
require_once 'config.php';
require_once 'fonctions_geoloc.php';

$id_vendeur = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$id_utilisateur_connecte = $_SESSION['id_utilisateur'] ?? null;

if ($id_vendeur <= 0) {
    die("Boutique introuvable ou invalide.");
}

// 1. RÉCUPÉRATION DE LA VILLE DE L'ACHETEUR CONNECTÉ
$id_ville_acheteur = null;
if ($id_utilisateur_connecte) {
    try {
        $stmt_acheteur = $bdd->prepare("SELECT id_ville FROM jevend_utilisateurs WHERE id_utilisateur = ?");
        $stmt_acheteur->execute([$id_utilisateur_connecte]);
        $id_ville_acheteur = $stmt_acheteur->fetchColumn();
    } catch (PDOException $e) { }
}

try {
    // 2. INFOS DU VENDEUR, SA VILLE ET SA DESCRIPTION MAGASIN
    $stmt_vendeur = $bdd->prepare("
        SELECT u.nom, u.description_magasin, v.nom_ville 
        FROM jevend_utilisateurs u
        JOIN jevend_villes v ON u.id_ville = v.id_ville
        WHERE u.id_utilisateur = ? AND u.statut = 'actif'
    ");
    $stmt_vendeur->execute([$id_vendeur]);
    $vendeur = $stmt_vendeur->fetch();

    if (!$vendeur) {
        die("Ce commerçant n'a pas de boutique active.");
    }

    // 3. EXTRACTION DE TOUTES LES ANNONCES DE CE VENDEUR UNIQUE AVEC LE STATUT DE VENTE
    $sql_stock = "
        SELECT a.*, u.nom AS vendeur_nom, u.id_ville AS vendeur_ville_id, v.nom_ville AS vendeur_ville_nom,
               IF(le.id_envie IS NOT NULL, 1, 0) AS est_favoris,
               (SELECT COUNT(*) FROM jevend_listes_envie WHERE id_annonce = a.id_annonces) AS nb_envies
        FROM jevend_annonces a
        JOIN jevend_utilisateurs u ON a.id_utilisateur = u.id_utilisateur
        JOIN jevend_villes v ON u.id_ville = v.id_ville
        LEFT JOIN jevend_listes_envie le ON a.id_annonces = le.id_annonce AND le.id_utilisateur = :id_user
        WHERE a.id_utilisateur = :id_vendeur AND a.statut = 'actif'
        ORDER BY a.date_creation DESC
    ";
    $stmt_stock = $bdd->prepare($sql_stock);
    $stmt_stock->bindValue(':id_user', $id_utilisateur_connecte, PDO::PARAM_INT);
    $stmt_stock->bindValue(':id_vendeur', $id_vendeur, PDO::PARAM_INT);
    $stmt_stock->execute();
    $stock_annonces = $stmt_stock->fetchAll();

} catch (PDOException $e) {
    die("Erreur critique d'affichage de la boutique : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boutique de <?= htmlspecialchars($vendeur['nom']) ?> — jevend.com</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="style_index.css?v=1.2">
</head>
<body class="admin-body">

    <!-- ZONE EN-TÊTE DU STORE EXCLUSIF -->
    <div class="store-header-zone">
        <span style="background:#16a34a; color:#fff; padding:4px 10px; border-radius:4px; font-size:0.8rem; font-weight:bold; text-transform:uppercase;">🏬 Vitrine Marchande</span>
        <h1 style="margin: 15px 0 5px 0; font-size: 2.2rem; font-style: italic;">La Boutique de <?= htmlspecialchars($vendeur['nom']) ?></h1>
        <p style="margin: 0; opacity: 0.85; font-size: 1rem;">Localisation principale : <strong>📍 <?= htmlspecialchars($vendeur['nom_ville']) ?></strong></p>
    </div>

    <div class="nav-bar-store">
        <a href="index.php" class="btn-back">← Retour au fil général</a>
    </div>

    <!-- AFFICHAGE DYNAMIQUE DU MOT DE BIENVENUE UNIQUEMENT S'IL EXISTE -->
    <?php if (!empty($vendeur['description_magasin'])): ?>
        <div class="mot-bienvenue-box">
            <div class="mot-bienvenue-contenu">
                <div style="font-weight: bold; color: #1e3a8a; font-size: 1.1rem; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                    📢 Présentation & Promotions
                </div>
                <?= htmlspecialchars(stripslashes(html_entity_decode($vendeur['description_magasin'], ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="admin-conteneur">
        <h2 style="font-size: 1.3rem; margin-top: 10px; color: #1e293b;"><?= count($stock_annonces) ?> article(s) en vitrine</h2>
        
        <div class="flux-grille" id="fil-actualite">
            <?php 
            if (count($stock_annonces) === 0):
                echo "<p style='color:#64748b; font-style:italic;'>Ce vendeur n'a aucun article en vitrine pour le moment.</p>";
            endif;

            foreach ($stock_annonces as $annonce): 
                $fichier_image = !empty($annonce['image_courante']) ? $annonce['image_courante'] : '';
                $chemin_complet_image = "uploads/" . $fichier_image; 

                // Urgence temps d'affichage standard
                $date_cree = new DateTime($annonce['date_creation']);
                $date_expire = new DateTime($annonce['date_expiration']);
                $maintenant = new DateTime();
                $intervalle_total = $date_cree->diff($date_expire)->days;
                $jours_restants = $maintenant->diff($date_expire)->days;
                $badge_temps = "";
                
                if ($maintenant <= $date_expire && $intervalle_total > 0) {
                    $pourcentage = ($jours_restants / $intervalle_total) * 100;
                    if ($pourcentage <= 25) { $badge_temps = "<div class='badge-urgence-carte'>⏳ Plus que quelques jours !</div>"; }
                    elseif ($pourcentage <= 50) { $badge_temps = "<div class='badge-urgence-carte'>⏳ Le temps s'écoule !</div>"; }
                }

                // DÉTECTION EN DIRECT DU PRIX SPÉCIAL (VENTE FLASH)
                $a_promo = false;
                $prix_promo_affiche = 0;
                $temps_promo_texte = "";

                if (!empty($annonce['prix_promo']) && !empty($annonce['date_fin_promo'])) {
                    try {
                        $dt_fin_p = new DateTime($annonce['date_fin_promo']);
                        if ($maintenant < $dt_fin_p) {
                            $a_promo = true;
                            $prix_promo_affiche = (float)$annonce['prix_promo'];
                            $diff_p = $maintenant->diff($dt_fin_p);
                            $h_rest = ($diff_p->days * 24) + $diff_p->h;
                            $temps_promo_texte = "Reste " . $h_rest . "h " . $diff_p->i . "m !";
                        }
                    } catch (Exception $e) { $a_promo = false; }
                }

                // Formatage propre AAAA-MM-JJ de la date d'expiration
                $date_exp_formatee = date('Y-m-d', strtotime($annonce['date_expiration']));
                ?>
                <div class="carte-annonce" style="<?= $a_promo ? 'border: 2px solid #dc2626;' : '' ?>">
                    <div class="carte-image-zone">
                        <!-- AJOUT DU TAG DE VENTE VISUEL SUR LA PHOTO -->
                        <?php if (isset($annonce['statut_vente']) && $annonce['statut_vente'] === 'vendu'): ?>
                            <div class="mini-badge-vendu">🔴 VENDU</div>
                        <?php endif; ?>

                        <?php if(!empty($annonce['image_courante']) && file_exists($chemin_complet_image)): ?>
                            <img src="<?= htmlspecialchars($chemin_complet_image) ?>" alt="<?= htmlspecialchars($annonce['titre_objet_nettoye']) ?>">
                        <?php else: ?>
                            <div style="color: #94a3b8; font-size: 0.8rem; text-align: center; padding: 10px;">📸 Pas de photo</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="carte-corps">
                        <div class="carte-vendeur-ligne">
                            <span class="vendeur-nom">👤 <?= htmlspecialchars($annonce['vendeur_nom']) ?></span>
                            <span style="font-size: 0.75rem; color: #475569; font-weight: bold; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; border: 1px solid #cbd5e1;">
                                ⌛ Exp : <?= $date_exp_formatee ?>
                            </span>
                        </div>
                        
                        <div class="carte-meta-ligne">
                            <?= obtenirTexteDistance($bdd, $id_ville_acheteur, $annonce['vendeur_ville_id'], $annonce['vendeur_ville_nom'], $annonce['id_utilisateur'], $id_utilisateur_connecte) ?> • 🕒 <?= date('d M', strtotime($annonce['date_creation'])) ?>
                        </div>

                        <?php if ($a_promo): ?>
                            <div style="font-size: 0.72rem; font-weight: bold; color: #ffffff; background-color: #dc2626; padding: 3px 6px; border-radius: 4px; margin-bottom: 6px; display: inline-block;">
                                🔥 VENTE FLASH (<?= $temps_promo_texte ?>)
                            </div>
                        <?php else: ?>
                            <?= $badge_temps ?>
                        <?php endif; ?>

                        <?php if ($annonce['nb_envies'] > 0): ?>
                            <div style="font-size:0.65rem; color:#b45309; margin-bottom:6px;">🔥 Envie : <strong><?= $annonce['nb_envies'] ?> acheteur(s)</strong></div>
                        <?php endif; ?>
                        
                        <h3 class="carte-titre" title="<?= htmlspecialchars($annonce['titre_objet_nettoye']) ?>"><?= htmlspecialchars($annonce['titre_objet_nettoye']) ?></h3>
                        
                        <!-- PRIX VENTE FLASH OU PRIX RÉGULIER -->
                        <div class="carte-prix">
                            <?php if ($a_promo): ?>
                                <del style="color: #94a3b8; font-size: 0.85rem; margin-right: 6px; font-weight: normal;">
                                    <?= number_format((float)$annonce['prix'], 2, ',', ' ') ?> $
                                </del>
                                <span style="color: #dc2626; font-weight: bold;"><?= number_format($prix_promo_affiche, 2, ',', ' ') ?> $</span>
                            <?php else: ?>
                                <?= number_format((float)$annonce['prix'], 2, ',', ' ') ?> $
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="carte-actions">
                        <button class="btn-favoris" data-id="<?= $annonce['id_annonces'] ?>" title="Favoris">
                            <?= ($annonce['est_favoris'] == 1) ? '❤️' : '🤍' ?>
                        </button>
                        
                        <!-- VRAIE FONCTION DE PARTAGE INTELLIGENTE -->
                        <button style="background:none; border:none; cursor:pointer; font-size:0.9rem; color:#64748b; padding:0;" onclick="partagerAnnonce(<?= $annonce['id_annonces'] ?>, '<?= htmlspecialchars(addslashes($annonce['titre_objet_nettoye']), ENT_QUOTES) ?>')">
                            🔗 Partager
                        </button>
                        
                        <!-- REFILEMENT DE COULEUR SI L'OBJET EST LIQUIDÉ OU EN PROMO -->
                        <?php if (isset($annonce['statut_vente']) && $annonce['statut_vente'] === 'vendu'): ?>
                            <a href="details.php?id=<?= $annonce['id_annonces'] ?>" class="btn-action" style="margin:0; padding:4px 8px; font-size:0.75rem; text-decoration:none; width:auto; background-color:#64748b;">📂 Archives</a>
                        <?php else: ?>
                            <a href="details.php?id=<?= $annonce['id_annonces'] ?>" class="btn-action" style="margin:0; padding:4px 8px; font-size:0.75rem; text-decoration:none; width:auto; <?= $a_promo ? 'background-color:#dc2626;' : '' ?>">👁️ Vitrine</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    // FONCTION GLOBALE : PARTAGE MULTI-PLATEFORME (MOBILE NATIVE + PC COPIE PRESSE-PAPIER)
    function partagerAnnonce(idAnnonce, titreAnnonce) {
        const baseUrl = window.location.protocol + '//' + window.location.host + window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1);
        const urlAnnonce = baseUrl + 'details.php?id=' + idAnnonce;

        if (navigator.share) {
            navigator.share({
                title: titreAnnonce,
                text: 'Regarde cet article sur jevend.com : ' + titreAnnonce,
                url: urlAnnonce
            }).catch(() => {});
        } else if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(urlAnnonce).then(() => {
                alert('🔗 Lien de l\'annonce copié dans le presse-papier !');
            }).catch(() => {
                prompt('Copiez le lien direct vers cette annonce :', urlAnnonce);
            });
        } else {
            prompt('Copiez le lien direct vers cette annonce :', urlAnnonce);
        }
    }

    const grilleFlux = document.getElementById('fil-actualite');
    grilleFlux.addEventListener('click', function(e) {
        const boutonCoeur = e.target.closest('.btn-favoris'); if (!boutonCoeur) return;
        const idAnnonce = boutonCoeur.getAttribute('data-id'); const donnees = new FormData(); donnees.append('id_annonce', idAnnonce);
        fetch('gerer_liste_envie.php', { method: 'POST', body: donnees }).then(response => response.json()).then(data => {
            if (data.status === 'ajoute') { boutonCoeur.textContent = '❤️'; } 
            else if (data.status === 'retire') { boutonCoeur.textContent = '🤍'; }
        });
    });
    </script>
</body>
</html>
