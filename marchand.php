<?php
// =============================================================================
// NOM DU SCRIPT : marchand.php
// REVISION : 1.1 - Vitrine d'affaires complète (Fiche profil, Horaires, Photo du commerce, Coordonnées & Catalogue)
// =============================================================================
session_start();
require_once 'config.php';
require_once 'fonctions_geoloc.php';

$id_marchand = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_marchand <= 0) {
    header('Location: index.php');
    exit();
}

$marchand = null;
$annonces_marchand = [];
$nom_ville = "Localisation non précisée";

try {
    // 1. Extraction complète de la fiche du marchand
    $stmt_m = $bdd->prepare("
        SELECT u.*, v.nom_ville 
        FROM jevend_utilisateurs u
        LEFT JOIN jevend_villes v ON u.id_ville = v.id_ville
        WHERE u.id_utilisateur = ?
    ");
    $stmt_m->execute([$id_marchand]);
    $marchand = $stmt_m->fetch(PDO::FETCH_ASSOC);

    if (!$marchand) {
        die("Commerçant introuvable ou compte inexistant.");
    }

    if (!empty($marchand['nom_ville'])) {
        $nom_ville = $marchand['nom_ville'];
    }

    // 2. Extraction de toutes les annonces actives de ce marchand
    $stmt_a = $bdd->prepare("
        SELECT * FROM jevend_annonces 
        WHERE id_utilisateur = ? AND statut = 'actif'
        ORDER BY date_creation DESC
    ");
    $stmt_a->execute([$id_marchand]);
    $annonces_marchand = $stmt_a->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur lors du chargement de la vitrine marchand : " . $e->getMessage());
}

$nom_entreprise = !empty($marchand['nom_entreprise']) ? $marchand['nom_entreprise'] : $marchand['nom'];
$description    = !empty($marchand['description_entreprise']) ? $marchand['description_entreprise'] : "Bienvenue sur la vitrine officielle de notre commerce.";
$photo_commerce = !empty($marchand['photo_commerce']) && file_exists("uploads/" . $marchand['photo_commerce']) ? "uploads/" . $marchand['photo_commerce'] : null;
$adresse_physique = $marchand['adresse'] ?? '';
$telephone_fixe  = $marchand['telephone'] ?? '';
$cellulaire      = $marchand['cellulaire'] ?? $marchand['telephone_cell'] ?? '';
$heures_ouverture = $marchand['heures_ouverture'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($nom_entreprise) ?> — Vitrine Officielle sur jevend.com</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .marchand-wrapper {
            max-width: 1200px;
            margin: 25px auto;
            padding: 0 15px;
            box-sizing: border-box;
        }

        /* DISPOSITION PRINCIPALE : 2 COLONNES */
        .marchand-grid-layout {
            display: grid;
            grid-template-columns: 360px 1fr;
            gap: 25px;
            align-items: start;
        }

        /* FICHE DU COMMERCE (SIDEBAR GAUCHE) */
        .card-profil-commerce {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            position: sticky;
            top: 20px;
        }

        .facade-img-box {
            width: 100%;
            height: 200px;
            background: #0f172a;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .facade-img-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profil-corps {
            padding: 20px;
        }

        .badge-pro-officiel {
            background: #7c3aed;
            color: #ffffff;
            font-size: 0.7rem;
            font-weight: bold;
            padding: 3px 10px;
            border-radius: 20px;
            text-transform: uppercase;
            display: inline-block;
            margin-bottom: 8px;
        }

        .nom-commerce {
            margin: 0 0 10px 0;
            font-size: 1.5rem;
            color: #0f172a;
            line-height: 1.2;
        }

        .description-commerce {
            font-size: 0.88rem;
            color: #475569;
            line-height: 1.5;
            margin-bottom: 20px;
            background: #f8fafc;
            padding: 12px;
            border-radius: 6px;
            border-left: 3px solid #2563eb;
        }

        /* BLOC COORDONNÉES ET CONTACTS */
        .bloc-info-ligne {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 12px;
            font-size: 0.9rem;
            color: #334155;
        }

        .bloc-info-ligne span.icon {
            font-size: 1.1rem;
            min-width: 20px;
        }

        /* BOUTONS D'ACTION RAPIDE */
        .grid-actions-contact {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin: 20px 0;
        }

        .btn-contact-quick {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 0.85rem;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .btn-tel { background: #16a34a; color: #ffffff; }
        .btn-tel:hover { background: #15803d; }

        .btn-sms { background: #2563eb; color: #ffffff; }
        .btn-sms:hover { background: #1d4ed8; }

        .btn-gps { background: #0f172a; color: #ffffff; grid-column: span 2; }
        .btn-gps:hover { background: #1e293b; }

        .btn-web { background: #7c3aed; color: #ffffff; grid-column: span 2; }
        .btn-web:hover { background: #6d28d9; }

        /* BLOC HORAIRES D'OUVERTURE */
        .box-horaires {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 15px;
            margin-top: 15px;
        }

        .box-horaires h4 {
            margin: 0 0 10px 0;
            font-size: 0.9rem;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* ZONE PRINCIPALE (CATALOGUE PRODUITS) */
        .section-titre-catalogue {
            font-size: 1.4rem;
            color: #0f172a;
            margin: 0 0 20px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
        }

        .flux-grille {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 20px;
        }

        .carte-annonce {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease;
        }

        .carte-annonce:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .carte-image-zone {
            width: 100%;
            height: 170px;
            background-color: #ffffff;
            border-bottom: 1px solid #f1f5f9;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 5px;
            box-sizing: border-box;
        }

        .carte-image-zone img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .carte-corps {
            padding: 12px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .carte-titre {
            font-size: 0.95rem;
            font-weight: bold;
            color: #1e293b;
            margin: 0 0 6px 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 2.3rem;
        }

        .carte-prix {
            font-size: 1.1rem;
            font-weight: bold;
            color: #16a34a;
            margin-top: auto;
        }

        .carte-actions {
            border-top: 1px solid #f1f5f9;
            padding: 8px 12px;
        }

        @media (max-width: 900px) {
            .marchand-grid-layout {
                grid-template-columns: 1fr;
            }
            .card-profil-commerce {
                position: static;
            }
        }
    </style>
</head>
<body class="admin-body">

    <?php include 'partials/_nav_publique.php'; ?>

    <div class="marchand-wrapper">
        <div class="marchand-grid-layout">
            
            <!-- 1. FICHE COMMERCIALE (SIDEBAR GAUCHE) -->
            <div class="card-profil-commerce">
                <!-- FAÇADE / PHOTO DU COMMERCE -->
                <div class="facade-img-box">
                    <?php if ($photo_commerce): ?>
                        <img src="<?= htmlspecialchars($photo_commerce) ?>" alt="Façade <?= htmlspecialchars($nom_entreprise) ?>">
                    <?php else: ?>
                        <div style="color: #94a3b8; font-size: 0.9rem; text-align: center; padding: 20px;">
                            🏢<br>Photo du commerce non disponible
                        </div>
                    <?php endif; ?>
                </div>

                <div class="profil-corps">
                    <?php if (($marchand['type_compte'] ?? '') === 'pro'): ?>
                        <span class="badge-pro-officiel">🏢 Compte Marchand Officiel</span>
                    <?php endif; ?>

                    <h1 class="nom-commerce"><?= htmlspecialchars($nom_entreprise) ?></h1>

                    <div class="description-commerce">
                        <?= nl2br(htmlspecialchars($description)) ?>
                    </div>

                    <!-- DÉTAILS DES COORDONNÉES -->
                    <div class="bloc-info-ligne">
                        <span class="icon">📍</span>
                        <div>
                            <strong>Adresse :</strong><br>
                            <?= !empty($adresse_physique) ? htmlspecialchars($adresse_physique) . '<br>' : '' ?>
                            <?= htmlspecialchars($nom_ville) ?>
                        </div>
                    </div>

                    <?php if (!empty($telephone_fixe)): ?>
                        <div class="bloc-info-ligne">
                            <span class="icon">📞</span>
                            <div>
                                <strong>Téléphone bureau :</strong><br>
                                <?= htmlspecialchars($telephone_fixe) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($cellulaire)): ?>
                        <div class="bloc-info-ligne">
                            <span class="icon">📱</span>
                            <div>
                                <strong>Cellulaire / SMS :</strong><br>
                                <?= htmlspecialchars($cellulaire) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- BOUTONS D'ACTION RAPIDE (REROUTAGE DIRECT SUR MOBILE) -->
                    <div class="grid-actions-contact">
                        <?php if (!empty($telephone_fixe) || !empty($cellulaire)): ?>
                            <?php $num_appel = !empty($cellulaire) ? $cellulaire : $telephone_fixe; ?>
                            <a href="tel:<?= preg_replace('/[^0-9+]/', '', $num_appel) ?>" class="btn-contact-quick btn-tel">
                                📞 Appeler
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($cellulaire)): ?>
                            <a href="sms:<?= preg_replace('/[^0-9+]/', '', $cellulaire) ?>" class="btn-contact-quick btn-sms">
                                💬 SMS
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($adresse_physique)): ?>
                            <?php $requete_gps = urlencode($adresse_physique . ', ' . $nom_ville); ?>
                            <a href="https://www.google.com/maps/search/?api=1&query=<?= $requete_gps ?>" target="_blank" class="btn-contact-quick btn-gps">
                                🗺️ Obtenir l'itinéraire
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($marchand['site_web'])): ?>
                            <?php $url_web = (strpos($marchand['site_web'], 'http') === 0) ? $marchand['site_web'] : 'https://' . $marchand['site_web']; ?>
                            <a href="<?= htmlspecialchars($url_web) ?>" target="_blank" rel="noopener" class="btn-contact-quick btn-web">
                                🌐 Visiter le site web officiel
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- BLOC HEURES D'OUVERTURE -->
                    <div class="box-horaires">
                        <h4>🕒 Horaires d'ouverture</h4>
                        <?php if (!empty($heures_ouverture)): ?>
                            <div style="font-size: 0.85rem; color: #475569; line-height: 1.5; white-space: pre-line;">
                                <?= htmlspecialchars($heures_ouverture) ?>
                            </div>
                        <?php else: ?>
                            <div style="font-size: 0.8rem; color: #94a3b8; font-style: italic;">
                                Contactez l'établissement pour connaître les heures d'ouverture.
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

            <!-- 2. CATALOGUE D'ARTICLES DU COMMERÇANT (ZONE PRINCIPALE) -->
            <div>
                <div class="section-titre-catalogue">
                    <span>🛍️ Articles & Services en Vitrine</span>
                    <span style="font-size: 0.9rem; color: #64748b; font-weight: normal; background: #f1f5f9; padding: 4px 10px; border-radius: 20px;">
                        <strong><?= count($annonces_marchand) ?></strong> article(s)
                    </span>
                </div>

                <?php if (empty($annonces_marchand)): ?>
                    <div style="background: #ffffff; padding: 50px 20px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center; color: #64748b;">
                        <div style="font-size: 3rem; margin-bottom: 10px;">📦</div>
                        <p style="font-size: 1.1rem; margin: 0 0 5px 0; color: #0f172a; font-weight: bold;">Aucune annonce en vitrine pour le moment</p>
                        <small>Revenez bientôt pour découvrir les nouveaux arrivages de cet établissement.</small>
                    </div>
                <?php else: ?>
                    <div class="flux-grille">
                        <?php foreach ($annonces_marchand as $annonce): ?>
                            <?php 
                                $fichier_image = !empty($annonce['image_courante']) ? $annonce['image_courante'] : '';
                                $chemin_complet_image = "uploads/" . $fichier_image; 
                            ?>
                            <div class="carte-annonce">
                                <div class="carte-image-zone">
                                    <?php if(!empty($annonce['image_courante']) && file_exists($chemin_complet_image)): ?>
                                        <img src="<?= htmlspecialchars($chemin_complet_image) ?>" alt="<?= htmlspecialchars($annonce['titre_objet_nettoye']) ?>">
                                    <?php else: ?>
                                        <div style="color: #94a3b8; font-size: 0.8rem; text-align: center;">📸 Pas de photo</div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="carte-corps">
                                    <h3 class="carte-titre" title="<?= htmlspecialchars($annonce['titre_objet_nettoye']) ?>">
                                        <?= htmlspecialchars($annonce['titre_objet_nettoye']) ?>
                                    </h3>
                                    <div class="carte-prix"><?= number_format($annonce['prix'], 2, ',', ' ') ?> $</div>
                                </div>

                                <div class="carte-actions">
                                    <a href="details.php?id=<?= $annonce['id_annonces'] ?>" class="btn-action" style="margin:0; padding:6px 10px; font-size:0.8rem; text-decoration:none; display:block; text-align:center;">
                                        👁️ Voir les détails
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

</body>
</html>
