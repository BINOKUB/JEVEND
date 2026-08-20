<?php
// =============================================================================
// NOM DU SCRIPT : chat_hub.php
// REVISION     : 2.0 - Hub unifié avec sections pliables par catégorie et tri chronologique
// =============================================================================
session_start();
require_once 'config.php';

$id_utilisateur = $_SESSION['id_utilisateur'] ?? null;
if (!$id_utilisateur) {
    header("Location: connexion.php");
    exit();
}

// Extraction de toutes les conversations confondues (Annonces et Recherches)
try {
    $stmt = $bdd->prepare("
        SELECT c.id_annonce, 
               c.id_recherche,
               c.id_expediteur, 
               c.id_destinataire,
               c.date_envoi,
               c.lu,
               COALESCE(a.titre_objet_nettoye, r.titre_recherche) AS titre_contexte,
               u_exp.nom AS expediteur_nom,
               u_dest.nom AS destinataire_nom
        FROM jevend_chat c
        LEFT JOIN jevend_annonces a ON c.id_annonce = a.id_annonces
        LEFT JOIN jevend_recherches r ON c.id_recherche = r.id_recherche
        JOIN jevend_utilisateurs u_exp ON c.id_expediteur = u_exp.id_utilisateur
        JOIN jevend_utilisateurs u_dest ON c.id_destinataire = u_dest.id_utilisateur
        WHERE c.id_expediteur = :id_user OR c.id_destinataire = :id_user
        ORDER BY c.date_envoi DESC
    ");
    $stmt->execute([':id_user' => $id_utilisateur]);
    $tous_messages = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

// Regroupement par contexte + interlocuteur
$conversations = [];
foreach ($tous_messages as $m) {
    $autre_id = ($m['id_expediteur'] == $id_utilisateur) ? $m['id_destinataire'] : $m['id_expediteur'];
    $type_contexte = !empty($m['id_annonce']) ? 'annonce_' . $m['id_annonce'] : 'recherche_' . $m['id_recherche'];
    $cle = $type_contexte . '_' . $autre_id;

    if (!isset($conversations[$cle])) {
        $conversations[$cle] = [
            'id_annonce' => $m['id_annonce'],
            'id_recherche' => $m['id_recherche'],
            'autre_id' => $autre_id,
            'autre_nom' => ($m['id_expediteur'] == $id_utilisateur) ? $m['destinataire_nom'] : $m['expediteur_nom'],
            'titre' => $m['titre_contexte'] ?? 'Discussion',
            'date_dernier' => $m['date_envoi'],
            'non_lu' => ($m['id_destinataire'] == $id_utilisateur && $m['lu'] == 0) ? 1 : 0
        ];
    } elseif ($m['id_destinataire'] == $id_utilisateur && $m['lu'] == 0) {
        $conversations[$cle]['non_lu']++;
    }
}

// Séparation en deux catégories distinctes
$conv_annonces = [];
$conv_recherches = [];

foreach ($conversations as $c) {
    if (!empty($c['id_annonce'])) {
        $conv_annonces[] = $c;
    } else {
        $conv_recherches[] = $c;
    }
}

// Tri chronologique : Du plus récent au plus ancien dans chaque catégorie
usort($conv_annonces, function($a, $b) {
    return strtotime($b['date_dernier']) - strtotime($a['date_dernier']);
});

usort($conv_recherches, function($a, $b) {
    return strtotime($b['date_dernier']) - strtotime($a['date_dernier']);
});

// Comptage des non lus pour les badges d'en-tête
$non_lus_annonces = array_sum(array_column($conv_annonces, 'non_lu'));
$non_lus_recherches = array_sum(array_column($conv_recherches, 'non_lu'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centre de messagerie — jevend.com</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .liste-conv-container {
            max-width: 650px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            padding: 20px;
        }
        .accordion-section {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            margin-bottom: 15px;
            overflow: hidden;
        }
        .accordion-summary {
            padding: 12px 15px;
            font-weight: bold;
            color: #0f172a;
            cursor: pointer;
            background-color: #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            user-select: none;
            transition: background 0.15s ease;
            font-size: 0.95rem;
        }
        .accordion-summary:hover {
            background-color: #e2e8f0;
        }
        .accordion-content {
            padding: 10px;
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
        }
        .item-conv {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            border-bottom: 1px solid #e2e8f0;
            text-decoration: none;
            color: #1e293b;
            border-radius: 6px;
            transition: background 0.15s ease;
        }
        .item-conv:last-child {
            border-bottom: none;
        }
        .item-conv:hover { background-color: #f1f5f9; }
        .item-conv.non-lu { background-color: #fef2f2; border-left: 4px solid #ef4444; }
        .badge-count {
            background-color: #ef4444;
            color: #ffffff;
            font-size: 0.75rem;
            font-weight: bold;
            padding: 2px 8px;
            border-radius: 10px;
        }
        .badge-section {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: 10px;
        }
    </style>
</head>
<body class="admin-body">

    <?php include 'partials/_nav_membre.php'; ?>

    <div class="liste-conv-container">
        <h2 style="margin-top: 0; color: #0f172a; font-size: 1.3rem; margin-bottom: 20px;">💬 Centre de messagerie unifié</h2>

        <?php if (empty($conversations)): ?>
            <p style="color: #64748b; text-align: center; margin: 30px 0;">Vous n'avez aucun échange en cours pour le moment.</p>
        <?php else: ?>

            <!-- SECTION 1 : ANNONCES (FERMÉE PAR DÉFAUT) -->
            <details class="accordion-section">
                <summary class="accordion-summary">
                    <span>📦 Conversations « Annonces » <span class="badge-section"><?= count($conv_annonces) ?></span></span>
                    <div>
                        <?php if ($non_lus_annonces > 0): ?>
                            <span class="badge-count" style="margin-right: 8px;"><?= $non_lus_annonces ?> non lu(s)</span>
                        <?php endif; ?>
                        <span style="font-size: 0.8rem; color: #64748b;">▼ Déplier</span>
                    </div>
                </summary>
                <div class="accordion-content">
                    <?php if (empty($conv_annonces)): ?>
                        <p style="color: #64748b; text-align: center; padding: 15px; font-size: 0.85rem; margin: 0;">Aucune conversation liée à une annonce.</p>
                    <?php else: ?>
                        <?php foreach ($conv_annonces as $c): ?>
                            <a href="chat_membre.php?id_annonce=<?= $c['id_annonce'] ?>&avec=<?= $c['autre_id'] ?>" class="item-conv <?= ($c['non_lu'] > 0) ? 'non-lu' : '' ?>">
                                <div>
                                    <strong><?= htmlspecialchars($c['titre']) ?></strong>
                                    <div style="font-size: 0.85rem; color: #64748b;">Avec : <?= htmlspecialchars($c['autre_nom']) ?></div>
                                </div>
                                <div>
                                    <?php if ($c['non_lu'] > 0): ?>
                                        <span class="badge-count"><?= $c['non_lu'] ?> nouveau(x)</span>
                                    <?php else: ?>
                                        <span style="font-size: 0.8rem; color: #94a3b8;"><?= date('d/m H:i', strtotime($c['date_dernier'])) ?></span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </details>

            <!-- SECTION 2 : RECHERCHES (FERMÉE PAR DÉFAUT) -->
            <details class="accordion-section">
                <summary class="accordion-summary">
                    <span>🎯 Conversations « Je Cherche » <span class="badge-section"><?= count($conv_recherches) ?></span></span>
                    <div>
                        <?php if ($non_lus_recherches > 0): ?>
                            <span class="badge-count" style="margin-right: 8px;"><?= $non_lus_recherches ?> non lu(s)</span>
                        <?php endif; ?>
                        <span style="font-size: 0.8rem; color: #64748b;">▼ Déplier</span>
                    </div>
                </summary>
                <div class="accordion-content">
                    <?php if (empty($conv_recherches)): ?>
                        <p style="color: #64748b; text-align: center; padding: 15px; font-size: 0.85rem; margin: 0;">Aucune conversation liée à une recherche.</p>
                    <?php else: ?>
                        <?php foreach ($conv_recherches as $c): ?>
                            <a href="chat_recherche.php?id_recherche=<?= $c['id_recherche'] ?>&avec=<?= $c['autre_id'] ?>" class="item-conv <?= ($c['non_lu'] > 0) ? 'non-lu' : '' ?>">
                                <div>
                                    <strong><?= htmlspecialchars($c['titre']) ?></strong>
                                    <div style="font-size: 0.85rem; color: #64748b;">Avec : <?= htmlspecialchars($c['autre_nom']) ?></div>
                                </div>
                                <div>
                                    <?php if ($c['non_lu'] > 0): ?>
                                        <span class="badge-count"><?= $c['non_lu'] ?> nouveau(x)</span>
                                    <?php else: ?>
                                        <span style="font-size: 0.8rem; color: #94a3b8;"><?= date('d/m H:i', strtotime($c['date_dernier'])) ?></span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </details>

        <?php endif; ?>
    </div>

</body>
</html>
