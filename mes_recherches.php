<?php
// =============================================================================
// NOM DU SCRIPT : mes_recherches.php
// REVISION : 1.1 - Historique personnel avec système d'onglets ergonomiques
// =============================================================================
session_start();
require_once 'config.php';

$id_utilisateur_connecte = $_SESSION['id_utilisateur'] ?? null;

if (!$id_utilisateur_connecte) {
    header("Location: connexion.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// EXTRACTION EXCLUSIVE DES RECHERCHES DE L'UTILISATEUR CONNECTÉ
$toutes_les_demandes = [];
try {
    $stmt = $bdd->prepare("
        SELECT r.*, v.nom_ville, c.nom_fr AS nom_categorie
        FROM jevend_recherches r
        JOIN jevend_villes v ON r.id_ville = v.id_ville
        JOIN jevend_categories c ON r.id_categorie = c.id_categorie
        WHERE r.id_utilisateur = ?
        ORDER BY r.date_creation DESC
    ");
    $stmt->execute([$id_utilisateur_connecte]);
    $toutes_les_demandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erreur = "Erreur lors du chargement de vos recherches.";
}

$maintenant = new DateTime();

// TRAITEMENT DES STATUTS ET COMPTEURS POUR LES ONGLETS
$demandes_traitees = [];
$compteurs = ['tous' => 0, 'actif' => 0, 'trouve' => 0, 'expire' => 0];

foreach ($toutes_les_demandes as $d) {
    $statut_reel = $d['statut'];
    $dt_exp = new DateTime($d['date_expiration']);
    
    if ($statut_reel === 'actif' && $maintenant > $dt_exp) {
        $statut_reel = 'expire';
    }
    
    $d['statut_reel'] = $statut_reel;
    $demandes_traitees[] = $d;

    // Incrémentation des compteurs
    $compteurs['tous']++;
    if (isset($compteurs[$statut_reel])) {
        $compteurs[$statut_reel]++;
    }
}

// GESTION DE L'ONGLET ACTIF DANS L'URL (?tab=tous|actif|trouve|expire)
$onglet_actif = $_GET['tab'] ?? 'tous';
if (!array_key_exists($onglet_actif, $compteurs)) {
    $onglet_actif = 'tous';
}

// FILTRAGE SELON L'ONGLET SÉLECTIONNÉ
$mes_demandes = array_filter($demandes_traitees, function($d) use ($onglet_actif) {
    if ($onglet_actif === 'tous') return true;
    return $d['statut_reel'] === $onglet_actif;
});
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes recherches — jevend.com</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .conteneur-mes-recherches { max-width: 950px; margin: 30px auto 60px auto; padding: 0 15px; }
        .titre-page-profil { font-size: 1.8rem; font-weight: 900; color: #0f172a; margin-bottom: 8px; }
        
        /* STYLE DES ONGLETS */
        .onglets-profil-barre {
            display: flex;
            gap: 8px;
            margin: 20px 0 25px 0;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 0;
            flex-wrap: wrap;
        }
        .onglet-lien {
            padding: 10px 18px;
            font-size: 0.9rem;
            font-weight: 700;
            text-decoration: none;
            color: #64748b;
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-bottom: none;
            border-radius: 8px 8px 0 0;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .onglet-lien:hover {
            color: #0f172a;
            background-color: #e2e8f0;
        }
        .onglet-lien.actif {
            color: #2563eb;
            background-color: #ffffff;
            border-color: #cbd5e1;
            border-bottom: 2px solid #ffffff;
            margin-bottom: -2px;
        }
        .badge-compteur-tab {
            background: #e2e8f0;
            color: #334155;
            font-size: 0.75rem;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: bold;
        }
        .onglet-lien.actif .badge-compteur-tab {
            background: #dbeafe;
            color: #1d4ed8;
        }

        /* CARTES DE L'HISTORIQUE */
        .carte-recherche-ligne { background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 15px 20px; display: flex; align-items: center; justify-content: space-between; gap: 15px; margin-bottom: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); transition: border-color 0.15s ease; }
        .carte-recherche-ligne:hover { border-color: #2563eb; }
        .infos-principales-rech { flex-grow: 1; display: flex; flex-direction: column; gap: 4px; }
        .titre-rech-lien { font-size: 1.1rem; font-weight: bold; color: #1e293b; text-decoration: none; }
        .titre-rech-lien:hover { color: #2563eb; text-decoration: underline; }
        .meta-rech-ligne { font-size: 0.85rem; color: #64748b; display: flex; gap: 10px; flex-wrap: wrap; }
        .badge-statut-rech { font-size: 0.75rem; font-weight: 800; padding: 3px 8px; border-radius: 4px; text-transform: uppercase; width: fit-content; }
        .badge-actif { background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-trouve { background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .badge-expire { background-color: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .actions-rech-droite { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
        .btn-action-rech { background-color: #2563eb; color: #ffffff; text-decoration: none; padding: 8px 14px; border-radius: 6px; font-size: 0.85rem; font-weight: bold; transition: background 0.15s ease; }
        .btn-action-rech:hover { background-color: #1d4ed8; }
        
        @media (max-width: 768px) {
            .carte-recherche-ligne { flex-direction: column; align-items: flex-start; }
            .actions-rech-droite { width: 100%; justify-content: space-between; border-top: 1px dashed #e2e8f0; padding-top: 10px; }
            .onglets-profil-barre { flex-direction: column; gap: 4px; border-bottom: none; }
            .onglet-lien { border-radius: 6px; border: 1px solid #cbd5e1; }
            .onglet-lien.actif { border-bottom: 1px solid #cbd5e1; margin-bottom: 0; }
        }
    </style>
</head>
<body class="admin-body">

    <?php include 'partials/_nav_publique.php'; ?>
    <?php include 'partials/_ticker_je_cherche.php'; ?>

    <div class="conteneur-mes-recherches">
        <h1 class="titre-page-profil">📋 Mes demandes « Je Cherche »</h1>
        <div style="font-size: 0.95rem; color: #64748b;">
            Retrouvez ici l'historique de vos recherches publiées, suivez leur statut et consultez les propositions des vendeurs.
        </div>

        <!-- BARRE D'ONGLETS DE FILTRAGE -->
        <div class="onglets-profil-barre">
            <a href="mes_recherches.php?tab=tous" class="onglet-lien <?= $onglet_actif === 'tous' ? 'actif' : '' ?>">
                📂 Toutes <span class="badge-compteur-tab"><?= $compteurs['tous'] ?></span>
            </a>
            <a href="mes_recherches.php?tab=actif" class="onglet-lien <?= $onglet_actif === 'actif' ? 'actif' : '' ?>">
                🟢 Actives <span class="badge-compteur-tab"><?= $compteurs['actif'] ?></span>
            </a>
            <a href="mes_recherches.php?tab=trouve" class="onglet-lien <?= $onglet_actif === 'trouve' ? 'actif' : '' ?>">
                🔵 Trouvées <span class="badge-compteur-tab"><?= $compteurs['trouve'] ?></span>
            </a>
            <a href="mes_recherches.php?tab=expire" class="onglet-lien <?= $onglet_actif === 'expire' ? 'actif' : '' ?>">
                🔴 Expirées <span class="badge-compteur-tab"><?= $compteurs['expire'] ?></span>
            </a>
        </div>

        <?php if (empty($mes_demandes)): ?>
            <div style="background: #ffffff; border: 1px solid #cbd5e1; padding: 40px; border-radius: 8px; text-align: center; color: #64748b;">
                <div style="font-size: 2.5rem; margin-bottom: 10px;">🎯</div>
                <h3 style="color: #0f172a; margin: 0 0 8px 0;">Aucune demande dans cet onglet</h3>
                <p style="margin: 0 0 15px 0; font-size: 0.9rem;">Vous cherchez un objet en particulier ? Publiez un besoin pour que les vendeurs vous contactent.</p>
                <a href="poster_recherche.php" class="btn-action-rech" style="display: inline-block;">🎯 Publier une recherche</a>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <?php foreach ($mes_demandes as $d): ?>
                    <?php $statut = $d['statut_reel']; ?>
                    <div class="carte-recherche-ligne">
                        <div class="infos-principales-rech">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                                <?php if ($statut === 'actif'): ?>
                                    <span class="badge-statut-rech badge-actif">🟢 Actif</span>
                                <?php elseif ($statut === 'trouve'): ?>
                                    <span class="badge-statut-rech badge-trouve">🔵 Trouvé (Résolu)</span>
                                <?php else: ?>
                                    <span class="badge-statut-rech badge-expire">🔴 Expiré</span>
                                <?php endif; ?>
                                <span style="font-size: 0.8rem; color: #94a3b8;">Créé le <?= date('d/m/Y', strtotime($d['date_creation'])) ?></span>
                            </div>

                            <a href="details_recherche.php?id=<?= $d['id_recherche'] ?>" class="titre-rech-lien">
                                <?= htmlspecialchars($d['titre_recherche']) ?>
                            </a>

                            <div class="meta-rech-ligne">
                                <span>📁 <?= htmlspecialchars($d['nom_categorie']) ?></span>
                                <span>📍 <?= htmlspecialchars($d['nom_ville']) ?></span>
                                <span>💰 Budget : <strong><?= (!empty($d['budget_max']) && $d['budget_max'] > 0) ? number_format((float)$d['budget_max'], 2, ',', ' ') . ' $' : 'Ouvert' ?></strong></span>
                            </div>
                        </div>

                        <div class="actions-rech-droite">
                            <a href="details_recherche.php?id=<?= $d['id_recherche'] ?>" class="btn-action-rech">
                                👁️ Voir les propositions
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div style="margin-top: 25px;">
            <a href="zone_cherche.php" style="color: #2563eb; text-decoration: none; font-weight: bold; font-size: 0.9rem;">
                ← Retour à la zone publique "Je Cherche"
            </a>
        </div>
    </div>

</body>
</html>
