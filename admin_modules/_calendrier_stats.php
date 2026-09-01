<?php
// =============================================================================
// NOM DU SCRIPT : admin_modules/_calendrier_stats.php
// REVISION     : 1.3 - Isolation AJAX + Pagination Dynamique du jour
// SCRIPT COMPLET ET SUIVI
// =============================================================================

// -----------------------------------------------------------------------------
// A. TRAITEMENT EXCLUSIF DE LA REQUÊTE AJAX (TABLEAU DU JOUR PAGINÉ)
// -----------------------------------------------------------------------------
if (isset($_GET['ajax_date'])) {
    
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    require_once __DIR__ . '/../config.php';

    $date_cible = $_GET['ajax_date'];
    $cat_cible  = $_GET['ajax_cat'] ?? 'all';
    $page       = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limite     = 10; // Nombre d'annonces par page
    $offset     = ($page - 1) * $limite;

    if (isset($bdd)) {
        try {
            // 1. Compter le total d'annonces pour ce jour
            $sql_count = "SELECT COUNT(*) FROM jevend_annonces WHERE DATE(date_creation) = :date_jour";
            $params_count = ['date_jour' => $date_cible];

            if ($cat_cible !== 'all' && is_numeric($cat_cible)) {
                $sql_count .= " AND id_categorie = :cat";
                $params_count['cat'] = (int)$cat_cible;
            }

            $stmt_count = $bdd->prepare($sql_count);
            $stmt_count->execute($params_count);
            $total_annonces_jour = (int)$stmt_count->fetchColumn();
            $total_pages = ceil($total_annonces_jour / $limite);

            // 2. Récupérer la tranche de 10 annonces
            $sql_ajax = "SELECT a.titre_objet_nettoye, a.prix, a.date_creation, c.nom_fr as nom_categorie, u.nom as nom_utilisateur 
                         FROM jevend_annonces a
                         LEFT JOIN jevend_categories c ON a.id_categorie = c.id_categorie
                         LEFT JOIN jevend_utilisateurs u ON a.id_utilisateur = u.id_utilisateur
                         WHERE DATE(a.date_creation) = :date_jour";
            
            $params_ajax = ['date_jour' => $date_cible];

            if ($cat_cible !== 'all' && is_numeric($cat_cible)) {
                $sql_ajax .= " AND a.id_categorie = :cat";
                $params_ajax['cat'] = (int)$cat_cible;
            }

            $sql_ajax .= " ORDER BY a.date_creation DESC LIMIT :limite OFFSET :offset";

            $stmt_ajax = $bdd->prepare($sql_ajax);
            foreach ($params_ajax as $key => $val) {
                $stmt_ajax->bindValue($key, $val);
            }
            $stmt_ajax->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt_ajax->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt_ajax->execute();

            $annonces_jour = $stmt_ajax->fetchAll(PDO::FETCH_ASSOC);

            if (count($annonces_jour) > 0) {
                echo '<table style="width:100%; border-collapse:collapse; background:#fff; font-size:0.85rem; border:1px solid #cbd5e1; margin-top:10px;">';
                echo '<tr style="background:#0f172a; color:#fff;"><th style="padding:8px; text-align:left;">Heure</th><th style="padding:8px; text-align:left;">Titre</th><th style="padding:8px; text-align:left;">Catégorie</th><th style="padding:8px; text-align:left;">Membre</th><th style="padding:8px; text-align:right;">Prix Demand&eacute;</th></tr>';
                foreach ($annonces_jour as $ann) {
                    $heure = date('H:i', strtotime($ann['date_creation']));
                    echo '<tr style="border-bottom:1px solid #e2e8f0;">';
                    echo '<td style="padding:8px;">' . $heure . '</td>';
                    echo '<td style="padding:8px; font-weight:bold;">' . htmlspecialchars($ann['titre_objet_nettoye']) . '</td>';
                    echo '<td style="padding:8px;">' . htmlspecialchars($ann['nom_categorie'] ?? 'N/A') . '</td>';
                    echo '<td style="padding:8px;">' . htmlspecialchars($ann['nom_utilisateur'] ?? 'Anonyme') . '</td>';
                    echo '<td style="padding:8px; text-align:right; font-weight:bold; color:#166534;">' . number_format($ann['prix'], 2) . ' $</td>';
                    echo '</tr>';
                }
                echo '</table>';

                // Barre de pagination
                if ($total_pages > 1) {
                    echo '<div style="display:flex; justify-content:space-between; align-items:center; margin-top:12px; font-size:0.85rem;">';
                    echo '<span style="color:#64748b; font-weight:bold;">Page ' . $page . ' sur ' . $total_pages . ' (' . $total_annonces_jour . ' publications)</span>';
                    echo '<div style="display:flex; gap:6px;">';

                    if ($page > 1) {
                        echo '<button onclick="chargerDetailJour(\'' . $date_cible . '\', \'' . $cat_cible . '\', ' . ($page - 1) . ')" style="padding:5px 10px; background:#e2e8f0; border:none; border-radius:4px; font-weight:bold; cursor:pointer;">◄ Précédent</button>';
                    }

                    if ($page < $total_pages) {
                        echo '<button onclick="chargerDetailJour(\'' . $date_cible . '\', \'' . $cat_cible . '\', ' . ($page + 1) . ')" style="padding:5px 10px; background:#2563eb; color:#fff; border:none; border-radius:4px; font-weight:bold; cursor:pointer;">Suivant ►</button>';
                    }

                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<div style="color:#64748b; font-style:italic; padding:10px;">Aucune annonce publiée à cette date.</div>';
            }
        } catch (PDOException $e) {
            echo '<div style="color:red; padding:10px;">Erreur SQL : ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
    
    exit();
}

// -----------------------------------------------------------------------------
// B. AFFICHAGE DE L'INTERFACE COMPLÈTE DU CALENDRIER
// -----------------------------------------------------------------------------

$mois_actuel    = isset($_GET['cal_mois']) ? (int)$_GET['cal_mois'] : (int)date('m');
$annee_actuelle = isset($_GET['cal_annee']) ? (int)$_GET['cal_annee'] : (int)date('Y');
$id_cat_filtre  = isset($_GET['cal_cat']) ? $_GET['cal_cat'] : 'all';

$date_objet = DateTime::createFromFormat('Y-n-d', "$annee_actuelle-$mois_actuel-01");
if (!$date_objet) {
    $date_objet = new DateTime();
    $mois_actuel = (int)$date_objet->format('m');
    $annee_actuelle = (int)$date_objet->format('Y');
}

$mois_prev = (clone $date_objet)->modify('-1 month');
$mois_next = (clone $date_objet)->modify('+1 month');

$liste_categories = [];
if (isset($bdd)) {
    try {
        $stmt_cat = $bdd->query("SELECT id_categorie, nom_fr FROM jevend_categories ORDER BY nom_fr ASC");
        $liste_categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { }
}

$stats_jours = [];
$total_mois = 0;

if (isset($bdd)) {
    try {
        $sql = "SELECT DAY(date_creation) as jour, COUNT(*) as nb 
                FROM jevend_annonces 
                WHERE MONTH(date_creation) = :mois AND YEAR(date_creation) = :annee";
        
        $params = [
            'mois'  => $mois_actuel,
            'annee' => $annee_actuelle
        ];

        if ($id_cat_filtre !== 'all' && is_numeric($id_cat_filtre)) {
            $sql .= " AND id_categorie = :cat";
            $params['cat'] = (int)$id_cat_filtre;
        }

        $sql .= " GROUP BY DAY(date_creation)";
        
        $stmt_stats = $bdd->prepare($sql);
        $stmt_stats->execute($params);
        
        while ($row = $stmt_stats->fetch(PDO::FETCH_ASSOC)) {
            $stats_jours[(int)$row['jour']] = (int)$row['nb'];
            $total_mois += (int)$row['nb'];
        }
    } catch (PDOException $e) { }
}

$premier_jour_semaine = (int)$date_objet->format('N');
$nb_jours_dans_mois   = (int)$date_objet->format('t');
$nom_mois_fr = [
    1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
    5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
    9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
];
?>

<style>
    .cal-conteneur { background: #ffffff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .cal-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; }
    .cal-titre { font-size: 1.4rem; font-weight: bold; color: #0f172a; margin: 0; }
    .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; }
    .cal-entete-jour { background: #0f172a; color: #ffffff; text-align: center; padding: 10px; font-weight: bold; font-size: 0.85rem; border-radius: 4px; }
    .cal-jour { min-height: 80px; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px; background: #ffffff; position: relative; cursor: pointer; transition: all 0.2s; }
    .cal-jour:hover { border-color: #2563eb; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
    .cal-jour.vide { background: #f8fafc; border-color: #f1f5f9; cursor: default; }
    .cal-num-jour { font-weight: bold; font-size: 0.9rem; color: #334155; }
    .cal-badge { display: inline-block; padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; margin-top: 10px; }
    .heat-0 { background: #f1f5f9; color: #94a3b8; }
    .heat-low { background: #dcfce7; color: #166534; }
    .heat-high { background: #15803d; color: #ffffff; }
    .cal-detail-boite { margin-top: 25px; padding: 15px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; display: none; }
</style>

<div class="cal-conteneur">
    
    <div class="cal-header">
        <div>
            <h2 class="cal-titre">📅 Calendrier des Parutions (<?= $nom_mois_fr[$mois_actuel] . ' ' . $annee_actuelle ?>)</h2>
            <span style="font-size:0.9rem; color:#64748b;">Total pour la période : <strong><?= $total_mois ?></strong> annonce(s)</span>
        </div>

        <form method="GET" action="panneau.php" style="display:flex; gap:10px; align-items:center;">
            <select name="cal_cat" onchange="window.location.href='panneau.php?cal_mois=<?= $mois_actuel ?>&cal_annee=<?= $annee_actuelle ?>&cal_cat=' + this.value + '#onglet-calendrier-stats';" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-weight:600;">
                <option value="all" <?= $id_cat_filtre === 'all' ? 'selected' : '' ?>>-- Toutes les catégories --</option>
                <?php foreach ($liste_categories as $cat): ?>
                    <option value="<?= $cat['id_categorie'] ?>" <?= (string)$id_cat_filtre === (string)$cat['id_categorie'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nom_fr']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <a href="panneau.php?cal_mois=<?= $mois_prev->format('n') ?>&cal_annee=<?= $mois_prev->format('Y') ?>&cal_cat=<?= $id_cat_filtre ?>#onglet-calendrier-stats" style="padding:8px 12px; background:#e2e8f0; color:#1e293b; text-decoration:none; border-radius:6px; font-weight:bold;">◄ Mois Préc.</a>
            <a href="panneau.php?cal_mois=<?= $mois_next->format('n') ?>&cal_annee=<?= $mois_next->format('Y') ?>&cal_cat=<?= $id_cat_filtre ?>#onglet-calendrier-stats" style="padding:8px 12px; background:#e2e8f0; color:#1e293b; text-decoration:none; border-radius:6px; font-weight:bold;">Mois Suiv. ►</a>
        </form>
    </div>

    <div class="cal-grid">
        <div class="cal-entete-jour">Lun</div>
        <div class="cal-entete-jour">Mar</div>
        <div class="cal-entete-jour">Mer</div>
        <div class="cal-entete-jour">Jeu</div>
        <div class="cal-entete-jour">Ven</div>
        <div class="cal-entete-jour">Sam</div>
        <div class="cal-entete-jour">Dim</div>

        <?php
        for ($i = 1; $i < $premier_jour_semaine; $i++) {
            echo '<div class="cal-jour vide"></div>';
        }

        for ($j = 1; $j <= $nb_jours_dans_mois; $j++) {
            $nb_annonces = isset($stats_jours[$j]) ? $stats_jours[$j] : 0;
            
            $classe_heat = 'heat-0';
            if ($nb_annonces > 0 && $nb_annonces < 5) $classe_heat = 'heat-low';
            if ($nb_annonces >= 5) $classe_heat = 'heat-high';

            $date_complete = sprintf('%04d-%02d-%02d', $annee_actuelle, $mois_actuel, $j);
            ?>
            <div class="cal-jour" onclick="chargerDetailJour('<?= $date_complete ?>', '<?= $id_cat_filtre ?>', 1)">
                <div class="cal-num-jour"><?= $j ?></div>
                <?php if ($nb_annonces > 0): ?>
                    <span class="cal-badge <?= $classe_heat ?>"><?= $nb_annonces ?> pub.</span>
                <?php endif; ?>
            </div>
            <?php
        }
        ?>
    </div>

    <div id="zone-detail-jour" class="cal-detail-boite">
        <h4 style="margin-top:0; color:#0f172a;" id="titre-detail-jour">📋 Publications du jour</h4>
        <div id="contenu-detail-jour">Sélectionnez une date dans le calendrier pour voir les annonces.</div>
    </div>

</div>

<script>
function chargerDetailJour(dateJour, catFiltre, page = 1) {
    const zone = document.getElementById('zone-detail-jour');
    const titre = document.getElementById('titre-detail-jour');
    const contenu = document.getElementById('contenu-detail-jour');
    
    zone.style.display = 'block';
    titre.innerText = '📋 Publications du ' + dateJour;
    contenu.innerHTML = '<em>Chargement des annonces en cours...</em>';

    fetch('admin_modules/_calendrier_stats.php?ajax_date=' + dateJour + '&ajax_cat=' + catFiltre + '&page=' + page)
        .then(response => response.text())
        .then(html => {
            contenu.innerHTML = html;
        })
        .catch(err => {
            contenu.innerHTML = '<span style="color:red;">Erreur lors du chargement des détails.</span>';
        });
}
</script>
