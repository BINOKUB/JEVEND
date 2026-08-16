<?php
// =============================================================================
// MODULE : _admin_membres.php
// REVISION : 1.0 - Gestion des membres avec Pagination Haute Performance et Recherche
// NOM DU SCRIPT : admin_modules/_admin_membres.php
// =============================================================================
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { exit(); }

// 1. TRAITEMENT DU CHANGEMENT DE STATUT (ACTIVER / BLOQUER)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_statut'])) {
    $id_m = (int)($_POST['id_membre'] ?? 0);
    $nouveau_statut = $_POST['statut_cible'] === 'bloque' ? 'bloque' : 'actif';
    $courriel_admin_supreme = 'douimet61@gmail.com';

    if ($id_m > 0) {
        try {
            // Sécurité : On vérifie que l'admin ne se bloque pas lui-même
            $check_not_self = $bdd->prepare("SELECT courriel FROM jevend_utilisateurs WHERE id_utilisateur = ?");
            $check_not_self->execute([$id_m]);
            $cible = $check_not_self->fetch(PDO::FETCH_ASSOC);

            if ($cible && $cible['courriel'] !== $courriel_admin_supreme) {
                $update = $bdd->prepare("UPDATE jevend_utilisateurs SET statut = ? WHERE id_utilisateur = ?");
                $update->execute([$nouveau_statut, $id_m]);
            }
        } catch (PDOException $e) {
            // Gestion d'erreur silencieuse ou affichage discret
        }
    }
}

// 2. CONFIGURATION DE LA RECHERCHE ET PAGINATION
$recherche = trim($_GET['recherche'] ?? '');
$page_actuelle = max(1, (int)($_GET['p'] ?? 1));
$membres_par_page = 20;
$offset = ($page_actuelle - 1) * $membres_par_page;

$membres = [];
$total_membres = 0;

try {
    // Construction de la requête avec filtre de recherche
    $sql_where = "";
    $params = [];
    
    if (!empty($recherche)) {
        $sql_where = "WHERE u.nom LIKE ? OR u.courriel LIKE ? OR u.cellulaire LIKE ? ";
        $params = ["%$recherche%", "%$recherche%", "%$recherche%"];
    }

    // Compter le nombre total de lignes correspondantes pour la pagination
    $sql_count = "SELECT COUNT(*) FROM jevend_utilisateurs u $sql_where";
    $stmt_count = $bdd->prepare($sql_count);
    $stmt_count->execute($params);
    $total_membres = (int)$stmt_count->fetchColumn();

    // Calcul du nombre total de pages
    $total_pages = max(1, ceil($total_membres / $membres_par_page));
    $page_actuelle = min($page_actuelle, $total_pages); // Évite de dépasser

    // Récupération des données par bloc (LIMIT / OFFSET) avec liaison Ville
    $sql_select = "
        SELECT u.id_utilisateur, u.nom, u.courriel, u.cellulaire, u.role, u.statut, u.date_inscription, v.nom_ville 
        FROM jevend_utilisateurs u
        INNER JOIN jevend_villes v ON u.id_ville = v.id_ville
        $sql_where
        ORDER BY u.date_inscription DESC
        LIMIT $membres_par_page OFFSET $offset
    ";
    
    $stmt_select = $bdd->prepare($sql_select);
    $stmt_select->execute($params);
    $membres = $stmt_select->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "<div style='color: #991b1b; background: #fef2f2; padding: 15px; border-radius: 6px;'>⚠️ Erreur SQL Membres : " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

<div class="admin-bloc-vide" style="background: #ffffff; padding: 25px; border: 1px solid #e2e8f0; border-radius: 8px; text-align: left; color: inherit; box-sizing: border-box; width: 100%;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 20px;">
        <h3 style="color: #1e3a8a; margin: 0; font-size: 1.3rem;">👥 Contrôle et Modération des Membres (<?= $total_membres ?>)</h3>
        
        <!-- BARRE DE RECHERCHE -->
        <form action="panneau.php" method="GET" style="display: flex; gap: 8px; max-width: 400px; width: 100%;">
            <input type="text" name="recherche" placeholder="Nom, courriel, tel..." value="<?= htmlspecialchars($recherche) ?>" style="flex: 1; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem;">
            <button type="submit" style="background: #1e3a8a; color: white; border: none; padding: 8px 15px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 0.9rem;">🔍</button>
            <?php if (!empty($recherche)): ?>
                <a href="panneau.php" style="background: #e2e8f0; color: #475569; padding: 8px 12px; border-radius: 6px; text-decoration: none; font-size: 0.9rem; font-weight: bold;">X</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- STYLE INJECTÉ POUR LA CONVERSION EN CARTES MOBILES -->
    <style>
        .table-responsive-admin { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table-responsive-admin th, .table-responsive-admin td { padding: 12px; border-bottom: 1px solid #e2e8f0; text-align: left; font-size: 0.9rem; }
        .table-responsive-admin th { background-color: #f8fafc; color: #475569; font-weight: bold; }
        
        .badge-statut { padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 0.75rem; text-transform: uppercase; display: inline-block; }
        .badge-actif { background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .badge-bloque { background-color: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

        .btn-toggle-statut { border: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 0.8rem; cursor: pointer; transition: background 0.2s; color: white; }
        .btn-block { background-color: #ef4444; } .btn-block:hover { background-color: #dc2626; }
        .btn-activate { background-color: #22c55e; } .btn-activate:hover { background-color: #16a34a; }

        @media (max-width: 900px) {
            .table-responsive-admin thead { display: none; }
            .table-responsive-admin tr { display: block; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
            .table-responsive-admin td { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: none; border-top: 1px solid #f1f5f9; font-size: 0.85rem; }
            .table-responsive-admin td:first-child { border-top: none; font-size: 1rem; font-weight: bold; color: #0f172a; }
            .table-responsive-admin td::before { content: attr(data-label); font-weight: bold; color: #64748b; margin-right: 15px; }
            .table-responsive-admin td .btn-toggle-statut { width: 100%; text-align: center; margin-top: 8px; padding: 10px; }
        }
    </style>

    <?php if (empty($membres)): ?>
        <div style="text-align: center; color: #94a3b8; padding: 40px 10px; font-style: italic;">
            Aucun membre trouvé correspondant à vos critères.
        </div>
    <?php else: ?>
        <table class="table-responsive-admin">
            <thead>
                <tr>
                    <th>Membre / Nom</th>
                    <th>Courriel</th>
                    <th>Cellulaire</th>
                    <th>Ville</th>
                    <th>Rôle</th>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($membres as $m): ?>
                    <tr>
                        <td data-label="Nom" style="font-weight: bold; color: #0f172a;"><?= htmlspecialchars($m['nom']) ?></td>
                        <td data-label="Courriel"><?= htmlspecialchars($m['courriel']) ?></td>
                        <td data-label="Cellulaire"><?= htmlspecialchars($m['cellulaire']) ?></td>
                        <td data-label="Ville">📍 <?= htmlspecialchars($m['nom_ville']) ?></td>
                        <td data-label="Rôle"><code style="background: #f1f5f9; padding: 2px 4px; border-radius: 3px; font-weight: bold;"><?= htmlspecialchars($m['role']) ?></code></td>
                        <td data-label="Statut">
                            <span class="badge-statut <?= $m['statut'] === 'actif' ? 'badge-actif' : 'badge-bloque' ?>">
                                <?= $m['statut'] === 'actif' ? 'Actif' : 'Bloqué' ?>
                            </span>
                        </td>
                        <td data-label="Action">
                            <?php if ($m['courriel'] === 'douimet61@gmail.com'): ?>
                                <span style="font-size: 0.8rem; color: #94a3b8; font-style: italic;">Créateur Suprême</span>
                            <?php else: ?>
                                <form action="panneau.php?recherche=<?= urlencode($recherche) ?>&p=<?= $page_actuelle ?>" method="POST" style="margin: 0; width: 100%;">
                                    <input type="hidden" name="action_statut" value="1">
                                    <input type="hidden" name="id_membre" value="<?= $m['id_utilisateur'] ?>">
                                    <?php if ($m['statut'] === 'actif'): ?>
                                        <input type="hidden" name="statut_cible" value="bloque">
                                        <button type="submit" class="btn-toggle-statut btn-block">🚫 Bloquer</button>
                                    <?php else: ?>
                                        <input type="hidden" name="statut_cible" value="actif">
                                        <button type="submit" class="btn-toggle-statut btn-activate">✅ Réactiver</button>
                                    <?php endif; ?>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- ZONE DE PAGINATION NUMÉRIQUE -->
        <?php if ($total_pages > 1): ?>
            <div style="display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 25px; flex-wrap: wrap;">
                
                <?php if ($page_actuelle > 1): ?>
                    <a href="panneau.php?recherche=<?= urlencode($recherche) ?>&p=<?= $page_actuelle - 1 ?>" style="padding: 6px 12px; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 4px; text-decoration: none; color: #334155; font-size: 0.85rem; font-weight: bold;">« Précédent</a>
                <?php endif; ?>

                <span style="font-size: 0.9rem; color: #64748b; font-weight: bold; padding: 0 10px;">
                    Page <?= $page_actuelle ?> sur <?= $total_pages ?>
                </span>

                <?php if ($page_actuelle < $total_pages): ?>
                    <a href="panneau.php?recherche=<?= urlencode($recherche) ?>&p=<?= $page_actuelle + 1 ?>" style="padding: 6px 12px; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 4px; text-decoration: none; color: #334155; font-size: 0.85rem; font-weight: bold;">Suivant »</a>
                <?php endif; ?>

            </div>
        <?php endif; ?>

    <?php endif; ?>

</div>
