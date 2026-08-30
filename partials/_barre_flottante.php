<?php
// =============================================================================
// NOM DU SCRIPT : partials/_barre_flottante.php
// REVISION     : 1.1 - Barre de navigation fixe (Intégration Chat à gauche de FB)
// =============================================================================
?>
<style>
    .barre-flottante-bas {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        background-color: #0f172a;
        color: #ffffff;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 20px;
        box-shadow: 0 -4px 10px rgba(0, 0, 0, 0.2);
        z-index: 9998;
        box-sizing: border-box;
        font-family: sans-serif;
        font-size: 0.85rem;
    }
    .barre-flottante-gauche, .barre-flottante-droite {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .barre-flottante-bas a {
        color: #94a3b8;
        text-decoration: none;
        transition: color 0.2s;
    }
    .barre-flottante-bas a:hover {
        color: #ffffff;
    }
    .btn-action-rapide {
        background-color: #2563eb;
        color: #fff !important;
        padding: 6px 12px;
        border-radius: 4px;
        font-weight: bold;
    }

    /* Style spécifique pour intégrer le déclencheur du chat dans la barre */
    .conteneur-chat-barre {
        display: flex;
        align-items: center;
        position: relative;
    }
    
    /* Adaptation de la bulle pour la barre de navigation */
    .conteneur-chat-barre #btn-chat-live-trigger {
        position: static !important;
        width: 32px !important;
        height: 32px !important;
        box-shadow: none !important;
        background: #2563eb !important;
        font-size: 1rem !important;
    }

    /* Repositionnement de la fenêtre surgissante par rapport à la barre */
    .conteneur-chat-barre #window-chat-live {
        bottom: 50px !important;
        right: 10px !important;
    }

    /* Adaptation mobile */
    @media (max-width: 600px) {
        .barre-flottante-bas {
            padding: 6px 10px;
            font-size: 0.75rem;
        }
        .texte-desktop {
            display: none;
        }
    }
</style>

<div class="barre-flottante-bas">
    <!-- Côté gauche : Liens rapides et légaux -->
    <div class="barre-flottante-gauche">
        <a href="index.php">🏠 <span class="texte-desktop">Accueil</span></a>
        <a href="faq.php">F.A.Q.</a>
        <a href="confidentialite.php" class="texte-desktop">Confidentialité</a>
    </div>

    <!-- Côté droit : Réseaux sociaux, Chat Live & Action -->
    <div class="barre-flottante-droite">
        <span style="color: #64748b;" class="texte-desktop">Suivez-nous :</span>
        
        <!-- Inclusion du Chat Live positionné à gauche de Facebook -->
        <div class="conteneur-chat-barre">
            <?php if (file_exists('chat_live.php')) include 'chat_live.php'; ?>
        </div>

        <a href="https://www.facebook.com/jevend.officiel/" target="_blank" title="Facebook" style="font-weight: bold; font-size: 1rem;">fb</a>
        <a href="https://youtube.com" target="_blank" title="YouTube" style="font-weight: bold; font-size: 0.9rem;">▶</a>
        <a href="connexion.php" class="btn-action-rapide">+ Poster</a>
    </div>
</div>
