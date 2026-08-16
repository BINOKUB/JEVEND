<?php
// =============================================================================
// SCRIPT TEMPORAIRE : Générateur d'injection massive (1000 annonces)
// NOM DU SCRIPT : injecteur_annonces.php
// =============================================================================
require_once 'config.php';

// 1. Vos 14 utilisateurs réels (IDs)
$utilisateurs = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14];

// 2. Vos catégories réelles (IDs)
$categories = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 24, 25, 27, 28, 29, 30];

// 3. Votre liste d'images réelles issues du dossier uploads/
$images_disponibles = [
    'img_6a4c0c4f5f4d70.44525622.jpg', 'img_6a4c3cae5781b8.68488210.jpg', 'img_6a4c10a87389d1.88046370.jpg',
    'img_6a4cf816ccbfc0.59118451.jpg', 'img_6a4cf816ccd600.69167434.jpg', 'img_6a4cf816cce8b7.73613185.jpg',
    'img_6a4cf816cd6880.07603582.jpg', 'img_6a4fb47e926d89.96971997.jpg', 'img_6a4fb47e9255f5.94478289.jpg',
    'img_6a4fb47e9276c9.53067398.jpg', 'img_6a4fc4639a7f84.99298727.jpg', 'img_6a4fcf89ea89e6.39261841.jpg',
    'img_6a4fd5ce13bad9.43382564.jpg', 'img_6a4fd5ce13d103.11994154.jpg', 'img_6a4fd5ce138e86.87069892.jpg',
    'img_6a4fd5ce1458d8.53493561.jpg', 'img_6a4fd5ce144508.24263299.jpg', 'img_6a5cf021aad131.18479591.jpg',
    'img_6a5d4cc582aa94.55672849.jpg', 'img_6a5d4eb47a54f6.32396218.jpg', 'img_6a5d4eb47a6703.47650015.jpg',
    'img_6a5d4eb4797737.92246737.jpg', 'img_6a5d44c5e4f605.18990843.jpg', 'img_6a5d50bed96bf7.03996941.jpg',
    'img_6a50fc00e4f0c4.98073715.jpg', 'img_6a50fc00e5ac37.64002934.jpg', 'img_6a50fc00e5e873.82839421.jpg',
    'img_6a50fc00e549f7.18629650.jpg', 'img_6a50fc00e62857.65924492.jpg', 'img_6a5032da3a94a4.94541254.jpg',
    'img_6a5032da39a388.31823797.jpg', 'img_6a5032da39b081.58820851.jpg', 'img_6a5032da3917b9.57243619.jpg',
    'img_6a5032da399500.45927372.jpg', 'img_6a56350e45a4d7.60116310.jpg', 'img_6a56350e45fcd5.87856535.jpg',
    'img_6a56350e461708.39509614.jpg', 'OBJECTF.png'
];

// Dictionnaires de mots pour fabriquer des titres de petites annonces crédibles
$objets = [
    "Vélo de montagne Trek", "Batterie électronique Roland", "Scie circulaire DeWalt", 
    "Guitare acoustique Fender", "Sofa en cuir 3 places", "Tondeuse à gazon Honda", 
    "Perceuse sans fil Milwaukee", "Téléviseur 4K Smart TV 55-po", "Amplificateur de puissance JBL", 
    "Casque d'écoute professionnel", "Clavier maître MIDI 61 touches", "Table à manger en chêne", 
    "Réfrigérateur en inox", "Laveuse sécheuse Frontale", "Remorque artisanale 5x8", 
    "Souffleuse à neige Ariens", "Paires de raquettes de neige", "Ensemble d'outils à main", 
    "Plaque de cuisson induction", "Caméra reflex Nikon"
];

$qualificatifs = [
    "presque neuf", "en excellent état", "édition limitée", "très peu utilisé", 
    "impeccable", "modèle professionnel", "pour bricoleur", "garantie restante", 
    "fonctionne A1", "très propre"
];

echo "<div style='font-family: sans-serif; max-width: 600px; margin: 40px auto; padding: 20px; border: 2px solid #2563eb; border-radius: 8px;'>";
echo "<h2>🚀 Générateur de données de test JEVEND</h2>";

try {
    $bdd->beginTransaction();

    $stmt = $bdd->prepare("
        INSERT INTO jevend_annonces (
            id_utilisateur, id_categorie, titre_objet_nettoye, description_service, 
            prix, image_courante, date_creation, date_expiration, statut, statut_vente
        ) VALUES (
            :id_user, :id_cat, :titre, :description, 
            :prix, :image, :date_crea, :date_exp, 'actif', 'disponible'
        )
    ");

    for ($i = 1; $i <= 1000; $i++) {
        // Sélection aléatoire
        $id_user = $utilisateurs[array_rand($utilisateurs)];
        $id_cat = $categories[array_rand($categories)];
        $image = $images_disponibles[array_rand($images_disponibles)];
        
        $titre_base = $objets[array_rand($objets)] . " " . $qualificatifs[array_rand($qualificatifs)];
        // On tronque si jamais le titre dépasse 60 caractères (limite VARCHAR de votre table)
        $titre = mb_substr($titre_base, 0, 58, 'UTF-8');
        
        $description = "Excellente opportunité à saisir rapidement à Matane. Objet bien entretenu. Contactez-moi directement via mon espace ou par téléphone pour venir l'essayer.";
        $prix = rand(15, 2500) + (rand(0, 1) == 1 ? 0.99 : 0.00);

        // Dates étalées sur les 60 derniers jours
        $jours_passes = rand(0, 60);
        $date_crea = date('Y-m-d H:i:s', strtotime("-$jours_passes days"));
        $date_exp = date('Y-m-d H:i:s', strtotime("+$jours_passes days"));

        $stmt->execute([
            ':id_user'     => $id_user,
            ':id_cat'      => $id_cat,
            ':titre'       => $titre,
            ':description' => $description,
            ':prix'        => $prix,
            ':image'       => $image,
            ':date_crea'   => $date_crea,
            ':date_exp'    => $date_exp
        ]);
    }

    $bdd->commit();
    echo "<p style='color: #16a34a; font-weight: bold;'>✅ 1 000 annonces ont été injectées avec succès dans la base de données !</p>";
    echo "<p>Vous pouvez maintenant supprimer ou désactiver ce fichier <code>injecteur_annonces.php</code>.</p>";

} catch (Exception $e) {
    $bdd->rollBack();
    echo "<p style='color: #dc2626; font-weight: bold;'>❌ Erreur d'injection : " . $e->getMessage() . "</p>";
}

echo "</div>";
