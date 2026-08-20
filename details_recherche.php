<?php
// =============================================================================
// NOM DU SCRIPT : details_recherche.php
// REVISION : 1.8 - Intégration propre du bouton de chat web direct
// =============================================================================
session_start();
require_once 'config.php';

// NETOIE LE CHAT QUAND LE CHERCHEUR CLOTURE SA DEMANDE.
//include 'partials/_cloture_recherche.php';

$id_utilisateur_connecte = $_SESSION['id_utilisateur'] ?? null;
$id_recherche = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['id_recherche']) ? (int)$_GET['id_recherche'] : 0);

$erreur_fatale = "";
$erreur = "";
$succes = "";

// EXTRACTION DES DÉTAILS DE LA RECHERCHE
$demande = null;
if ($id_recherche > 0) {
    try {
        $stmt = $bdd->prepare("
            SELECT r.*, 
                   COALESCE(v.nom_ville, 'Ville non spécifiée') AS nom_ville, 
                   c.nom_fr AS nom_categorie, 
                   c.parent_id,
                   p.nom_fr AS nom_parent_categorie,
                   COALESCE(u.nom, 'Utilisateur inconnu') AS nom_acheteur, 
                   u.cellulaire AS cellulaire_acheteur
            FROM jevend_recherches r
            LEFT JOIN jevend_villes v ON r.id_ville = v.id_ville
            LEFT JOIN jevend_categories c ON r.id_categorie = c.id_categorie
            LEFT JOIN jevend_categories p ON c.parent_id = p.id_categorie
            LEFT JOIN jevend_utilisateurs u ON r.id_utilisateur = u.id_utilisateur
            WHERE r.id_recherche = ?
        ");
        $stmt->execute([$id_recherche]);
        $demande = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $erreur_fatale = "Erreur SQL (Chargement demande) : " . $e->getMessage();
    }
}

if ($erreur_fatale) {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head><meta charset="UTF-8"><title>Erreur — jevend.com</title><link rel="stylesheet" href="style.css"></head>
    <body class="admin-body" style="padding: 40px; text-align: center;">
        <div style="max-width: 600px; margin: 0 auto; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 25px; border-radius: 8px;">
            <h3>⚠️ Oups ! Un problème technique est survenu</h3>
            <p style="font-family: monospace; font-size: 0.9rem; background: #fff; padding: 10px; border-radius: 4px; border: 1px solid #fca5a5;"><?= htmlspecialchars($erreur_fatale) ?></p>
            <a href="zone_cherche.php" style="display: inline-block; margin-top: 15px; background: #0f172a; color: #fff; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: bold;">← Retour à la zone Je Cherche</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

if (!$demande) {
    header("Location: zone_cherche.php");
    exit();
}

$est_auteur = ($id_utilisateur_connecte && $id_utilisateur_connecte == $demande['id_utilisateur']);


// Construction de l'affichage de la catégorie (Parent / Sous-catégorie)
$affichage_categorie = 'Général';
if ($demande) {
    if (!empty($demande['parent_id']) && $demande['parent_id'] > 0 && !empty($demande['nom_parent_categorie'])) {
        $affichage_categorie = htmlspecialchars($demande['nom_parent_categorie']) . ' / ' . htmlspecialchars($demande['nom_categorie']);
    } else {
        $affichage_categorie = htmlspecialchars($demande['nom_categorie'] ?? 'Général');
    }
}


// TRAITEMENT : CLÔTURE ET SUPPRESSION TOTALE DE LA RECHERCHE (IMAGE + CHAT + RÉPONSES + LIGNE)

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_marquer_trouve'])) {
    if ($est_auteur) {
        try {
            // 0. Récupérer le nom de l'image de référence pour l'effacer du serveur
            $stmt_img = $bdd->prepare("SELECT image_reference FROM jevend_recherches WHERE id_recherche = ?");
            $stmt_img->execute([$id_recherche]);
            $img_ref = $stmt_img->fetchColumn();

            if (!empty($img_ref) && file_exists('uploads/' . $img_ref)) {
                @unlink('uploads/' . $img_ref); // Suppression physique du fichier image
            }

            // 1. Supprimer les messages de chat liés à cette recherche
            $stmt_del_chat = $bdd->prepare("DELETE FROM jevend_chat WHERE id_recherche = ?");
            $stmt_del_chat->execute([$id_recherche]);

            // 2. Supprimer les propositions des vendeurs pour éviter les données orphelines
            $stmt_del_rep = $bdd->prepare("DELETE FROM jevend_reponses_recherche WHERE id_recherche = ?");
            $stmt_del_rep->execute([$id_recherche]);

            // 3. Supprimer définitivement la recherche de la base de données
            $stmt_del_rech = $bdd->prepare("DELETE FROM jevend_recherches WHERE id_recherche = ?");
            $stmt_del_rech->execute([$id_recherche]);

            // 4. Redirection propre vers la zone avec un message de succès
            header("Location: zone_cherche.php?succes_cloture=1");
            exit();

        } catch (PDOException $e) {
            $erreur = "Erreur lors de la suppression de la recherche : " . $e->getMessage();
        }
    }
}

// EXTRACTION DES PROPOSITIONS REÇUES AVEC CELLULAIRE DU VENDEUR
$propositions_recues = [];
if ($est_auteur) {
    try {
        $stmt_prop = $bdd->prepare("
            SELECT rr.*, u.nom AS nom_vendeur, u.cellulaire AS cellulaire_vendeur, 
                   a.titre_objet_nettoye AS titre_annonce, a.prix AS prix_annonce, a.id_annonces
            FROM jevend_reponses_recherche rr
            LEFT JOIN jevend_utilisateurs u ON rr.id_vendeur = u.id_utilisateur
            LEFT JOIN jevend_annonces a ON rr.id_annonce_associee = a.id_annonces
            WHERE rr.id_recherche = ?
            ORDER BY rr.id_reponse DESC
        ");
        $stmt_prop->execute([$id_recherche]);
        $propositions_recues = $stmt_prop->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { }
}

// EXTRACTION DES ANNONCES ACTIVES DU VENDEUR CONNECTÉ - ON NE MELANGE PAS LES JE RECHERCHE AUX ANNONCES
/************************************************************************* DESACTIVÉ
$mes_annonces = [];
if ($id_utilisateur_connecte && !$est_auteur) {
    try {
        $stmt_a = $bdd->prepare("
            SELECT id_annonces, titre_objet_nettoye, prix 
            FROM jevend_annonces 
            WHERE id_utilisateur = ? AND statut = 'actif' 
            ORDER BY date_creation DESC
        ");
        $stmt_a->execute([$id_utilisateur_connecte]);
        $mes_annonces = $stmt_a->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { }
}

*********************************************************************/


// TRAITEMENT DE LA PROPOSITION DU VENDEUR (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_repondre_recherche'])) {
    if (!$id_utilisateur_connecte) {
        $url_retour = urlencode($_SERVER['REQUEST_URI']);
        header("Location: connexion.php?redirect=" . $url_retour);
        exit();
    }

    if ($est_auteur) {
        $erreur = "Vous ne pouvez pas répondre à votre propre demande d'achat.";
    } else {
        $id_annonce_associee = NULL; // Plus d'association d'annonce
        $message_vendeur     = trim($_POST['message_vendeur'] ?? '');

        if (empty($message_vendeur)) {
            $erreur = "Veuillez rédiger un message à l'acheteur.";
        } else {
            try {
                $stmt_ins = $bdd->prepare("
                    INSERT INTO jevend_reponses_recherche (id_recherche, id_vendeur, id_annonce_associee, message_vendeur)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt_ins->execute([
                    $id_recherche,
                    $id_utilisateur_connecte,
                    $id_annonce_associee,
                    $message_vendeur
                ]);

                $succes = "Votre proposition a été transmise avec succès à " . htmlspecialchars($demande['nom_acheteur']) . " !";
            } catch (PDOException $e) {
                $erreur = "Une erreur est survenue lors de l'envoi de votre proposition : " . $e->getMessage();
            }
        }
    }
}

$maintenant = new DateTime();
$dt_exp = new DateTime($demande['date_expiration']);
$diff = $maintenant->diff($dt_exp);
$jours_restants = ($maintenant < $dt_exp) ? $diff->days : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($demande['titre_recherche']) ?> — La Zone Je Cherche</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .conteneur-details-recherche {
            max-width: 950px;
            margin: 30px auto 60px auto;
            padding: 0 15px;
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 25px;
        }

        .carte-principale-demande {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-top: 5px solid #f59e0b;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .badge-tag-zone {
            display: inline-block;
            background: #f59e0b;
            color: #0f172a;
            font-size: 0.75rem;
            font-weight: 800;
            padding: 3px 8px;
            border-radius: 4px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .titre-details-demande {
            font-size: 1.8rem;
            font-weight: 900;
            color: #0f172a;
            margin: 0 0 15px 0;
            line-height: 1.2;
        }

        .metas-details-ligne {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            font-size: 0.88rem;
            color: #64748b;
            padding-bottom: 15px;
            border-bottom: 1px dashed #e2e8f0;
            margin-bottom: 20px;
        }

        .cadre-image-ref {
            width: 100%;
            max-height: 350px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        .cadre-image-ref img {
            max-width: 100%;
            max-height: 350px;
            object-fit: contain;
        }

        .description-texte-demande {
            font-size: 0.98rem;
            color: #334155;
            line-height: 1.6;
            white-space: pre-line;
            margin-bottom: 20px;
        }

        .panneau-lateral-proposition {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-top: 5px solid #16a34a;
            border-radius: 8px;
            padding: 22px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            height: fit-content;
        }

        .panneau-lateral-proposition h3 {
            margin: 0 0 10px 0;
            font-size: 1.2rem;
            color: #0f172a;
        }

        .bloc-budget-affichage {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            padding: 12px;
            border-radius: 6px;
            text-align: center;
            margin-bottom: 18px;
        }
        .valeur-budget-chiffre {
            font-size: 1.6rem;
            font-weight: 900;
            color: #16a34a;
        }

        .champ-groupe-prop {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 15px;
        }
        .champ-groupe-prop label {
            font-weight: bold;
            font-size: 0.85rem;
            color: #1e293b;
        }
        .champ-groupe-prop select,
        .champ-groupe-prop textarea {
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 0.9rem;
            outline: none;
        }

        .btn-envoyer-prop {
            width: 100%;
            background-color: #16a34a;
            color: #ffffff;
            border: none;
            padding: 12px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 0.95rem;
            cursor: pointer;
            transition: background 0.15s ease;
        }
        .btn-envoyer-prop:hover { background-color: #15803d; }

        @media (max-width: 800px) {
            .conteneur-details-recherche { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="admin-body">

    <?php include 'partials/_nav_publique.php'; ?>
    <?php include 'partials/_ticker_je_cherche.php'; ?>

    <div class="conteneur-details-recherche">
        
        <!-- COLONNE GAUCHE : DÉTAILS DE LA DEMANDE -->
        <div class="carte-principale-demande">
            <span class="badge-tag-zone">🎯 DEMANDE ACHETEUR</span>
            
            <h1 class="titre-details-demande"><?= htmlspecialchars($demande['titre_recherche']) ?></h1>

            <div class="metas-details-ligne">
                <span>👤 Acheteur : <strong><?= htmlspecialchars($demande['nom_acheteur']) ?></strong></span>
                <span>📍 Municipalité : <strong style="color:#0284c7;"><?= htmlspecialchars($demande['nom_ville']) ?></strong></span>
                <span>📁 Catégorie : <strong><?= $affichage_categorie ?></strong></span>
                <span>🕒 Reste : <strong><?= $jours_restants ?> jour(s)</strong></span>
            </div>

            <?php if (!empty($demande['image_reference']) && file_exists('uploads/' . $demande['image_reference'])): ?>
                <div class="cadre-image-ref">
                    <img src="uploads/<?= htmlspecialchars($demande['image_reference']) ?>" alt="Photo de référence">
                </div>
            <?php endif; ?>

            <h3 style="color:#0f172a; margin-top:0;">Détails & exigences de l'acheteur :</h3>
            <div class="description-texte-demande">
                <?= !empty($demande['description']) ? htmlspecialchars($demande['description']) : '<i>Aucune précision supplémentaire fournie par l\'acheteur.</i>' ?>
            </div>

            <a href="zone_cherche.php" style="color:#2563eb; text-decoration:none; font-weight:bold; font-size:0.9rem;">
                ← Retour à toutes les demandes "Je Cherche"
            </a>
        </div>

        <!-- COLONNE DROITE : PANNEAU LATÉRAL -->
        <div class="panneau-lateral-proposition">
            
            <div class="bloc-budget-affichage">
                <div style="font-size:0.78rem; color:#166534; font-weight:bold; text-transform:uppercase;">Budget proposé par l'acheteur</div>
                <div class="valeur-budget-chiffre">
                    <?= (!empty($demande['budget_max']) && $demande['budget_max'] > 0) ? number_format((float)$demande['budget_max'], 2, ',', ' ') . ' $' : 'Budget ouvert' ?>
                </div>
            </div>

            <?php if (!empty($erreur)): ?>
                <div style="background-color: #fef2f2; color: #991b1b; padding: 10px; border-radius: 6px; font-size: 0.85rem; margin-bottom: 15px; border: 1px solid #fecaca; font-weight: bold; text-align: center;">
                    ⚠️ <?= htmlspecialchars($erreur) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($succes)): ?>
                <div style="background-color: #f0fdf4; color: #166534; padding: 12px; border-radius: 6px; font-size: 0.88rem; margin-bottom: 15px; border: 1px solid #bbf7d0; font-weight: bold; text-align: center;">
                    🚀 <?= htmlspecialchars($succes) ?>
                </div>
            <?php endif; ?>

            <!-- SI C'EST L'AUTEUR : GESTION ET PROPOSITIONS REÇUES -->
            <?php if ($est_auteur): ?>
                
                <!-- BOUTON DE CLÔTURE ANTICIPÉE -->
                <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 12px; border-radius: 6px; margin-bottom: 18px; text-align: center;">
                    <h4 style="margin: 0 0 8px 0; color: #0f172a; font-size: 0.95rem;">⚙️ Statut de votre recherche</h4>
                    <?php if ($demande['statut'] === 'actif'): ?>
                        <form action="details_recherche.php?id=<?= $id_recherche ?>" method="POST" onsubmit="return confirm('Voulez-vous vraiment marquer cette recherche comme résolue ? Elle ne sera plus visible publiquement.');">
                            <input type="hidden" name="action_marquer_trouve" value="1">
                            <button type="submit" style="width: 100%; background-color: #0284c7; color: #fff; border: none; padding: 10px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 0.85rem; transition: background 0.15s;">
                                🎉 J'ai trouvé ! Clôturer
                            </button>
                        </form>
                    <?php else: ?>
                        <div style="background: #e0f2fe; color: #0369a1; padding: 8px; border-radius: 4px; font-size: 0.82rem; font-weight: bold;">
                            🔒 Demande clôturée (Résolue)
                        </div>
                    <?php endif; ?>
                </div>

                <h3 style="font-size: 1.1rem; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 15px;">📬 Propositions (<?= count($propositions_recues) ?>)</h3>
                
                <?php if (empty($propositions_recues)): ?>
                    <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 6px; font-size: 0.85rem; color: #64748b; text-align: center;">
                        ℹ️ Aucune proposition reçue pour le moment. Votre demande est active et visible par les vendeurs.
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php foreach ($propositions_recues as $prop): ?>
                            <div style="background: #f8fafc; border: 1px solid #cbd5e1; padding: 12px; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.02);">
                                <div style="font-size: 0.85rem; font-weight: bold; color: #2563eb; margin-bottom: 4px;">
                                    👤 <?= htmlspecialchars($prop['nom_vendeur'] ?? 'Utilisateur') ?>
                                </div>
                                
                                <?php if (!empty($prop['id_annonce_associee'])): ?>
                                    <div style="background: #e0f2fe; padding: 6px 8px; border-radius: 4px; font-size: 0.8rem; margin-bottom: 6px; color: #0369a1;">
                                        📦 Annonce liée : <a href="annonce.php?id=<?= $prop['id_annonces'] ?>" target="_blank" style="font-weight: bold; color: #0369a1; text-decoration: underline;"><?= htmlspecialchars($prop['titre_annonce']) ?> (<?= number_format((float)$prop['prix'], 2, ',', ' ') ?> $)</a>
                                    </div>
                                <?php endif; ?>

                                <p style="font-size: 0.88rem; color: #334155; margin: 0 0 10px 0; line-height: 1.4; white-space: pre-line;">
                                    <?= htmlspecialchars($prop['message_vendeur']) ?>
                                </p>

                                <!-- CONTACT DIRECT PAR CELLULAIRE / SMS -->
                                <?php if (!empty($prop['cellulaire_vendeur'])): ?>
                                    <div style="display: flex; gap: 8px; margin-bottom: 8px;">
                                        <a href="tel:<?= htmlspecialchars($prop['cellulaire_vendeur']) ?>" style="flex: 1; text-align: center; background-color: #16a34a; color: #fff; text-decoration: none; padding: 7px; border-radius: 4px; font-size: 0.78rem; font-weight: bold;">
                                            📞 Appeler
                                        </a>
                                        <a href="sms:<?= htmlspecialchars($prop['cellulaire_vendeur']) ?>?body=Bonjour, suite a votre proposition sur jevend.com pour : <?= urlencode($demande['titre_recherche']) ?>" style="flex: 1; text-align: center; background-color: #0284c7; color: #fff; text-decoration: none; padding: 7px; border-radius: 4px; font-size: 0.78rem; font-weight: bold;">
                                            💬 SMS
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <!-- BOUTON DE CHAT WEB EN DIRECT -->
                                <div>
                                    <a href="chat_recherche.php?id_recherche=<?= $id_recherche ?>&avec=<?= $prop['id_vendeur'] ?>" style="display: block; text-align: center; background-color: #2563eb; color: #fff; text-decoration: none; padding: 8px; border-radius: 4px; font-size: 0.82rem; font-weight: bold;">
                                        💬 Discuter en direct (Chat)
                                    </a>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            <!-- SI C'EST UN AUTRE UTILISATEUR CONNECTÉ : FORMULAIRE DE PROPOSITION -->
            <?php elseif ($id_utilisateur_connecte): ?>
                <h3>💬 Vous avez cet objet ?</h3>
                
                <form action="details_recherche.php?id=<?= $id_recherche ?>" method="POST">
                    <input type="hidden" name="action_repondre_recherche" value="1">

                   
                    <div class="champ-groupe-prop">
                        <label for="message_vendeur">Votre message à l'acheteur : *</label>
                        <textarea name="message_vendeur" id="message_vendeur" rows="4" placeholder="Bonjour ! J'ai cet objet disponible. Appelez-moi ou textotez-moi au..." required></textarea>
                    </div>

                    <button type="submit" class="btn-envoyer-prop">
                        🤝 Transmettre ma proposition
                    </button>
                </form>

            <!-- SI VISITEUR NON CONNECTÉ -->
            <?php else: ?>
                <h3>💬 Vous avez cet objet ?</h3>
                <div style="text-align: center; background-color: #f8fafc; padding: 15px; border-radius: 6px; border: 1px solid #e2e8f0;">
                    <p style="font-size: 0.88rem; color: #475569; margin: 0 0 12px 0;">Vous devez être connecté pour proposer un objet à <?= htmlspecialchars($demande['nom_acheteur']) ?>.</p>
                    <a href="connexion.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn-envoyer-prop" style="display: inline-block; text-decoration: none;">
                        🔑 Se connecter en 1 clic
                    </a>
                </div>
            <?php endif; ?>

        </div>

    </div>

</body>
</html>
