<?php
// =============================================================================
// NOM DU SCRIPT : creer_annonce_traitement.php
// DESCRIPTION  : Traitement de la soumission du formulaire de création d'annonce
// =============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$quota_global_atteint && !$quota_utilisateur_atteint) {
    
    // Double vérification au moment du POST
    $stmt_quota_post = $bdd->prepare("SELECT COUNT(*) FROM jevend_annonces WHERE id_utilisateur = ?");
    $stmt_quota_post->execute([$id_utilisateur]);
    $nb_actuel_post = (int)$stmt_quota_post->fetchColumn();

    $id_categorie = isset($_POST['id_categorie']) ? intval($_POST['id_categorie']) : 0;
    $titre_objet_nettoye = isset($_POST['titre_objet_nettoye']) ? htmlspecialchars(trim($_POST['titre_objet_nettoye']), ENT_QUOTES, 'UTF-8') : '';
    $description_service = isset($_POST['description_service']) ? htmlspecialchars(trim($_POST['description_service']), ENT_QUOTES, 'UTF-8') : '';
    $prix_brut = isset($_POST['prix']) ? trim($_POST['prix']) : '';
    $duree_affichage = isset($_POST['duree_affichage']) ? intval($_POST['duree_affichage']) : 0;
    $prix = (!empty($prix_brut) && is_numeric($prix_brut)) ? floatval($prix_brut) : null;

    if ($nb_actuel_post >= $limite_personnelle) {
        $erreur = "Quota atteint : Vous avez atteint la limite maximale de 6 annonces autorisées par compte membre.";
    } elseif (empty($id_categorie) || empty($titre_objet_nettoye) || empty($description_service) || empty($duree_affichage)) {
        $erreur = "Veuillez remplir tous les champs obligatoires (Catégorie, Titre, Description et Durée).";
    } elseif (strlen($titre_objet_nettoye) > 60) {
        $erreur = "Le titre ne doit pas dépasser 60 caractères.";
    } elseif (!in_array($duree_affichage, [10, 20, 30])) {
        $erreur = "Durée d'affichage invalide (Maximum 30 jours).";
    } else {
        $date_courante = new DateTime();
        $date_courante->modify("+$duree_affichage days");
        $date_expiration = $date_courante->format('Y-m-d H:i:s');

        try {
            $bdd->beginTransaction();

            $sql = "INSERT INTO jevend_annonces 
                    (id_utilisateur, id_categorie, titre_objet_nettoye, description_service, prix, image_courante, date_expiration, statut, nb_vues_visiteurs, nb_vues_membres, nb_clics_contact) 
                    VALUES (?, ?, ?, ?, ?, NULL, ?, 'actif', 0, 0, 0)";
            
            $stmt = $bdd->prepare($sql);
            $stmt->execute([$id_utilisateur, $id_categorie, $titre_objet_nettoye, $description_service, $prix, $date_expiration]);
            $id_nouvelle_annonce = $bdd->lastInsertId();

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
            
            // Mettre à jour le compteur local après insertion
            $nb_annonces_actuelles++;
            if ($nb_annonces_actuelles >= $limite_personnelle) {
                $quota_utilisateur_atteint = true;
            }

        } catch (PDOException $e) {
            $bdd->rollBack();
            $erreur = "Erreur SQL : " . $e->getMessage();
        }
    }
}
