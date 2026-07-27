<?php
// =============================================================================
// SCRIPT      : details.php
// REVISION    : 1.9 - Intégration du Cœur Négociateur (Signal de révision de prix)
// DESCRIPTION : Ajout de l'action directe "Liste d'Envie" pour permettre à l'acheteur
//               d'interpeller le vendeur pour une éventuelle baisse de prix.
// =============================================================================
session_start();
require_once 'config.php';
require_once 'fonctions_geoloc.php';
require_once 'partials/_jevend_stat.php';

$id_annonce = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$id_utilisateur_connecte = $_SESSION['id_utilisateur'] ?? null;

if ($id_annonce <= 0) {
    die("Annonce introuvable ou invalide.");
}

$id_ville_acheteur = null;
if ($id_utilisateur_connecte) {
    try {
        $stmt_acheteur = $bdd->prepare("SELECT id_ville FROM jevend_utilisateurs WHERE id_utilisateur = ?");
        $stmt_acheteur->execute([$id_utilisateur_connecte]);
        $id_ville_acheteur = $stmt_acheteur->fetchColumn();
    } catch (PDOException $e) { }
}

try {
    // EXTRACTION DE L'ANNONCE AVEC DÉTECTION DU STATUT FAVORIS ET COMPTEUR D'ENVIES
    $sql_annonce = "
        SELECT a.*, u.nom AS vendeur_nom, u.cellulaire AS vendeur_tel, u.id_ville AS vendeur_ville_id, v.nom_ville AS vendeur_ville_nom,
               IF(le.id_envie IS NOT NULL, 1, 0) AS est_favoris,
               (SELECT COUNT(*) FROM jevend_listes_envie WHERE id_annonce = a.id_annonces) AS nb_envies
        FROM jevend_annonces a
        JOIN jevend_utilisateurs u ON a.id_utilisateur = u.id_utilisateur
        JOIN jevend_villes v ON u.id_ville = v.id_ville
        LEFT JOIN jevend_listes_envie le ON a.id_annonces = le.id_annonce AND le.id_utilisateur = :id_user
        WHERE a.id_annonces = :id_annonce AND a.statut = 'actif'
    ";
    $stmt = $bdd->prepare($sql_annonce);
    $stmt->bindValue(':id_user', $id_utilisateur_connecte, PDO::PARAM_INT);
    $stmt->bindValue(':id_annonce', $id_annonce, PDO::PARAM_INT);
    $stmt->execute();
    $annonce = $stmt->fetch();

    if (!$annonce) {
        die("Cette vitrine n'est plus disponible ou a été retirée.");
    }

    incrementerVueAnnonce($bdd, $id_annonce, $id_utilisateur_connecte);

    $sql_images = "
        SELECT nom_fichier, est_principale 
        FROM jevend_annonces_images 
        WHERE id_annonces = ? 
        ORDER BY est_principale DESC, id_image ASC
    ";
    $stmt_img = $bdd->prepare($sql_images);
    $stmt_img->execute([$id_annonce]);
    $galerie_images = $stmt_img->fetchAll();

} catch (PDOException $e) {
    die("Erreur critique de base de données : " . $e->getMessage());
}

$image_principale_defaut = !empty($annonce['image_courante']) ? $annonce['image_courante'] : 'defaut.png';
$chemin_principale_defaut = "uploads/" . $image_principale_defaut;

$date_cree = new DateTime($annonce['date_creation']);
$date_expire = new DateTime($annonce['date_expiration']);
$maintenant = new DateTime();

$intervalle_total = $date_cree->diff($date_expire)->days;
$jours_restants = $maintenant->diff($date_expire)->days;

if ($maintenant > $date_expire) {
    $texte_temps = "❌ Affichage expiré";
} else {
    $pourcentage_restant = ($intervalle_total > 0) ? ($jours_restants / $intervalle_total) * 100 : 0;
    
    if ($pourcentage_restant <= 25) {
        $texte_temps = "⏳ Attention, plus que quelques jours avant la fin !";
    } elseif ($pourcentage_restant <= 50) {
        $texte_temps = "⏳ Le temps s'écoule, faites vite...";
    } else {
        $texte_temps = "🗓️ Il reste " . $jours_restants . " jours d'affichage";
    }
}

// DÉTECTION EN DIRECT DE LA VENTE FLASH
$a_promo = false;
$prix_promo_affiche = 0;
$temps_promo_texte = "";

if (!empty($annonce['prix_promo']) && !empty($annonce['date_fin_promo'])) {
    try {
        $dt_fin_p = new DateTime($annonce['date_fin_promo']);
        $dt_now_p = new DateTime();
        if ($dt_now_p < $dt_fin_p) {
            $a_promo = true;
            $prix_promo_affiche = (float)$annonce['prix_promo'];
            $diff_p = $dt_now_p->diff($dt_fin_p);
            $h_rest = ($diff_p->days * 24) + $diff_p->h;
            $temps_promo_texte = "Reste " . $h_rest . "h " . $diff_p->i . "m !";
        }
    } catch (Exception $e) {
        $a_promo = false;
    }
}

// DÉCODAGE DES STRINGS
$titre_propre = htmlspecialchars(stripslashes(html_entity_decode($annonce['titre_objet_nettoye'], ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8');
$description_propre = stripslashes(html_entity_decode($annonce['description_service'] ?? "Aucune description fournie.", ENT_QUOTES, 'UTF-8'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titre_propre ?> — jevend.com</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .vitrine-conteneur { max-width: 1000px; margin: 30px auto; padding: 0 15px; display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .galerie-zone { display: flex; flex-direction: column; gap: 15px; }
        .affichage-principal { width: 100%; height: 400px; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; display: flex; align-items: center; justify-content: center; overflow: hidden; padding: 10px; box-sizing: border-box; position: relative; }
        .affichage-principal img { width: 100%; height: 100%; object-fit: contain; }
        
        .vignettes-ligne { display: flex; flex-wrap: wrap; gap: 10px; padding-bottom: 5px; }
        .vignette-item { width: 80px; height: 80px; background-color: #ffffff; border: 2px solid #e2e8f0; border-radius: 6px; cursor: pointer; overflow: hidden; display: flex; align-items: center; justify-content: center; padding: 2px; box-sizing: border-box; transition: border-color 0.15s ease; }
        .vignette-item:hover, .vignette-item.active { border-color: #2563eb; }
        .vignette-item img { width: 100%; height: 100%; object-fit: contain; }

        .infos-zone { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 25px; display: flex; flex-direction: column; }
        .vitrine-vendeur { display: flex; align-items: center; gap: 8px; font-size: 0.95rem; color: #1e3a8a; font-weight: bold; margin-bottom: 10px; }
        .vitrine-meta { font-size: 0.85rem; color: #64748b; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; }
        .vitrine-titre { font-size: 1.6rem; font-weight: bold; color: #1e293b; margin: 0 0 15px 0; line-height: 1.3; }
        .vitrine-prix { font-size: 1.8rem; font-weight: bold; color: #16a34a; margin-bottom: 20px; }
        .vitrine-prix-promo { font-size: 2rem; font-weight: bold; color: #dc2626; margin-bottom: 20px; }
        .vitrine-description { font-size: 1rem; color: #334155; line-height: 1.6; margin-bottom: 25px; white-space: pre-line; }
        
        .boite-urgence { background: #fff7ed; border: 1px dashed #f97316; border-radius: 6px; padding: 12px; margin-bottom: 20px; font-size: 0.9rem; color: #c2410c; }
        .boite-promo-flash { background: #dc2626; color: #ffffff; border-radius: 6px; padding: 12px 16px; margin-bottom: 20px; font-weight: bold; font-size: 0.95rem; display: flex; justify-content: space-between; align-items: center; }

        /* BOUTON CŒUR NÉGOCIATEUR */
        .btn-interpeller-vendeur {
            width: 100%;
            margin-top: 10px;
            padding: 10px 14px;
            border: 1px solid #ea580c;
            background-color: #ffffff;
            color: #c2410c;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 0.88rem;
            transition: all 0.2s ease;
        }
        .btn-interpeller-vendeur:hover {
            background-color: #fff7ed;
            border-color: #c2410c;
        }

        .zone-contact-direct { display: flex; gap: 10px; margin-bottom: 15px; margin-top: auto; }
        .btn-contact-action { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none; color: #ffffff; padding: 12px; border-radius: 6px; font-weight: bold; font-size: 0.9rem; text-align: center; transition: background 0.15s ease; }
        .btn-appel { background-color: #10b981; }
        .btn-appel:hover { background-color: #059669; }
        .btn-texto { background-color: #059669; }
        .btn-texto:hover { background-color: #047857; }

        .btn-retour { display: inline-block; background: #f1f5f9; color: #475569; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; font-size: 0.9rem; text-align: center; transition: background 0.15s ease; }
        .btn-retour:hover { background: #e2e8f0; }

        .ribbon-vendu { position: absolute; top: 15px; left: 15px; background-color: #dc2626; color: #ffffff; padding: 6px 16px; font-size: 1.1rem; font-weight: bold; border-radius: 4px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2); text-transform: uppercase; letter-spacing: 1px; z-index: 10; }

        @media (max-width: 768px) { .vitrine-conteneur { grid-template-columns: 1fr; gap: 20px; margin: 15px auto; } .affichage-principal { height: 280px; } .zone-contact-direct { margin-top: 15px; } }
    </style>
</head>
<body class="admin-body">

    <div style="background: linear-gradient(135deg, #1e3a8a, #1e293b); color: #ffffff; padding: 20px; text-align: center;">
        <h1 style="margin: 0; font-size: 1.5rem; cursor:pointer;" onclick="window.location.href='index.php'">jevend.com</h1>
    </div>

    <div class="vitrine-conteneur">
        <div class="galerie-zone">
            <div class="affichage-principal">
                <?php if ($annonce['statut_vente'] === 'vendu'): ?>
                    <div class="ribbon-vendu">🔴 VENDU</div>
                <?php endif; ?>

                <?php if (file_exists($chemin_principale_defaut)): ?>
                    <img id="vue-principale" src="<?= htmlspecialchars($chemin_principale_defaut) ?>" alt="Image principale">
                <?php else: ?>
                    <div style="color: #94a3b8; font-size: 1rem;">📸 Image principale indisponible</div>
                <?php endif; ?>
            </div>

            <?php if (count($galerie_images) > 0): ?>
                <div class="vignettes-ligne">
                    <?php 
                    $index = 0;
                    foreach ($galerie_images as $img): 
                        $chemin_vignette = "uploads/" . $img['nom_fichier'];
                        if (file_exists($chemin_vignette)): 
                            $index++; 
                            ?>
                            <div class="vignette-item <?= ($img['nom_fichier'] == $image_principale_defaut) ? 'active' : '' ?>" onclick="changerImage('<?= htmlspecialchars($chemin_vignette) ?>', this)">
                                <img src="<?= htmlspecialchars($chemin_vignette) ?>" alt="Miniature <?= $index ?>">
                            </div>
                        <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="infos-zone">
            <div class="vitrine-vendeur">👤 Vendeur : <?= htmlspecialchars($annonce['vendeur_nom']) ?></div>

            <div class="vitrine-meta">
                <?= obtenirTexteDistance($bdd, $id_ville_acheteur, $annonce['vendeur_ville_id'], $annonce['vendeur_ville_nom'], $annonce['id_utilisateur'], $id_utilisateur_connecte) ?> 
                • Mis en ligne le : <?= date('d M Y', strtotime($annonce['date_creation'])) ?>
            </div>

            <?php if ($a_promo): ?>
                <div class="boite-promo-flash">
                    <span>🔥 VENTE FLASH EN COURS !</span>
                    <span style="background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 4px; font-size: 0.85rem;"><?= $temps_promo_texte ?></span>
                </div>
            <?php endif; ?>

            <div class="boite-urgence">
                <div style="font-weight: bold; margin-bottom: 4px;"><?= $texte_temps ?></div>
                <?php if ($annonce['nb_envies'] > 0): ?>
                    <div style="font-size: 0.85rem;">🔥 <strong><?= $annonce['nb_envies'] ?> acheteur(s)</strong> ont cet item dans leur liste d'envie !</div>
                <?php else: ?>
                    <div style="font-size: 0.85rem; opacity: 0.8;">Soyez le premier à ajouter cet objet à vos favoris !</div>
                <?php endif; ?>

                <!-- BOUTON NÉGOCIATEUR CŒUR -->
                <button id="btn-favoris-details" class="btn-interpeller-vendeur" data-id="<?= $id_annonce ?>">
                    <span id="coeur-icone"><?= ($annonce['est_favoris'] == 1) ? '❤️' : '🤍' ?></span>
                    <span id="coeur-texte"><?= ($annonce['est_favoris'] == 1) ? 'Cet objet est dans votre Liste d\'Envie' : 'Interpeller le vendeur (Demander une baisse de prix)' ?></span>
                </button>
            </div>

            <h2 class="vitrine-titre"><?= $titre_propre ?></h2>

            <!-- AFFICHAGE DU PRIX (RÉGULIER OU PROMO FLASH) -->
            <?php if ($a_promo): ?>
                <div class="vitrine-prix-promo">
                    <del style="color: #94a3b8; font-size: 1.3rem; margin-right: 10px; font-weight: normal;">
                        <?= number_format((float)$annonce['prix'], 2, ',', ' ') ?> $
                    </del>
                    <?= number_format($prix_promo_affiche, 2, ',', ' ') ?> $
                </div>
            <?php else: ?>
                <div class="vitrine-prix"><?= number_format((float)$annonce['prix'], 2, ',', ' ') ?> $</div>
            <?php endif; ?>

            <div class="vitrine-description"><?= nl2br(htmlspecialchars($description_propre, ENT_QUOTES, 'UTF-8')) ?></div>

            <!-- BLOC DES BOUTONS DE CONTACT DIRECT -->
            <?php if ($annonce['statut_vente'] === 'vendu'): ?>
                <div style="background-color: #fef2f2; border: 1px solid #fee2e2; padding: 15px; border-radius: 6px; text-align: center; color: #991b1b; font-weight: bold; margin-bottom: 15px;">
                    🔕 Les options d'appels et de messages ont été désactivées car cet objet a été vendu.
                </div>
            <?php elseif (!empty($annonce['vendeur_tel'])): ?>
                <div class="zone-contact-direct">
                    <a href="tel:<?= preg_replace('/[^0-9]/', '', $annonce['vendeur_tel']) ?>" class="btn-contact-action btn-appel" onclick="comptabiliserClic()">
                        📞 Appeler le vendeur
                    </a>
                    <a href="sms:<?= preg_replace('/[^0-9]/', '', $annonce['vendeur_tel']) ?>?body=Bonjour, je suis intéressé par votre annonce &quot;<?= rawurlencode(stripslashes(html_entity_decode($annonce['titre_objet_nettoye'], ENT_QUOTES, 'UTF-8'))) ?>&quot; sur jevend.com !" class="btn-contact-action btn-texto" onclick="comptabiliserClic()">
                        💬 Envoyer un texto
                    </a>
                </div>
            <?php else: ?>
                <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 12px; border-radius: 6px; font-size: 0.85rem; color: #64748b; text-align: center; margin-bottom: 15px; font-style: italic;">
                    📞 Aucun numéro de cellulaire renseigné par le vendeur pour cette vitrine.
                </div>
            <?php endif; ?>

            <a href="index.php" class="btn-retour">← Retour au fil d'actualité</a>
        </div>
    </div>

    <script>
    function changerImage(chemin, elementVignette) {
        const grandeImage = document.getElementById('vue-principale');
        if (grandeImage) { grandeImage.src = chemin; }
        document.querySelectorAll('.vignette-item').forEach(vignette => vignette.classList.remove('active'));
        elementVignette.classList.add('active');
    }

    function comptabiliserClic() {
        const idAnnonce = <?= $id_annonce ?>;
        fetch(`clic_stat.php?id_annonce=${idAnnonce}`)
            .then(response => response.json())
            .catch(error => console.error('Erreur stat:', error));
    }

    // --- GESTION DU CŒUR NÉGOCIATEUR SUR LA FICHE DÉTAILLÉE ---
    const btnFavorisDetails = document.getElementById('btn-favoris-details');
    if (btnFavorisDetails) {
        btnFavorisDetails.addEventListener('click', function() {
            const idAnnonce = this.getAttribute('data-id');
            const donnees = new FormData();
            donnees.append('id_annonce', idAnnonce);

            fetch('gerer_liste_envie.php', { method: 'POST', body: donnees })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'ajoute') {
                        document.getElementById('coeur-icone').textContent = '❤️';
                        document.getElementById('coeur-texte').textContent = 'Cet objet est dans votre Liste d\'Envie';
                        location.reload(); // Actualise le compteur d'acheteurs en direct
                    } else if (data.status === 'retire') {
                        document.getElementById('coeur-icone').textContent = '🤍';
                        document.getElementById('coeur-texte').textContent = 'Interpeller le vendeur (Demander une baisse de prix)';
                        location.reload();
                    } else if (data.status === 'erreur') {
                        alert('Veuillez vous connecter pour placer cet objet dans votre Liste d\'Envie.');
                        window.location.href = 'connexion.php';
                    }
                })
                .catch(err => console.error('Erreur favoris :', err));
        });
    }
    </script>
</body>
</html>
