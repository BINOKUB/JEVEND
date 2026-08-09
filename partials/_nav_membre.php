<?php
// =============================================================================
// NOM DU SCRIPT : partials/_nav_membre.php
// REVISION : 1.6 - Ajout du slogan "« Premier arrivé, premier vendu »" à gauche
// =============================================================================
?>
<style>
    /* BARRE DE NAVIGATION SUPÉRIEURE MEMBRE HARMONISÉE */
    .nav-membre-responsive {
        margin-bottom: 30px; 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        padding: 10px 25px; 
        background-color: #0f172a; 
        border-bottom: 1px solid #1e293b; 
    }
    .nav-membre-gauche {
        display: flex; 
        align-items: center; 
        gap: 15px;
    }
    .logo-membre-container {
        display: flex;
        align-items: center;
        text-decoration: none;
    }
    .logo-membre-img {
        max-height: 45px; 
        width: auto;
        display: block;
        border-radius: 4px;
    }
    .slogan-nav {
        color: #94a3b8;
        font-size: 0.85rem;
        font-style: italic;
        font-weight: 500;
        white-space: nowrap;
        margin-left: 5px;
    }
    .nav-membre-droite {
        display: flex; 
        align-items: center; 
        margin: 0;
        color: #94a3b8; 
        font-size: 0.9rem;
        white-space: nowrap;
    }
    .btn-nav-publier {
        margin: 0; 
        padding: 6px 14px; 
        font-size: 0.85rem; 
        text-decoration: none; 
        display: inline-block; 
        width: auto; 
        background-color: #2563eb; 
        color: #ffffff; 
        border-radius: 4px; 
        font-weight: bold;
        transition: background 0.15s ease;
    }
    .btn-nav-publier:hover { background-color: #1d4ed8; }

    @media (max-width: 992px) {
        .slogan-nav {
            display: none; /* Masqué sur tablettes/mobiles pour éviter l'encombrement */
        }
    }

    @media (max-width: 768px) {
        .nav-membre-responsive {
            flex-direction: column;
            gap: 15px;
            text-align: center;
            padding: 15px;
        }
        .nav-membre-gauche {
            flex-direction: column;
            gap: 12px;
            width: 100%;
        }
        .logo-membre-img { max-height: 38px; }
        .nav-membre-gauche a.btn-nav-publier, 
        .nav-membre-gauche span.badge-quota-nav {
            width: 100% !important;
            box-sizing: border-box;
            text-align: center;
            padding: 8px 14px;
        }
        .nav-membre-droite {
            width: 100%;
            justify-content: center;
            border-top: 1px dashed #1e293b;
            padding-top: 12px;
        }
    }
</style>

<div class="nav-membre-responsive">
    <div class="nav-membre-gauche">
        <a href="index.php" class="logo-membre-container">
            <img src="assets/LOGO_JEVEND-COM.jpeg" alt="jevend.com" class="logo-membre-img">
        </a>
        <span class="slogan-nav">« Premier arrivé, premier vendu »</span>

        <span style="font-size: 0.8rem; background-color: #1e293b; color: #3b82f6; padding: 4px 10px; border-radius: 4px; font-weight: bold; border: 1px solid #2563eb; white-space: nowrap;">
            👤 Espace Client
        </span>

        <!-- CONDITION D'AFFICHAGE DU BOUTON SELON LE QUOTA GLOBAL -->
        <?php if (isset($quota_annonces_atteint) && $quota_annonces_atteint): ?>
            <span class="badge-quota-nav" style="background-color: #7f1d1d; color: #fecaca; padding: 6px 14px; border-radius: 4px; font-weight: bold; font-size: 0.85rem; border: 1px solid #f87171; white-space: nowrap; display: inline-block;">
                🔒 Publication suspendue
            </span>
        <?php else: ?>
            <a href="creer_annonce.php" class="btn-nav-publier">
                + Publier une annonce
            </a>
        <?php endif; ?>
    </div>
    
    <div class="nav-membre-droite">
        <span style="color: #94a3b8;">Bonjour, <a href="edit_membre.php" style="color: #ffffff; text-decoration: none; border-bottom: 1px dotted #ffffff;" title="Modifier mes informations"><strong><?php echo htmlspecialchars($_SESSION['nom']); ?></strong></a></span>
        <a href="deconnexion.php" style="margin-left: 20px; color: #94a3b8; font-weight: bold; text-decoration: none; font-size: 0.85rem; transition: color 0.15s;" onmouseover="this.style.color='#f43f5e'" onmouseout="this.style.color='#94a3b8'">Déconnexion</a>
    </div>
</div>
