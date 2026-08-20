<?php
// =============================================================================
// NOM DU SCRIPT : partials/_nav_publique.php
// REVISION : 1.12 - Intégration du menu Hamburger mobile
// =============================================================================

$page_active = basename($_SERVER['PHP_SELF']);
$url_courante = urlencode($_SERVER['REQUEST_URI'] ?? 'index.php');
$est_pro = (isset($_SESSION['type_compte']) && $_SESSION['type_compte'] === 'pro');
?>
<style>
    .barre-navigation-globale {
        background-color: #0f172a;
        border-bottom: 1px solid #1e293b;
        padding: 10px 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .nav-gauche-publique {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .logo-nav-container {
        display: flex;
        align-items: center;
        text-decoration: none;
    }
    .logo-nav-img {
        max-height: 45px;
        width: auto;
        display: block;
        border-radius: 4px;
    }
    .slogan-nav-pub {
        color: #94a3b8;
        font-size: 0.85rem;
        font-style: italic;
        font-weight: 500;
        white-space: nowrap;
    }
    .liens-session-zone {
        display: flex;
        gap: 12px;
        align-items: center;
    }
    .btn-nav-recherche {
        color: #94a3b8;
        text-decoration: none;
        font-size: 1.15rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px;
        border-radius: 4px;
        transition: all 0.15s ease;
    }
    .btn-nav-recherche:hover {
        color: #00f3ff;
        background-color: #1e293b;
        transform: scale(1.05);
    }
    .btn-nav-faq {
        color: #94a3b8;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 600;
        padding: 6px 10px;
        border-radius: 4px;
        transition: all 0.15s ease;
        white-space: nowrap;
    }
    .btn-nav-faq:hover {
        color: #ffffff;
        background-color: #1e293b;
    }
    .btn-nav-faq.active {
        color: #38bdf8;
        background-color: #1e293b;
    }
    .btn-nav-zone-cherche {
        color: #f59e0b;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 700;
        padding: 6px 10px;
        border-radius: 4px;
        border: 1px solid rgba(245, 158, 11, 0.4);
        transition: all 0.15s ease;
        white-space: nowrap;
    }
    .btn-nav-zone-cherche:hover {
        color: #ffffff;
        background-color: #f59e0b;
    }
    .btn-nav-zone-cherche.active {
        color: #ffffff;
        background-color: #d97706;
        border-color: #d97706;
    }
    .btn-nav-mes-recherches {
        color: #38bdf8;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 600;
        padding: 6px 10px;
        border-radius: 4px;
        transition: all 0.15s ease;
        white-space: nowrap;
    }
    .btn-nav-mes-recherches:hover {
        color: #ffffff;
        background-color: #1e293b;
    }
    .btn-nav-mes-recherches.active {
        color: #ffffff;
        background-color: #1e293b;
        border: 1px solid #38bdf8;
    }
    .btn-nav-membre {
        background-color: #2563eb;
        color: #ffffff;
        text-decoration: none;
        padding: 6px 14px;
        border-radius: 4px;
        font-size: 0.85rem;
        font-weight: bold;
        transition: background 0.15s ease;
        white-space: nowrap;
    }
    .btn-nav-membre:hover { background-color: #1d4ed8; }
    .btn-nav-deconnexion {
        color: #94a3b8;
        text-decoration: none;
        font-size: 0.85rem;
        white-space: nowrap;
    }
    .btn-nav-deconnexion:hover { color: #f43f5e; }

    /* BOUTON HAMBURGER (Masqué par défaut sur desktop) */
    .hamburger-btn-publique {
        display: none;
        background: none;
        border: none;
        color: #ffffff;
        font-size: 1.8rem;
        cursor: pointer;
        padding: 0;
    }
    .nav-top-mobile-row {
        display: contents;
    }

    @media (max-width: 992px) {
        .slogan-nav-pub { display: none; }
    }

    @media (max-width: 768px) {
        .barre-navigation-globale {
            flex-direction: column;
            align-items: stretch;
            padding: 12px 15px;
        }
        .nav-top-mobile-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        .logo-nav-img { max-height: 38px; }
        .hamburger-btn-publique {
            display: block;
        }
        /* Menu caché par défaut sur mobile, s'ouvre avec .ouvert */
        .liens-session-zone {
            display: none;
            flex-direction: column;
            gap: 10px;
            width: 100%;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #1e293b;
            box-sizing: border-box;
            text-align: center;
        }
        .liens-session-zone.ouvert {
            display: flex;
        }
        .liens-session-zone a, .liens-session-zone span {
            width: 100%;
            box-sizing: border-box;
            text-align: center;
        }
    }
</style>

<?php 
// Inclusion du module d'affichage du bandeau sponsorisé tout en haut du site
include '_affiche_sponsorise.php'; 
?>

<nav class="barre-navigation-globale">
    <div class="nav-top-mobile-row">
        <div class="nav-gauche-publique">
            <a href="index.php" class="logo-nav-container">
                <img src="assets/LOGO_JEVEND-COM.jpeg" alt="jevend.com" class="logo-nav-img">
            </a>
            <span class="slogan-nav-pub">« Premier arrivé, premier vendu »</span>
        </div>
        <button class="hamburger-btn-publique" onclick="toggleMenuPublique()" aria-label="Menu">☰</button>
    </div>

    <div class="liens-session-zone" id="menu-liens-publique">
        <?php if ($page_active !== 'search.php'): ?>
            <a href="search.php" class="btn-nav-recherche" title="Rechercher une annonce">🔍</a>
        <?php endif; ?>

        <a href="faq.php" class="btn-nav-faq <?= ($page_active === 'faq.php') ? 'active' : '' ?>" title="Foire Aux Questions">F.A.Q.</a>

        <?php if (!$est_pro): ?>
            <a href="zone_cherche.php" class="btn-nav-zone-cherche <?= ($page_active === 'zone_cherche.php') ? 'active' : '' ?>" title="La Zone Je Cherche">🎯 Je Cherche</a>
        <?php endif; ?>

        <?php if (isset($_SESSION['id_utilisateur'])): ?>
            <span style="color: #94a3b8; font-size: 0.85rem; display: inline-block; white-space: nowrap;">
                👤 <a href="." style="color: #ffffff; text-decoration: none; border-bottom: 1px dotted #ffffff;" title="Modifier mes informations"><strong><?= htmlspecialchars($_SESSION['nom_entreprise'] ?? $_SESSION['nom'] ?? 'Membre') ?></strong></a>
            </span>

            <?php if (!$est_pro): ?>
                <a href="mes_recherches.php" class="btn-nav-mes-recherches <?= ($page_active === 'mes_recherches.php') ? 'active' : '' ?>" title="Mes recherches Je Cherche">📋 Mes recherches</a>
            <?php endif; ?>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="panneau.php" class="btn-nav-membre" style="background-color: #0f172a; border: 1px solid #38bdf8; color: #38bdf8;">⚙️ Panneau Admin</a>
            <?php elseif ($est_pro): ?>
                <a href="espace_membre_pro.php" class="btn-nav-membre" style="background-color: #2563eb;">🏢 Espace Marchand</a>
            <?php else: ?>
                <a href="espace_membre.php" class="btn-nav-membre">📋 Mon Espace</a>
            <?php endif; ?>

            <a href="deconnexion.php" class="btn-nav-deconnexion">Déconnexion</a>
        <?php else: ?>
            <?php if ($page_active !== 'connexion.php'): ?>
                <a href="connexion.php?redirect=<?= $url_courante ?>" class="btn-nav-membre" style="background-color: #1e293b; border: 1px solid #475569;">Se connecter</a>
            <?php endif; ?>

            <?php if ($page_active !== 'inscription.php' && $page_active !== 'connexion.php'): ?>
                <a href="inscription.php" class="btn-nav-membre">S'inscrire</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</nav>

<script>
function toggleMenuPublique() {
    const menu = document.getElementById('menu-liens-publique');
    menu.classList.toggle('ouvert');
}
</script>
