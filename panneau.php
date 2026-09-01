<?php
/*
====================================================
Fichier       : panneau.php
Révision      : v2.6 - Fix Redirection Infinie & Session
Description   : Panneau de configuration - JeVend.com
====================================================
*/

session_start();

$courriel_admin_supreme = 'douimet61@gmail.com';

// VERIFICATION RIGIDE MAIS SANS BOUCLE INFINIE
$is_admin = (
    isset($_SESSION['id_utilisateur']) && 
    isset($_SESSION['role']) && 
    isset($_SESSION['courriel']) && 
    $_SESSION['role'] === 'admin' && 
    strtolower(trim($_SESSION['courriel'])) === strtolower($courriel_admin_supreme)
);

if (!$is_admin) {
    // Si la session est invalide ou corrompue, on nettoie pour éviter la boucle
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    
    header('Location: connexion.php?erreur=session_expiree');
    exit();
}

require_once 'config.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Administration — jevend.com</title>
    <link rel="stylesheet" href="style_panneau.css">
    
    <!-- SCRIPT PLACÉ DANS LE HEAD POUR GARANTIR SON CHARGEMENT IMMÉDIAT -->
    <script>
    function changerOnglet(idSection) {
        // 1. Masquer toutes les sections
        const sections = document.querySelectorAll('.section-panneau');
        sections.forEach(sec => {
            sec.classList.remove('active');
            sec.style.display = 'none';
        });

        // 2. Désactiver tous les boutons
        const boutons = document.querySelectorAll('.onglet-btn');
        boutons.forEach(btn => btn.classList.remove('actif'));

        // 3. Activer et afficher la section demandée
        const sectionCible = document.getElementById(idSection);
        if (sectionCible) {
            sectionCible.classList.add('active');
            sectionCible.style.display = 'block';
        }

        // 4. Mettre en valeur le bouton correspondant
        const boutonActif = Array.from(boutons).find(btn => {
            const attr = btn.getAttribute('onclick');
            return attr && attr.includes(idSection);
        });
        
        if (boutonActif) {
            boutonActif.classList.add('actif');
        }

        // 5. Mettre à jour l'ancre URL sans rechargement
        if (history.pushState) {
            history.pushState(null, null, '#' + idSection);
        } else {
            location.hash = '#' + idSection;
        }
    }

    // Réouverture automatique de l'onglet actif au chargement
    document.addEventListener('DOMContentLoaded', function() {
        const hash = window.location.hash.replace('#', '');
        if (hash && document.getElementById(hash)) {
            changerOnglet(hash);
        }
    });
    </script>
</head>
<body class="admin-body">

    <!-- BANDEAU DE TÊTE FONCÉ -->
    <div class="admin-barre-haut">
        <h1>Panneau de Configuration Secrète</h1>
        <div class="statut-admin">
            Connecté en tant que : <strong><?php echo htmlspecialchars($_SESSION['nom']); ?></strong>
            <a href="deconnexion.php" style="margin-left: 15px; color: #f87171; font-weight: bold; text-decoration: none;">Déconnexion</a>
        </div>
    </div>

    <div class="admin-conteneur" style="max-width: 1200px; margin: 25px auto; padding: 0 15px; box-sizing: border-box;">
        
        <!-- INCLUSION DE LA BARRE DE NAVIGATION MODULAIRE DES ONGLETS -->
        <?php 
        if (file_exists('admin_modules/_admin_nav.php')) {
            include 'admin_modules/_admin_nav.php';
        } elseif (file_exists('_admin_nav.php')) {
            include '_admin_nav.php';
        }
        ?>

        <!-- MODULE 1 : TRAFFIC -->
        <div id="onglet-traffic" class="section-panneau active">
            <?php if (file_exists('admin_modules/_admin_traffic.php')) include 'admin_modules/_admin_traffic.php'; ?>
        </div>

        <!-- MODULE 2 : MEMBRES -->
        <div id="onglet-membres" class="section-panneau">
            <?php if (file_exists('admin_modules/_admin_membres.php')) include 'admin_modules/_admin_membres.php'; ?>
        </div>

        <!-- MODULE 3 : CATÉGORIES -->
        <div id="onglet-categories" class="section-panneau">
            <?php if (file_exists('admin_modules/_admin_categories.php')) include 'admin_modules/_admin_categories.php'; ?>
        </div>

        <!-- MODULE 4 : TARIFS -->
        <div id="onglet-tarifs" class="section-panneau">
            <?php if (file_exists('admin_modules/_admin_tarifs.php')) include 'admin_modules/_admin_tarifs.php'; ?>
        </div>

        <!-- MODULE 5 : COMPTABILITÉ -->
        <div id="onglet-compta" class="section-panneau">
            <?php if (file_exists('admin_modules/_admin_compta.php')) include 'admin_modules/_admin_compta.php'; ?>
        </div>

        <!-- MODULE 6 : INFO DIRECTION -->
        <div id="onglet-admin-ban" class="section-panneau">
            <?php if (file_exists('admin_modules/_admin_ban.php')) include 'admin_modules/_admin_ban.php'; ?>
        </div>

        <!-- MODULE 7 : F.A.Q. ADMIN -->
        <div id="onglet-faq" class="section-panneau">
            <?php if (file_exists('admin_modules/_admin_faq.php')) include 'admin_modules/_admin_faq.php'; ?>
        </div>

        <!-- MODULE 8 : RPM (Régulation, Publicités & Métriques) -->
        <div id="onglet-rpm" class="section-panneau">
            <?php if (file_exists('admin_modules/_admin_rpm.php')) include 'admin_modules/_admin_rpm.php'; ?>
        </div>

        <!-- MODULE 9 : FRAUDE & SIGNALEMENTS -->
        <div id="onglet-fraude" class="section-panneau">
            <?php if (file_exists('admin_modules/_fraude_verif.php')) include 'admin_modules/_fraude_verif.php'; ?>
        </div>

        <!-- MODULE 10 : FRAUDE & CHAT (Scanner silencieux) -->
        <div id="onglet-fraude-chat" class="section-panneau">
            <?php if (file_exists('admin_modules/_fraude_chat.php')) include 'admin_modules/_fraude_chat.php'; ?>
        </div>

        <!-- MODULE 11 : VITRINE DU VILLAGE -->
        <div id="onglet-vitrine" class="section-panneau">
            <?php if (file_exists('admin_modules/_vitrine_admin.php')) include 'admin_modules/_vitrine_admin.php'; ?>
        </div>

        <!-- MODULE CHAT LIVE -->
        <div id="onglet-chat-live" class="section-panneau">
            <?php if (file_exists('admin_modules/_chat_live.php')) include 'admin_modules/_chat_live.php'; ?>
        </div>

        <!-- MODULE 12 : FORMAT DES COURRIELS -->
        <div id="onglet-format-email" class="section-panneau">
            <?php 
            if (file_exists('admin_modules/_admin_format_email.php')) {
                include 'admin_modules/_admin_format_email.php';
            } elseif (file_exists('admin_modules/_admin_format_email.php')) {
                include 'admin_modules/_admin_format_email.php';
            }
            ?>
        </div>  


<!-- MODULE 13 : SANTE DU STOCKAGE ET IMAGES -->
<div id="onglet-stock-images" class="section-panneau">
    <?php 
    if (file_exists('admin_modules/_stock_images_sanitaire.php')) {
        include 'admin_modules/_stock_images_sanitaire.php';
    } elseif (file_exists('admin_modules/_stock_images_sanitaire.php')) {
        include 'admin_modules/_stock_images_sanitaire.php';
    }
    ?>
</div>


<!-- MODULE 14 : CALENDRIER DES STATISTIQUES -->
<div id="onglet-calendrier-stats" class="section-panneau">
    <?php 
    try {
        if (file_exists('admin_modules/_calendrier_stats.php')) {
            include 'admin_modules/_calendrier_stats.php';
        } elseif (file_exists('admin_modules/_calendrier_stats.php')) {
            include 'admin_modules/_calendrier_stats.php';
        }
    } catch (Throwable $e) {
      //  echo '<div style="background:#fee2e2; color:#b91c1c; padding:15px; border-radius:6px;">⚠️ Erreur Module Calendrier : ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    ?>
</div>





    </div>

</body>
</html>
