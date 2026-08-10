<?php
// =============================================================================
// NOM DU SCRIPT : partials/_nav_membre.php
// REVISION : 1.7 - Intégration du menu Hamburger mobile pour l'espace membre
// =============================================================================
?>
<style>
    .nav-membre-responsive {
        margin-bottom: 30px; 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        padding: 10px 25px; 
        background-color: #0f172a; 
        border-bottom: 1px solid #1e293b; 
    }
    .nav-membre-conteneur-interne {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
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

    /* BOUTON HAMBURGER MEMBRE */
    .hamburger-btn-membre {
        display: none;
        background: none;
        border: none;
        color: #ffffff;
        font-size: 1.8rem;
        cursor: pointer;
        padding: 0;
    }
    .nav-membre-liens-mobile {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
    }

    @media (max-width: 992px) {
        .slogan-nav {
            display: none;
        }
    }

    @media (max-width: 768px) {
        .nav-membre-responsive {
            flex-direction: column;
            align-items: stretch;
            padding: 12px 15px;
        }
        .hamburger-btn-membre {
            display: block;
        }
        .nav-membre-conteneur-interne {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        .logo-membre-img { max-height: 38px; }

        /* Menu masqué par défaut sur mobile, affiché via .ouvert */
        .nav-membre-liens-mobile {
            display: none;
            flex-direction: column;
            gap: 12px;
            width: 100%;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #1e293b;
            text-align: center;
        }
        .nav-membre-liens-mobile.ouvert {
            display: flex;
        }
        .nav-membre-gauche, .nav-membre-droite {
            flex-direction: column;
            width: 100%;
            gap: 10px;
            align-items: center;
        }
        .nav-membre-gauche a.btn-nav-publier, 
        .nav-membre-gauche span.badge-quota-nav {
            width: 100% !important;
            box-sizing: border-box;
            text-align: center;
            padding: 8px 14px;
        }
        .nav-membre-droite {
            border-top: 1px dashed #1e293b;
            padding-top: 12px;
            justify-content: center;
        }
    }
</style>

<div class="nav-membre-responsive">
    <div class="nav-membre-conteneur-interne">
        <a href="index.php" class="logo-membre-container">
            <img src="assets/LOGO_JEVEND-COM.jpeg" alt="jevend.com" class="logo-membre-img">
        </a>
        <button class="hamburger-btn-membre" onclick="toggleMenuMembre()" aria-label="Menu">☰</button>
    </div>
    
    <div class="nav-membre-liens-mobile" id="menu-liens-membre">
        <div class="nav-membre-gauche">
            <span class="slogan-nav">« Premier arrivé, premier vendu »</span>

            <span style="font-size: 0.8rem; background-color: #1e293b; color: #3b82f6; padding: 4px 10px; border-radius: 4px; font-weight: bold; border: 1px solid #2563eb; white-space: nowrap;">
                👤 Espace Client
            </span>

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
            <span style="color: #94a3b8;">Bonjour, <a href="edit_membre.php" style="color: #ffffff; text-decoration: none; border-bottom: 1px dotted #ffffff;" title="Modifier mes informations"><strong><?= htmlspecialchars($_SESSION['nom'] ?? '') ?></strong></a></span>
            <a href="deconnexion.php" style="margin-left: 20px; color: #94a3b8; font-weight: bold; text-decoration: none; font-size: 0.85rem; transition: color 0.15s;" onmouseover="this.style.color='#f43f5e'" onmouseout="this.style.color='#94a3b8'">Déconnexion</a>
        </div>
    </div>
</div>

<script>
function toggleMenuMembre() {
    const menu = document.getElementById('menu-liens-membre');
    menu.classList.toggle('ouvert');
}
</script>
