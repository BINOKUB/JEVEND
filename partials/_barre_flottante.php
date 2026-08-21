<?php
// =============================================================================
// NOM DU SCRIPT : partials/_barre_flottante.php...
// REVISION : 1.0 - Barre de navigation fixe en bas d'écran (Réseaux & Raccourcis)
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
        z-index: 9998; /* Juste en dessous du bandeau publicitaire si besoin */
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
    /* Adaptation mobile : on simplifie pour que ça reste lisible sur un petit écran */
    @media (max-width: 600px) {
        .barre-flottante-bas {
            padding: 6px 10px;
            font-size: 0.75rem;
        }
        .texte-desktop {
            display: none; /* Cache les textes longs sur cellulaire, garde les icônes/boutons */
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

    <!-- Côté droit : Réseaux sociaux & Action -->
    <div class="barre-flottante-droite">
        <span style="color: #64748b;" class="texte-desktop">Suivez-nous :</span>
        <a href="https://www.facebook.com/jevend.officiel/" target="_blank" title="Facebook" style="font-weight: bold; font-size: 1rem;">fb</a>
        <a href="https://youtube.com" target="_blank" title="YouTube" style="font-weight: bold; font-size: 0.9rem;">▶</a>
        <a href="connexion.php" class="btn-action-rapide">+ Poster</a>
    </div>
</div>
