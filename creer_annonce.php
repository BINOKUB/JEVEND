<?php
session_start();

// Forcer l'affichage des erreurs PHP pour le débogage local
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// =============================================================================
// NOM DU SCRIPT : creer_annonce.php
// REVISION : 2.1 - Intégration de style_create_annonce.css, nettoyage des styles en ligne et barre de navigation membre
// =============================================================================

// 1. VERROU DE SÉCURITÉ : Uniquement pour les membres connectés
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: connexion.php');
    exit();
}

require_once 'config.php';

$erreur = "";
$succes = "";

// 2. TRAITEMENT DE LA SOUMISSION DU FORMULAIRE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_utilisateur = $_SESSION['id_utilisateur'];

    try {
        // VÉRIFICATION DU QUOTA : Compter le nombre d'annonces actuelles du membre
        $stmt_quota = $bdd->prepare("SELECT COUNT(*) FROM jevend_annonces WHERE id_utilisateur = ?");
        $stmt_quota->execute([$id_utilisateur]);
        $nb_annonces_actuelles = $stmt_quota->fetchColumn();
    } catch (PDOException $e) {
        $nb_annonces_actuelles = 0;
    }

    // Récupération des données du formulaire sécurisées
    $id_categorie = isset($_POST['id_categorie']) ? intval($_POST['id_categorie']) : 0;
    $titre_objet_nettoye = isset($_POST['titre_objet_nettoye']) ? htmlspecialchars(trim($_POST['titre_objet_nettoye']), ENT_QUOTES, 'UTF-8') : '';
    $description_service = isset($_POST['description_service']) ? htmlspecialchars(trim($_POST['description_service']), ENT_QUOTES, 'UTF-8') : '';
    $prix_brut = isset($_POST['prix']) ? trim($_POST['prix']) : '';
    $duree_affichage = isset($_POST['duree_affichage']) ? intval($_POST['duree_affichage']) : 0;

    // Validation du Prix (Devient NULL si vide ou si c'est un service)
    $prix = (!empty($prix_brut) && is_numeric($prix_brut)) ? floatval($prix_brut) : null;

    // VALIDATION STRICTE DE L'ENSEMBLE
    if ($nb_annonces_actuelles >= 6) {
        $erreur = "Quota atteint : Vous avez atteint la limite maximale de 5 annonces autorisées par compte membre.";
    } elseif (empty($id_categorie) || empty($titre_objet_nettoye) || empty($description_service) || empty($duree_affichage)) {
        $erreur = "Veuillez remplir tous les champs obligatoires (Catégorie, Titre, Description et Durée).";
    } elseif (strlen($titre_objet_nettoye) > 60) {
        $erreur = "Le titre ne doit pas dépasser 60 caractères.";
    } elseif (!in_array($duree_affichage, [10, 20, 30])) {
        $erreur = "Durée d'affichage invalide (Maximum 30 jours).";
    } else {
        
        // Calcul du Timeout Automatique
        $date_courante = new DateTime();
        $date_courante->modify("+$duree_affichage days");
        $date_expiration = $date_courante->format('Y-m-d H:i:s');

        try {
            $bdd->beginTransaction();

            // A. Insertion de l'annonce principale
            $sql = "INSERT INTO jevend_annonces 
                    (id_utilisateur, id_categorie, titre_objet_nettoye, description_service, prix, image_courante, date_expiration, statut, nb_vues_visiteurs, nb_vues_membres, nb_clics_contact) 
                    VALUES (?, ?, ?, ?, ?, NULL, ?, 'actif', 0, 0, 0)";
            
            $stmt = $bdd->prepare($sql);
            $stmt->execute([$id_utilisateur, $id_categorie, $titre_objet_nettoye, $description_service, $prix, $date_expiration]);
            $id_nouvelle_annonce = $bdd->lastInsertId();

            // B. Traitement des images reçues encodées en Base64 via le compresseur JavaScript
            $images_enregistrees = 0;
            if (isset($_POST['images_base64']) && is_array($_POST['images_base64'])) {
                foreach ($_POST['images_base64'] as $index => $data_base64) {
                    if (!empty($data_base64)) {
                        $data_base64 = str_replace('data:image/jpeg;base64,', '', $data_base64);
                        $data_base64 = str_replace(' ', '+', $data_base64);
                        $donnees_decodees = base64_decode($data_base64);

                        if ($donnees_decodees !== false) {
                            $nom_fichier_jpg = uniqid('img_', true) . '.jpg';
                            $chemin_final = 'uploads/' . $nom_fichier_jpg;

                            if (file_put_contents($chemin_final, $donnees_decodees)) {
                                $est_principale = ($images_enregistrees === 0) ? 1 : 0;

                                $stmt_img = $bdd->prepare("INSERT INTO jevend_annonces_images (id_annonces, nom_fichier, est_principale) VALUES (?, ?, ?)");
                                $stmt_img->execute([$id_nouvelle_annonce, $nom_fichier_jpg, $est_principale]);

                                if ($est_principale === 1) {
                                    $stmt_update_master = $bdd->prepare("UPDATE jevend_annonces SET image_courante = ? WHERE id_annonces = ?");
                                    $stmt_update_master->execute([$nom_fichier_jpg, $id_nouvelle_annonce]);
                                }

                                $images_enregistrees++;
                            }
                        }
                    }
                }
            }

            $bdd->commit();
            $succes = "Votre annonce avec " . $images_enregistrees . " photo(s) a été publiée avec succès !";
            $titre_objet_nettoye = $description_service = $prix_brut = "";

        } catch (PDOException $e) {
            $bdd->rollBack();
            $erreur = "Erreur SQL : " . $e->getMessage();
        }
    }
}

// 3. RÉCUPÉRATION DU MENU DÉROULANT DES CATÉGORIES
$categories = [];
try {
    $stmt = $bdd->query("SELECT id_categorie, parent_id, nom_fr FROM jevend_categories ORDER BY parent_id ASC, nom_fr ASC");
    $liste_brute = $stmt->fetchAll();

    $parents = [];
    $enfants = [];
    foreach ($liste_brute as $cat) {
        if ($cat['parent_id'] === null) {
            $parents[$cat['id_categorie']] = $cat;
            $parents[$cat['id_categorie']]['sous_cat'] = [];
        } else {
            $enfants[] = $cat;
        }
    }
    foreach ($enfants as $enfant) {
        if (isset($parents[$enfant['parent_id']])) {
            $parents[$enfant['parent_id']]['sous_cat'][] = $enfant;
        }
    }
} catch (PDOException $e) {
    $erreur = "Erreur de chargement des catégories : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publier une annonce - jevend.com</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="style_create_annonce.css?v=1.0">
</head>
<body class="admin-body">

    <?php include 'partials/_nav_membre.php'; ?>

    <div class="form-bloc">
        <h2>📢 Créer une Annonce (5 photos max)</h2>

        <?php if (!empty($erreur)): ?>
            <div class="erreur-msg"><?php echo htmlspecialchars($erreur); ?></div>
        <?php endif; ?>

        <?php if (!empty($succes)): ?>
            <div class="succes-msg"><?php echo htmlspecialchars($succes); ?></div>
        <?php endif; ?>

        <form id="form-annonce" action="creer_annonce.php" method="POST">
            
            <div class="champ-groupe">
                <label for="id_categorie">Catégorie ou Type de Service</label>
                <select id="id_categorie" name="id_categorie" required>
                    <option value="">-- Sélectionnez une catégorie --</option>
                    <?php foreach ($parents as $parent): ?>
                        <option value="<?php echo $parent['id_categorie']; ?>" style="font-weight: bold; background-color: #e2e8f0;">
                            <?php echo htmlspecialchars($parent['nom_fr']); ?>
                        </option>
                        <?php foreach ($parent['sous_cat'] as $sous_cat): ?>
                            <option value="<?php echo $sous_cat['id_categorie']; ?>">
                                &nbsp;&nbsp;&nbsp;&nbsp;↳ <?php echo htmlspecialchars($sous_cat['nom_fr']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="champ-groupe">
                <label for="titre_objet_nettoye">Titre court de l'annonce (Max 60 caractères)</label>
                <input type="text" id="titre_objet_nettoye" name="titre_objet_nettoye" maxlength="60" value="<?php echo isset($titre_objet_nettoye) ? htmlspecialchars($titre_objet_nettoye) : ''; ?>" required placeholder="Ex: Tonte de pelouse, Divan en cuir...">
            </div>

            <div class="champ-groupe">
                <label for="description_service">Description complète du bien ou du service</label>
                <textarea id="description_service" name="description_service" rows="5" required placeholder="Donnez un maximum de détails ici..."><?php echo isset($description_service) ? htmlspecialchars($description_service) : ''; ?></textarea>
            </div>

            <div class="champ-groupe">
                <label for="prix">Prix recherché ($) <span style="font-size:0.8rem; color:#64748b;">(Optionnel pour un service)</span></label>
                <input type="number" id="prix" name="prix" step="0.01" min="0" value="<?php echo isset($prix_brut) ? htmlspecialchars($prix_brut) : ''; ?>" placeholder="Ex: 45.50 (laisser vide si sur demande)">
            </div>

            <div class="champ-groupe">
                <label style="font-weight: bold; color: #1e3a8a;">📷 Photos de votre vitrine (Maximum 5 photos)</label>
                <p style="font-size: 0.8rem; color:#64748b; margin-top: 2px; margin-bottom: 10px;">La première photo valide sera l'image principale de votre vitrine.</p>
                
                <div class="zone-photos-inputs">
                    <input type="file" class="input-image-comp" data-index="0" accept="image/jpeg, image/png, image/webp">
                    <input type="file" class="input-image-comp" data-index="1" accept="image/jpeg, image/png, image/webp">
                    <input type="file" class="input-image-comp" data-index="2" accept="image/jpeg, image/png, image/webp">
                    <input type="file" class="input-image-comp" data-index="3" accept="image/jpeg, image/png, image/webp">
                    <input type="file" class="input-image-comp" data-index="4" accept="image/jpeg, image/png, image/webp">
                </div>

                <div id="conteneur-base64"></div>
            </div>

            <div class="champ-groupe">
                <label>Durée de présence sur la vitrine (Timeout obligatoire)</label>
                <div class="radio-duree-group">
                    <label class="radio-option">
                        <input type="radio" name="duree_affichage" value="10" checked> 10 Jours
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="duree_affichage" value="20"> 20 Jours
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="duree_affichage" value="30"> 30 Jours (Max)
                    </label>
                </div>
            </div>

            <button type="submit" class="btn-action-submit">✨ Mettre en vitrine instantanément</button>
            
            <div class="liens-navigation">
                <a href="espace_membre.php">← Retour à mon tableau de bord</a>
            </div>
        </form>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const inputs = document.querySelectorAll(".input-image-comp");
        const conteneurBase64 = document.getElementById("conteneur-base64");

        inputs.forEach(input => {
            input.addEventListener("change", function(e) {
                const index = this.getAttribute("data-index");
                const fichier = e.target.files[0];

                const ancienInput = document.getElementById("hidden-img-" + index);
                if (ancienInput) ancienInput.remove();

                if (!fichier) return;

                const reader = new FileReader();
                reader.readAsDataURL(fichier);
                reader.onload = function(event) {
                    const img = new Image();
                    img.src = event.target.result;
                    img.onload = function() {
                        const max_largeur = 1200;
                        let largeur = img.width;
                        let hauteur = img.height;

                        if (largeur > max_largeur) {
                            const ratio = max_largeur / largeur;
                            largeur = max_largeur;
                            hauteur = Math.round(hauteur * ratio);
                        }

                        const canvas = document.createElement("canvas");
                        canvas.width = largeur;
                        canvas.height = hauteur;

                        const ctx = canvas.getContext("2d");
                        ctx.fillStyle = "#FFFFFF";
                        ctx.fillRect(0, 0, largeur, hauteur);
                        ctx.drawImage(img, 0, 0, largeur, hauteur);

                        const dataUrl = canvas.toDataURL("image/jpeg", 0.75);

                        const hiddenInput = document.createElement("input");
                        hiddenInput.type = "hidden";
                        hiddenInput.name = "images_base64[" + index + "]";
                        hiddenInput.id = "hidden-img-" + index;
                        hiddenInput.value = dataUrl;
                        conteneurBase64.appendChild(hiddenInput);
                    };
                };
            });
        });
    });
    </script>
</body>
</html>
