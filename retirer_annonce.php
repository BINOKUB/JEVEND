<?php
session_start();

// NOM DU SCRIPT : retirer_annonce.php
// REVISION : 1.1 - Nettoyage complet de la galerie d'images sur le disque et en BDD
// SCRIPT COMPLET ET SUIVI

if (!isset($_SESSION['id_utilisateur']) || !isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: espace_membre.php');
    exit();
}

require_once 'config.php';

$id_annonce = intval($_GET['id']);
$id_utilisateur = $_SESSION['id_utilisateur'];

try {
    // -------------------------------------------------------------
    // 1. VÉRIFICATION DE LA PROPRIÉTÉ DE L'ANNONCE
    // -------------------------------------------------------------
    $stmt_check = $bdd->prepare("SELECT image_courante FROM jevend_annonces WHERE id_annonces = ? AND id_utilisateur = ?");
    $stmt_check->execute([$id_annonce, $id_utilisateur]);
    $image_principale = $stmt_check->fetchColumn();

    // Si l'annonce existe et appartient bien à l'utilisateur
    if ($image_principale !== false) {

        // -------------------------------------------------------------
        // 2. RÉCUPÉRATION DE TOUTES LES IMAGES DE LA GALERIE
        // -------------------------------------------------------------
        $stmt_galerie = $bdd->prepare("SELECT nom_fichier FROM jevend_annonces_images WHERE id_annonces = ?");
        $stmt_galerie->execute([$id_annonce]);
        $fichiers_galerie = $stmt_galerie->fetchAll(PDO::FETCH_COLUMN);

        // Tableau regroupant TOUS les fichiers à effacer du disque
        $tous_les_fichiers = [];

        if (!empty($image_principale)) {
            $tous_les_fichiers[] = $image_principale;
        }

        if (!empty($fichiers_galerie)) {
            $tous_les_fichiers = array_merge($tous_les_fichiers, $fichiers_galerie);
        }

        // Supprimer les doublons éventuels
        $tous_les_fichiers = array_unique($tous_les_fichiers);

        // -------------------------------------------------------------
        // 3. SUPPRESSION PHYSIQUE DES FICHIERS EN DOSSIER UPLOADS/
        // -------------------------------------------------------------
        foreach ($tous_les_fichiers as $fichier) {
            $chemin_complet = __DIR__ . '/uploads/' . $fichier;
            if (!empty($fichier) && file_exists($chemin_complet) && is_file($chemin_complet)) {
                @unlink($chemin_complet);
            }
        }

        // -------------------------------------------------------------
        // 4. SUPPRESSION SQL (GALERIE + ANNONCE)
        // -------------------------------------------------------------
        // Supprimer d'abord la galerie d'images
        $stmt_del_galerie = $bdd->prepare("DELETE FROM jevend_annonces_images WHERE id_annonces = ?");
        $stmt_del_galerie->execute([$id_annonce]);

        // Supprimer enfin l'annonce principale
        $stmt_del_annonce = $bdd->prepare("DELETE FROM jevend_annonces WHERE id_annonces = ? AND id_utilisateur = ?");
        $stmt_del_annonce->execute([$id_annonce, $id_utilisateur]);
    }
} catch (PDOException $e) {
    // En cas d'erreur SQL, on intercepte sans faire planter la redirection
}

header('Location: espace_membre.php');
exit();
