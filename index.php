<?php
// =============================================================================
// NOM DU SCRIPT : index.php
// REVISION : 4.7 - Prise en charge des Prix Spéciaux (Vente Flash) sur la grille publique
// =============================================================================
session_start();
require_once 'config.php';
require_once 'partials/_chek_bann_pro.php';
// Purge automatique silencieuse des messages de chat de plus de 30 jours
require_once 'chat_delete_membre.php';
// include_once 'partials/_check_bann_regulier.php';
include_once 'partials/_check_ann_reguliere.php';
require_once 'fonctions_geoloc.php';
require_once 'partials/_jevend_stat.php';
//INCLUSION DU MODULE DE PURGE AUTOMATIQUE
include 'partials/_delete_je_cherche.php';

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

<!-- 3. MODULE de LISTING DES ANNONCES PAGE INDEX -->
    <?php include 'partials/_index_annonce_listing.php'; ?>

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
<?php 
if (file_exists('partials/_barre_flottante.php')) {
    include 'partials/_barre_flottante.php';
}
?>
</body>
</html>
