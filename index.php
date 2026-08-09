<?php
// =============================================================================
// NOM DU SCRIPT : index.php
// REVISION : 4.7 - Prise en charge des Prix Spéciaux (Vente Flash) sur la grille publique
// =============================================================================
session_start();
require_once 'config.php';
require_once 'partials/_chek_bann_pro.php';
// include_once 'partials/_check_bann_regulier.php';
include_once 'partials/_check_ann_reguliere.php';
require_once 'fonctions_geoloc.php';
require_once 'partials/_jevend_stat.php';

$_SESSION['bannieres_affichees_session'] = [];

$id_utilisateur_connecte = $_SESSION['id_utilisateur'] ?? null;
$id_ville_acheteur = null;
if ($id_utilisateur_connecte) {
    try {
        $stmt_acheteur = $bdd->prepare("SELECT id_ville FROM jevend_utilisateurs WHERE id_utilisateur = ?");
        $stmt_acheteur->execute([$id_utilisateur_connecte]);
        $id_ville_acheteur = $stmt_acheteur->fetchColumn();
    } catch (PDOException $e) { }
}

try {
    $sql_nettoyage_bannieres = "
        DELETE b FROM jevend_bannieres_actives b
        INNER JOIN jevend_annonces a ON b.id_annonce = a.id_annonces
        WHERE a.statut_vente = 'vendu' OR a.statut = 'vendu'
    ";
    $bdd->exec($sql_nettoyage_bannieres);
} catch (PDOException $e) { }

try {
    // 1. CARROUSEL SUPRÊME PRO
    $sql_supreme_pro = "
        SELECT b.*, u.nom_entreprise, u.nom AS vendeur_nom 
        FROM jevend_bannieres_actives_pro b
        JOIN jevend_utilisateurs u ON b.id_utilisateur = u.id_utilisateur
        WHERE b.type_banniere = 'supreme' 
          AND b.statut_affichage = 'active'
          AND b.date_fin >= NOW()
        ORDER BY RAND()
        LIMIT 3
    ";
    $bannieres_supreme_pro = $bdd->query($sql_supreme_pro)->fetchAll(PDO::FETCH_ASSOC);

    // 2. FLUX D'ANNONCES
    $sql_annonces = "
        SELECT a.*, u.nom AS vendeur_nom, u.id_ville AS vendeur_ville_id, v.nom_ville AS vendeur_ville_nom,
               IF(le.id_envie IS NOT NULL, 1, 0) AS est_favoris,
               (SELECT COUNT(*) FROM jevend_listes_envie WHERE id_annonce = a.id_annonces) AS nb_envies
        FROM jevend_annonces a
        JOIN jevend_utilisateurs u ON a.id_utilisateur = u.id_utilisateur
        JOIN jevend_villes v ON u.id_ville = v.id_ville
        LEFT JOIN jevend_listes_envie le ON a.id_annonces = le.id_annonce AND le.id_utilisateur = :id_user
        WHERE a.statut = 'actif'
        ORDER BY a.date_creation DESC
        LIMIT 12
    ";
    $stmt_annonces = $bdd->prepare($sql_annonces);
    $stmt_annonces->bindValue(':id_user', $id_utilisateur_connecte, PDO::PARAM_INT);
    $stmt_annonces->execute();
    $flux_annonces = $stmt_annonces->fetchAll();

    // 3. BANNIÈRES RÉGULIÈRES
    $sql_flux_pub = "
        SELECT b.id_banniere, b.id_annonce, b.id_utilisateur, b.texte_banniere, b.type_banniere, u.nom AS vendeur_nom, a.image_courante 
        FROM jevend_bannieres_actives b
        JOIN jevend_utilisateurs u ON b.id_utilisateur = u.id_utilisateur
        LEFT JOIN jevend_annonces a ON b.id_annonce = a.id_annonces
        WHERE b.statut_affichage = 'active' AND b.type_banniere = 'reguliere'
        ORDER BY RAND()
    ";
    $bannieres_flux = $bdd->query($sql_flux_pub)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur critique d'affichage du flux : " . $e->getMessage());
}

$index_banniere = 0; 
$total_bannieres_flux = count($bannieres_flux);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>jevend.com — Le Réseau Social d'Affaires</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="style_index.css?v=1.1">
</head>
<body class="admin-body">

    <?php include 'partials/_nav_publique.php'; ?>

<!-- BANDEAU LIVE "JE CHERCHE" (Intercalé sous la navigation) -->
<?php include 'partials/_ticker_je_cherche.php'; ?>

    <!-- 1. CARROUSEL SUPRÊME B2B -->
    <?php if (!empty($bannieres_supreme_pro)): ?>
        <div class="carrousel-supreme-wrapper" id="carrousel-wrapper">
            <?php if (count($bannieres_supreme_pro) > 1): ?>
                <button class="carrousel-nav-btn prev" onclick="deplacerCarrousel(-1)">❮</button>
                <button class="carrousel-nav-btn next" onclick="deplacerCarrousel(1)">❯</button>
            <?php endif; ?>

            <div class="carrousel-supreme-container" id="carrousel-container">
                <?php foreach ($bannieres_supreme_pro as $index => $bann): ?>
                    <?php 
                        $url_brute_sup = trim($bann['url_redirection'] ?? '');
                        if (!empty($url_brute_sup) && strpos($url_brute_sup, 'http') !== 0) {
                            $url_brute_sup = 'https://' . $url_brute_sup;
                        }
                        $lien_cible = htmlspecialchars($url_brute_sup);
                        $image_url  = htmlspecialchars($bann['image_url']);
                        $id_pro     = (int)$bann['id_banniere_pro'];
                    ?>
                    <div class="carrousel-slide" style="background-image: url('<?= $image_url ?>');" onclick="enregistrerClicEtOuvrirPro(<?= $id_pro ?>, '<?= $lien_cible ?>')">
                        <div class="carrousel-slide-overlay"></div>
                        <div class="carrousel-slide-content">
                            <span style="background: #7c3aed; color: #ffffff; font-size: 0.7rem; font-weight: bold; padding: 3px 8px; border-radius: 4px; text-transform: uppercase;">
                                👑 Commerce Vedette
                            </span>
                            <h2><?= htmlspecialchars($bann['nom_entreprise'] ?: $bann['vendeur_nom'] ?: 'Commerce Local') ?></h2>
                            <?php if (!empty($bann['texte_banniere'])): ?>
                                <p><?= htmlspecialchars($bann['texte_banniere']) ?></p>
                            <?php endif; ?>
                            <span class="carrousel-btn-action">🌐 Visiter le site web →</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (count($bannieres_supreme_pro) > 1): ?>
                <div class="carrousel-puces" id="carrousel-puces">
                    <?php foreach ($bannieres_supreme_pro as $index => $bann): ?>
                        <span class="puce-dot <?= $index === 0 ? 'active' : '' ?>" onclick="allerAIndexSlide(<?= $index ?>)"></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="header-supreme-zone" style="padding: 30px 20px; text-align: center; background: #0f172a; color: #fff; border-bottom: 3px solid #7c3aed;">
            <h1 style="margin: 0; font-size: 1.8rem;">jevend.com</h1>
            <p style="margin: 5px 0 0 0; opacity: 0.8; font-size: 0.9rem;">Le réseau social d'affaires à Matane de Québec à Gaspé</p>
        </div>
    <?php endif; ?>

    <!-- BANNIÈRE D'INFORMATION OFFICIELLE -->
    <?php include_once 'partials/_admin_ban.php'; ?>

    <!-- 2. MODULE AUTONOME PREMIUM -->
    <?php include 'partials/_slot_premium.php'; ?>

    <!-- 3. FLUX D'ANNONCES -->
    <div class="admin-conteneur">
        <div class="flux-grille" id="fil-actualite">
            <?php 
            $compteur_position = 0;

            foreach ($flux_annonces as $annonce): 
                $compteur_position++;
                $fichier_image = !empty($annonce['image_courante']) ? $annonce['image_courante'] : '';
                $chemin_complet_image = "uploads/" . $fichier_image; 

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

                $date_exp_formatee = date('Y-m-d', strtotime($annonce['date_expiration']));
                ?>
                <div class="carte-annonce" style="<?= $a_promo ? 'border: 2px solid #dc2626;' : '' ?>">
                    <div class="carte-image-zone">
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
                            <a href="store.php?id=<?= $annonce['id_utilisateur'] ?>" class="vendeur-nom" title="Visiter la boutique de <?= htmlspecialchars($annonce['vendeur_nom']) ?>" style="text-decoration: none;">
                                👤 <?= htmlspecialchars($annonce['vendeur_nom']) ?>
                            </a>
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
                        
                        <button style="background:none; border:none; cursor:pointer; font-size:0.9rem; color:#64748b; padding:0;" onclick="partagerAnnonce(<?= $annonce['id_annonces'] ?>, '<?= htmlspecialchars(addslashes($annonce['titre_objet_nettoye']), ENT_QUOTES) ?>')">
                            🔗 Partager
                        </button>
                        
                        <?php if (isset($annonce['statut_vente']) && $annonce['statut_vente'] === 'vendu'): ?>
                            <a href="details.php?id=<?= $annonce['id_annonces'] ?>" class="btn-action" style="margin:0; padding:4px 8px; font-size:0.75rem; text-decoration:none; width:auto; background-color:#64748b;">📂 Archives</a>
                        <?php else: ?>
                            <a href="details.php?id=<?= $annonce['id_annonces'] ?>" class="btn-action" style="margin:0; padding:4px 8px; font-size:0.75rem; text-decoration:none; width:auto; <?= $a_promo ? 'background-color:#dc2626;' : '' ?>">👁️ Vitrine</a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php 
                if ($compteur_position % 4 === 0 && $index_banniere < $total_bannieres_flux): 
                    $banniere = $bannieres_flux[$index_banniere]; 
                    $index_banniere++;
                    
                    $_SESSION['bannieres_affichees_session'][] = (int)$banniere['id_banniere'];
                    incrementerVueBanniere($bdd, $banniere['id_banniere']);
                    ?>
                    <div class="bloc-banniere-pub">
                        <span class="banniere-badge">📣 VITRINE SPONSORISÉE</span>
                        <div class="banniere-slogan">"<?= htmlspecialchars($banniere['texte_banniere'] ?? '') ?>"</div>
                        <div style="font-size: 0.85rem; color: #64748b; margin-bottom: 12px;">Artisan local : <strong><?= htmlspecialchars($banniere['vendeur_nom'] ?? '') ?></strong></div>
                        
                        <?php if (!empty($banniere['id_annonce'])): ?>
                            <a href="details.php?id=<?= $banniere['id_annonce'] ?>" class="btn-action lien-banniere-pub" data-id="<?= $banniere['id_banniere'] ?>" style="max-width: 250px; margin: 0 auto; text-decoration: none;">👁️ Découvrir la vitrine</a>
                        <?php endif; ?>

                        <?php if (!empty($banniere['image_courante']) && file_exists("uploads/" . $banniere['image_courante'])): ?>
                            <div class="flux-pub-image-zone">
                                <img src="uploads/<?= htmlspecialchars($banniere['image_courante']) ?>" alt="Vitrine promotionnelle">
                            </div>
                        <?php endif; ?>
                    </div>
                <?php 
                endif; 
            endforeach; 
            ?>
        </div>
        <div id="declencheur-scroll" style="height: 50px; margin-top: 20px; text-align: center; color: #64748b; font-style: italic; font-size: 0.9rem;">🦊 Défiler pour en voir plus...</div>
    </div>

    <script>
    function partagerAnnonce(idAnnonce, titreAnnonce) {
        const baseUrl = window.location.protocol + '//' + window.location.host + window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1);
        const urlAnnonce = baseUrl + 'details.php?id=' + idAnnonce;

        if (navigator.share) {
            navigator.share({
                title: titreAnnonce,
                text: 'Regarde cette annonce sur jevend.com : ' + titreAnnonce,
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

    if (typeof enregistrerClicEtOuvrirPro === 'undefined') {
        function enregistrerClicEtOuvrirPro(idBannierePro, urlCible) {
            if (!urlCible || urlCible === '#' || urlCible === '') return;
            
            fetch(`clic_stat.php?id_banniere_pro=${idBannierePro}`)
                .finally(() => {
                    window.open(urlCible, '_blank');
                });
        }
    }

    let indexSlideActuel = 0;
    const totalSlides = <?= count($bannieres_supreme_pro) ?>;
    const container = document.getElementById('carrousel-container');
    const puces = document.querySelectorAll('.puce-dot');
    let intervalleCarrousel = null;

    function mettreAJourCarrousel() {
        if (!container) return;
        container.style.transform = `translateX(-${indexSlideActuel * 100}%)`;
        puces.forEach((puce, idx) => {
            if (idx === indexSlideActuel) { puce.classList.add('active'); }
            else { puce.classList.remove('active'); }
        });
    }

    function deplacerCarrousel(direction) {
        indexSlideActuel += direction;
        if (indexSlideActuel >= totalSlides) { indexSlideActuel = 0; } 
        else if (indexSlideActuel < 0) { indexSlideActuel = totalSlides - 1; }
        mettreAJourCarrousel();
    }

    function allerAIndexSlide(idx) {
        indexSlideActuel = idx;
        mettreAJourCarrousel();
    }

    function demarrerAutoRotation() {
        if (totalSlides > 1) {
            intervalleCarrousel = setInterval(() => {
                deplacerCarrousel(1);
            }, 3000);
        }
    }

    function stopperAutoRotation() {
        if (intervalleCarrousel) clearInterval(intervalleCarrousel);
    }

    const wrapper = document.getElementById('carrousel-wrapper');
    if (wrapper) {
        wrapper.addEventListener('mouseenter', stopperAutoRotation);
        wrapper.addEventListener('mouseleave', demarrerAutoRotation);
    }

    window.addEventListener('DOMContentLoaded', demarrerAutoRotation);

    const grilleFlux = document.getElementById('fil-actualite'); 
    const declencheur = document.getElementById('declencheur-scroll');
    let pageActuelle = 1; 
    let chargementEnCours = false; 
    let finDuCatalogue = false;
    const options = { root: null, rootMargin: '0px 0px 400px 0px', threshold: 0.1 };

    const observateur = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !chargementEnCours && !finDuCatalogue) {
                chargementEnCours = true; 
                declencheur.textContent = "🦊 Recherche des trésors suivants...";
                fetch(`charger_flux_infini.php?page=${pageActuelle}`)
                    .then(response => response.text())
                    .then(htmlOutput => {
                        if (htmlOutput.trim() === "") { 
                            finDuCatalogue = true; 
                            declencheur.textContent = "✨ Vous avez exploré toutes les vitrines de la région !"; 
                        } else { 
                            grilleFlux.insertAdjacentHTML('beforeend', htmlOutput); 
                            pageActuelle++; 
                            chargementEnCours = false; 
                            declencheur.textContent = "🦊 Défiler pour en voir plus..."; 
                        }
                    })
                    .catch(error => { chargementEnCours = false; });
            }
        });
    }, options);
    observateur.observe(declencheur);

    grilleFlux.addEventListener('click', function(e) {
        const boutonCoeur = e.target.closest('.btn-favoris'); 
        if (!boutonCoeur) return;
        const idAnnonce = boutonCoeur.getAttribute('data-id'); 
        const donnees = new FormData(); 
        donnees.append('id_annonce', idAnnonce);
        fetch('gerer_liste_envie.php', { method: 'POST', body: donnees })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'ajoute') { boutonCoeur.textContent = '❤️'; } 
                else if (data.status === 'retire') { boutonCoeur.textContent = '🤍'; }
                else if (data.status === 'erreur') { 
                    alert('Veuillez vous connecter pour enregistrer vos favoris.'); 
                    window.location.href = 'connexion.php'; 
                }
            });
    });

    document.addEventListener('click', function(e) {
        const lienBanniere = e.target.closest('.lien-banniere-pub');
        if (!lienBanniere) return;

        e.preventDefault();
        const idBanniere = lienBanniere.getAttribute('data-id');
        const destination = lienBanniere.getAttribute('href');

        fetch(`clic_stat.php?id_banniere=${idBanniere}`)
            .then(() => { window.location.href = destination; })
            .catch(() => { window.location.href = destination; });
    });
    </script>
</body>
</html>
