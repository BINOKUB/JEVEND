<?php
// =============================================================================
// NOM DU SCRIPT : search.php
// REVISION : 1.9 - Priorisation automatique des annonces avec bannière active en tête de recherche
// =============================================================================
session_start();
require_once 'config.php';
require_once 'fonctions_geoloc.php';

$id_utilisateur_connecte = $_SESSION['id_utilisateur'] ?? null;
$id_ville_acheteur = null;

if ($id_utilisateur_connecte) {
    try {
        $stmt_acheteur = $bdd->prepare("SELECT id_ville FROM jevend_utilisateurs WHERE id_utilisateur = ?");
        $stmt_acheteur->execute([$id_utilisateur_connecte]);
        $id_ville_acheteur = $stmt_acheteur->fetchColumn();
    } catch (PDOException $e) { }
}

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

$recherche = trim($_GET['q'] ?? '');
$cat_selectionnee = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$ville_selectionnee = isset($_GET['ville']) ? (int)$_GET['ville'] : 0;

$nom_cat_selectionnee = "Toutes les catégories";
if ($cat_selectionnee > 0) {
    foreach ($categories as $c) {
        if ($c['id_categorie'] == $cat_selectionnee) {
            $nom_cat_selectionnee = $c['nom_fr'];
            break;
        }
    }
}

$nom_ville_selectionnee = "Toutes les villes";
if ($ville_selectionnee > 0) {
    foreach ($villes as $v) {
        if ($v['id_ville'] == $ville_selectionnee) {
            $nom_ville_selectionnee = $v['nom_ville'];
            break;
        }
    }
}

$annonces_par_page = 10; 
$page_actuelle = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page_actuelle < 1) $page_actuelle = 1;
$offset = ($page_actuelle - 1) * $annonces_par_page;

$url_params = "";
if (!empty($recherche)) $url_params .= "&q=" . urlencode($recherche);
if ($cat_selectionnee > 0) $url_params .= "&cat=" . $cat_selectionnee;
if ($ville_selectionnee > 0) $url_params .= "&ville=" . $ville_selectionnee;

$resultats = [];
$resultats_bronze = [];
$total_resultats = 0;
$total_pages = 0;
$recherche_effectuee = false;
$erreur_caracteres = false;

if (!empty($recherche) && mb_strlen($recherche, 'UTF-8') < 3) {
    $erreur_caracteres = true;
    $recherche_effectuee = true;
}

if (!$erreur_caracteres && (!empty($recherche) || $cat_selectionnee > 0 || $ville_selectionnee > 0 || isset($_GET['q']))) {
    $recherche_effectuee = true;
    try {
        $sql_count = "SELECT COUNT(*) FROM jevend_annonces a JOIN jevend_utilisateurs u ON a.id_utilisateur = u.id_utilisateur WHERE a.statut = 'actif'";
        $params_count = [];

        if (!empty($recherche)) {
            $sql_count .= " AND a.titre_objet_nettoye LIKE :recherche";
            $params_count[':recherche'] = '%' . $recherche . '%';
        }
        if ($cat_selectionnee > 0) {
            $sql_count .= " AND a.id_categorie = :cat";
            $params_count[':cat'] = $cat_selectionnee;
        }
        if ($ville_selectionnee > 0) {
            $sql_count .= " AND u.id_ville = :ville";
            $params_count[':ville'] = $ville_selectionnee;
        }

        $stmt_count = $bdd->prepare($sql_count);
        $stmt_count->execute($params_count);
        $total_resultats = $stmt_count->fetchColumn();
        
        $total_pages = ceil($total_resultats / $annonces_par_page);

        if ($total_resultats > 0) {
            // EXTRACTION DES ANNONCES AVEC PRIORITÉ BANNIÈRE ACTIVE
            $sql = "
                SELECT a.*, u.nom AS vendeur_nom, u.id_ville AS vendeur_ville_id, v.nom_ville AS vendeur_ville_nom,
                       IF(le.id_envie IS NOT NULL, 1, 0) AS est_favoris,
                       IF(ba.id_banniere IS NOT NULL, 1, 0) AS a_banniere
                FROM jevend_annonces a
                JOIN jevend_utilisateurs u ON a.id_utilisateur = u.id_utilisateur
                JOIN jevend_villes v ON u.id_ville = v.id_ville
                LEFT JOIN jevend_listes_envie le ON a.id_annonces = le.id_annonce AND le.id_utilisateur = :id_user
                LEFT JOIN jevend_bannieres_actives ba ON a.id_annonces = ba.id_annonce AND ba.statut_affichage = 'active'
                WHERE a.statut = 'actif'
            ";

            $params = [':id_user' => $id_utilisateur_connecte];

            if (!empty($recherche)) {
                $sql .= " AND a.titre_objet_nettoye LIKE :recherche";
                $params[':recherche'] = '%' . $recherche . '%';
            }
            if ($cat_selectionnee > 0) {
                $sql .= " AND a.id_categorie = :cat";
                $params[':cat'] = $cat_selectionnee;
            }
            if ($ville_selectionnee > 0) {
                $sql .= " AND u.id_ville = :ville";
                $params[':ville'] = $ville_selectionnee;
            }

            // TRI : BANNIÈRES ACTIVES D'ABORD, PUIS PAR DATE DE CRÉATION
            $sql .= " ORDER BY a_banniere DESC, a.date_creation DESC LIMIT :offset, :limit";

            $stmt = $bdd->prepare($sql);
            foreach ($params as $key => &$val) { $stmt->bindParam($key, $val); }
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $annonces_par_page, PDO::PARAM_INT);
            
            $stmt->execute();
            $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Vitrines Bronze
            $sql_bronze = "
                SELECT a.*, u.nom AS vendeur_nom 
                FROM jevend_annonces a
                JOIN jevend_utilisateurs u ON a.id_utilisateur = u.id_utilisateur
                WHERE a.statut = 'actif'
            ";
            $params_bronze = [];
            if ($cat_selectionnee > 0) {
                $sql_bronze .= " AND a.id_categorie = :cat_b";
                $params_bronze[':cat_b'] = $cat_selectionnee;
            }
            if ($ville_selectionnee > 0) {
                $sql_bronze .= " AND u.id_ville = :ville_b";
                $params_bronze[':ville_b'] = $ville_selectionnee;
            }
            $sql_bronze .= " ORDER BY RAND() LIMIT 4";
            $stmt_bronze = $bdd->prepare($sql_bronze);
            $stmt_bronze->execute($params_bronze);
            $resultats_bronze = $stmt_bronze->fetchAll(PDO::FETCH_ASSOC);
        }

    } catch (PDOException $e) {
        $erreur_search = "Erreur de recherche : " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche — jevend.com</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .zone-recherche-centree { max-width: 850px; margin: <?= $recherche_effectuee ? '20px auto 20px auto' : '80px auto 40px auto' ?>; text-align: center; padding: 0 15px; transition: all 0.3s ease; }
        .logo-jevend-search { font-size: 3rem; font-weight: 900; font-style: italic; color: #0f172a; letter-spacing: -1px; display: inline-flex; align-items: center; justify-content: center; gap: 2px; margin-bottom: 5px; user-select: none; }
        .point-vert-pulse { width: 14px; height: 14px; background-color: #10b981; border-radius: 50%; display: inline-block; box-shadow: 0 0 10px rgba(16, 185, 129, 0.8); animation: pulsionVert 2s infinite ease-in-out; }
        @keyframes pulsionVert { 0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); } 70% { transform: scale(1.15); box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); } 100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); } }
        .slogan-moteur { color: #64748b; font-size: 0.95rem; margin-bottom: 20px; }
        
        .barre-google-wrapper { position: relative; background: #ffffff; border: 2px solid #cbd5e1; border-radius: 30px; display: flex; align-items: center; padding: 6px 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); transition: all 0.2s ease; gap: 8px; }
        .barre-google-wrapper:focus-within { border-color: #2563eb; box-shadow: 0 6px 16px rgba(37, 99, 235, 0.15); }
        .btn-plus-categorie { background: #f1f5f9; border: 1px solid #cbd5e1; color: #1e293b; font-size: 1.1rem; font-weight: bold; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s ease; flex-shrink: 0; }
        .btn-plus-categorie:hover { background: #e2e8f0; color: #2563eb; }
        .badge-cat-filtre { display: none; align-items: center; gap: 6px; background: #e0f2fe; color: #0369a1; font-size: 0.8rem; font-weight: bold; padding: 4px 10px; border-radius: 15px; white-space: nowrap; }
        .badge-cat-filtre span { cursor: pointer; font-size: 0.9rem; }
        .badge-cat-filtre span:hover { color: #dc2626; }
        
        .select-ville-search {
            border: none;
            background: #f8fafc;
            border-right: 1px solid #cbd5e1;
            padding: 6px 10px;
            font-size: 0.88rem;
            color: #334155;
            font-weight: 500;
            outline: none;
            cursor: pointer;
            border-radius: 15px;
            max-width: 170px;
            text-overflow: ellipsis;
        }

        .champ-saisie-search { flex: 1; border: none; outline: none; font-size: 1.05rem; color: #1e293b; background: transparent; padding: 0 6px; min-width: 120px; }
        .btn-soumettre-search { background: #2563eb; color: #ffffff; border: none; padding: 8px 18px; border-radius: 20px; font-size: 0.9rem; font-weight: bold; cursor: pointer; transition: all 0.15s ease; flex-shrink: 0; white-space: nowrap; }
        .btn-soumettre-search:hover { background: #1d4ed8; }
        
        .dropdown-categories-menu { display: none; position: absolute; top: 52px; left: 10px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 260px; max-height: 280px; overflow-y: auto; z-index: 100; text-align: left; padding: 8px 0; }
        .dropdown-categories-menu.ouvert { display: block; }
        .item-cat-option { padding: 10px 16px; font-size: 0.9rem; color: #334155; cursor: pointer; transition: background 0.15s ease; }
        .item-cat-option:hover { background: #f1f5f9; color: #2563eb; font-weight: bold; }

        .liste-resultats-container { max-width: 850px; margin: 0 auto; display: flex; flex-direction: column; gap: 12px; }
        .item-resultat-ligne { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 15px; display: flex; align-items: center; justify-content: space-between; gap: 15px; transition: border-color 0.15s ease, box-shadow 0.15s ease; }
        .item-resultat-ligne:hover { border-color: #3b82f6; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03); }
        .vignette-recherche-zone { width: 70px; height: 70px; min-width: 70px; min-height: 70px; max-width: 70px; max-height: 70px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; display: flex; align-items: center; justify-content: center; position: relative; flex-shrink: 0; }
        .vignette-recherche-zone img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .info-resultat-corps { flex-grow: 1; display: flex; flex-direction: column; gap: 4px; overflow: hidden; }
        .titre-resultat-annonce { font-size: 1rem; font-weight: bold; color: #1e293b; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .meta-resultat-ligne { font-size: 0.8rem; color: #64748b; display: flex; align-items: center; gap: 8px; }
        .action-resultat-droite { display: flex; align-items: center; gap: 15px; flex-shrink: 0; }
        .prix-resultat { font-size: 1.1rem; font-weight: bold; color: #16a34a; white-space: nowrap; }
        .mini-badge-vendu-search { position: absolute; top: 2px; left: 2px; background-color: #dc2626; color: #ffffff; font-size: 0.55rem; font-weight: bold; padding: 1px 4px; border-radius: 2px; text-transform: uppercase; z-index: 2; }

        .pagination-conteneur { display: flex; flex-direction: column; align-items: center; margin: 30px 0 60px 0; font-family: Arial, sans-serif; user-select: none; }
        .pagination-logo { display: flex; align-items: baseline; font-size: 2.5rem; font-weight: bold; color: #1e293b; margin-bottom: 5px; }
        .pagination-lettre-j { color: #2563eb; }
        .pagination-lettre-e { color: #ea4335; }
        .pagination-lettre-v { color: #fbbc05; }
        .pagination-lettre-e2 { color: #2563eb; }
        .pagination-lettre-n { color: #34a853; }
        .pagination-lettre-d { color: #ea4335; }
        .pagination-liens { display: flex; gap: 15px; align-items: center; font-size: 0.95rem; }
        .pagination-liens a { color: #2563eb; text-decoration: none; padding: 5px 8px; }
        .pagination-liens a:hover { text-decoration: underline; }
        .page-num-active { color: #1e293b; font-weight: bold; pointer-events: none; padding: 5px 8px; }
        .btn-prec-suiv { color: #2563eb; text-decoration: none; font-weight: bold; }

        @media (max-width: 768px) {
            .barre-google-wrapper { flex-wrap: wrap; border-radius: 16px; padding: 10px; }
            .select-ville-search { max-width: 100%; width: 100%; border-right: none; border-bottom: 1px solid #e2e8f0; margin-bottom: 6px; }
            .item-resultat-ligne { flex-wrap: wrap; gap: 10px; }
            .action-resultat-droite { width: 100%; justify-content: space-between; border-top: 1px dashed #e2e8f0; padding-top: 8px; }
            .logo-jevend-search { font-size: 2.2rem; }
            .pagination-logo { font-size: 1.8rem; }
            .pagination-liens { gap: 8px; font-size: 0.85rem; }
        }
    </style>
</head>
<body class="admin-body">

    <?php include 'partials/_nav_publique.php'; ?>

    <div class="zone-recherche-centree">
        <div class="logo-jevend-search">
            jevend<span class="point-vert-pulse"></span>com
        </div>
        <div class="slogan-moteur">Le moteur de recherche des bonnes affaires locales</div>

        <form action="search.php" method="GET" id="form-moteur-search">
            <input type="hidden" name="cat" id="input-cat-id" value="<?= $cat_selectionnee ?>">

            <div class="barre-google-wrapper">
                <button type="button" class="btn-plus-categorie" id="btn-toggle-cat" title="Filtrer par catégorie">+</button>

                <div class="badge-cat-filtre" id="badge-cat" style="<?= $cat_selectionnee > 0 ? 'display: inline-flex;' : '' ?>">
                    <span id="texte-badge-cat"><?= htmlspecialchars($nom_cat_selectionnee) ?></span>
                    <span onclick="retirerFiltreCategorie(event)" title="Retirer ce filtre">✕</span>
                </div>

                <div class="dropdown-categories-menu" id="menu-dropdown-cat">
                    <div class="item-cat-option" onclick="selectionnerCategorie(0, 'Toutes les catégories')">🌐 Toutes les catégories</div>
                    <?php foreach ($categories as $cat): ?>
                        <div class="item-cat-option" onclick="selectionnerCategorie(<?= $cat['id_categorie'] ?>, '<?= htmlspecialchars(addslashes($cat['nom_fr'])) ?>')">
                            📁 <?= htmlspecialchars($cat['nom_fr']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <select name="ville" id="select-ville-search" class="select-ville-search" onchange="verifierCompteResultats()">
                    <option value="0">📍 Toutes les villes</option>
                    <?php foreach ($villes as $v): ?>
                        <option value="<?= $v['id_ville'] ?>" <?= $v['id_ville'] == $ville_selectionnee ? 'selected' : '' ?>>
                            📍 <?= htmlspecialchars($v['nom_ville']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input type="text" name="q" id="input-q-search" class="champ-saisie-search" placeholder="Que cherchez-vous aujourd'hui ?" value="<?= htmlspecialchars($recherche) ?>" autofocus autocomplete="off">

                <button type="submit" id="btn-submit-search" class="btn-soumettre-search">🔍 Rechercher</button>
            </div>
            
            <div id="msg-ajax-search" style="font-size: 0.85rem; font-weight: bold; margin-top: 8px; text-align: center; min-height: 20px;"></div>
        </form>
    </div>

    <div class="admin-conteneur">
        <?php if ($recherche_effectuee): ?>
            
            <?php if ($erreur_caracteres): ?>
                <div style="max-width: 850px; margin: 20px auto; background: #fff1f2; border: 1px solid #fecdd3; padding: 40px; border-radius: 8px; text-align: center; color: #be123c;">
                    <div style="font-size: 2.5rem; margin-bottom: 10px;">⚠️</div>
                    <h3 style="margin: 0 0 8px 0;">Recherche trop courte</h3>
                    <p style="margin: 0; font-size: 0.95rem;">Veuillez saisir au moins <strong>3 caractères</strong> pour effectuer une recherche par mot-clé.</p>
                </div>

            <?php else: ?>
                <div style="max-width: 850px; margin: 0 auto 12px auto; color: #64748b; font-size: 0.9rem;">
                    🎯 <strong><?= number_format($total_resultats, 0, ',', ' ') ?></strong> résultat(s) trouvé(s)
                    <?php if (!empty($recherche)): ?> pour « <strong><?= htmlspecialchars($recherche) ?></strong> »<?php endif; ?>
                    <?php if ($cat_selectionnee > 0): ?> dans la catégorie <strong><?= htmlspecialchars($nom_cat_selectionnee) ?></strong><?php endif; ?>
                    <?php if ($ville_selectionnee > 0): ?> à <strong><?= htmlspecialchars($nom_ville_selectionnee) ?></strong><?php endif; ?>
                    (Page <?= $page_actuelle ?> sur <?= $total_pages ?>)
                </div>

                <?php if ($total_resultats > 0): ?>
                    
                    <!-- 1. VITRINES BRONZE EN HAUT -->
                    <?php include 'partials/_search_vitrine_bronze.php'; ?>

                    <!-- 2. ANNONCES CLASSIQUES (AVEC PRIORISATION DES BANNIÈRES) -->
                    <?php include 'partials/_search_annonce.php'; ?>

                    <!-- 3. RECHERCHES CONNEXES / AUTRES VILLES EN BAS -->
                    <?php include 'partials/_search_connexes.php'; ?>

                    <!-- 4. PAGINATION STYLE GOOGLE -->
                    <?php include 'partials/_search_paging.php'; ?>

                <?php else: ?>
                    <div style="max-width: 850px; margin: 20px auto; background: #ffffff; border: 1px solid #cbd5e1; padding: 40px; border-radius: 8px; text-align: center; color: #64748b;">
                        <div style="font-size: 2.5rem; margin-bottom: 10px;">🔍</div>
                        <h3 style="color: #1e293b; margin: 0 0 8px 0;">Aucun objet ne correspond à votre recherche à <?= htmlspecialchars($nom_ville_selectionnee) ?></h3>
                        <p style="margin: 0 0 15px 0; font-size: 0.9rem;">Essayez de vérifier l'orthographe ou de chercher dans toutes les villes.</p>
                    </div>

                    <?php include 'partials/_search_connexes.php'; ?>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
    const btnPlus = document.getElementById('btn-toggle-cat');
    const menuDropdown = document.getElementById('menu-dropdown-cat');
    const inputCatId = document.getElementById('input-cat-id');
    const badgeCat = document.getElementById('badge-cat');
    const texteBadge = document.getElementById('texte-badge-cat');

    btnPlus.addEventListener('click', function(e) {
        e.stopPropagation();
        menuDropdown.classList.toggle('ouvert');
    });

    function selectionnerCategorie(idCat, nomCat) {
        inputCatId.value = idCat;
        if (idCat > 0) {
            texteBadge.textContent = nomCat;
            badgeCat.style.display = 'inline-flex';
        } else {
            badgeCat.style.display = 'none';
        }
        menuDropdown.classList.remove('ouvert');
        verifierCompteResultats();
    }

    function retirerFiltreCategorie(e) {
        e.stopPropagation();
        inputCatId.value = 0;
        badgeCat.style.display = 'none';
        verifierCompteResultats();
    }

    document.addEventListener('click', function() {
        menuDropdown.classList.remove('ouvert');
    });

    function verifierCompteResultats() {
        const q = document.getElementById('input-q-search').value.trim();
        const cat = document.getElementById('input-cat-id').value;
        const ville = document.getElementById('select-ville-search').value;
        const btnSubmit = document.getElementById('btn-submit-search');
        const msgDiv = document.getElementById('msg-ajax-search');

        if (q.length === 0 && cat == 0 && ville == 0) {
            btnSubmit.disabled = false;
            btnSubmit.style.opacity = '1';
            btnSubmit.style.cursor = 'pointer';
            btnSubmit.style.backgroundColor = '#2563eb';
            btnSubmit.innerHTML = '🔍 Rechercher';
            msgDiv.textContent = '';
            return;
        }

        fetch(`compter_resultats_search.php?q=${encodeURIComponent(q)}&cat=${cat}&ville=${ville}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'succes') {
                    const total = data.total;
                    if (total > 0) {
                        btnSubmit.disabled = false;
                        btnSubmit.style.opacity = '1';
                        btnSubmit.style.cursor = 'pointer';
                        btnSubmit.style.backgroundColor = '#2563eb';
                        btnSubmit.innerHTML = `🔍 Rechercher (${total})`;
                        msgDiv.style.color = '#16a34a';
                        msgDiv.textContent = `✅ ${total} annonce(s) disponible(s) selon vos critères.`;
                    } else {
                        btnSubmit.disabled = true;
                        btnSubmit.style.opacity = '0.5';
                        btnSubmit.style.cursor = 'not-allowed';
                        btnSubmit.style.backgroundColor = '#64748b';
                        btnSubmit.innerHTML = '🔍 Rechercher (0)';
                        msgDiv.style.color = '#dc2626';
                        msgDiv.textContent = '❌ Aucun résultat disponible pour cette sélection.';
                    }
                }
            })
            .catch(err => console.error(err));
    }

    document.getElementById('input-q-search').addEventListener('input', verifierCompteResultats);
    </script>
</body>
</html>
