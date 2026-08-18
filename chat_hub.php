<?php
// =============================================================================
// NOM DU SCRIPT : chat_hub.php
// DESCRIPTION  : Hub centralisateur unifié de toutes les conversations (Annonces & Recherches)
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
    </style>
</head>
<body class="admin-body">

    <?php include 'partials/_nav_membre.php'; ?>

    <div class="liste-conv-container">
        <h2 style="margin-top: 0; color: #0f172a; font-size: 1.3rem;">💬 Centre de messagerie unifié</h2>

        <?php if (empty($conversations)): ?>
            <p style="color: #64748b; text-align: center; margin: 30px 0;">Vous n'avez aucun échange en cours pour le moment.</p>
        <?php else: ?>
            <?php foreach ($conversations as $c): ?>
                <?php 
                    // Routage dynamique selon la nature de la conversation
                    if (!empty($c['id_annonce'])) {
                        $lien_chat = "chat_membre.php?id_annonce=" . $c['id_annonce'] . "&avec=" . $c['autre_id'];
                        $badge_type = "📦 [Annonce]";
                    } else {
                        $lien_chat = "chat_recherche.php?id_recherche=" . $c['id_recherche'] . "&avec=" . $c['autre_id'];
                        $badge_type = "🎯 [Je Cherche]";
                    }
                ?>
                <a href="<?= $lien_chat ?>" class="item-conv <?= ($c['non_lu'] > 0) ? 'non-lu' : '' ?>">
                    <div>
                        <div style="font-size: 0.75rem; font-weight: bold; color: #64748b; margin-bottom: 2px;"><?= $badge_type ?></div>
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

</body>
</html>
