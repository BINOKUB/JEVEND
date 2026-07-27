<?php
/*
====================================================
Fichier       : admin_modules/_admin_nav.php
Révision      : v1.0
Description   : Barre de navigation modulaire pour le panneau d'administration
Nouveautés    : 
  - Disposition dynamique en grille (Grid CSS) à 4 onglets maximum par ligne
  - Espacement régulier et net entre chaque rangée
  - Ajout du 7e onglet "❓ F.A.Q. Admin"
  - Responsive : passe automatiquement à 2 colonnes sur tablette et 1 colonne sur mobile
====================================================
*/
?>
<style>
    /* GRILLE D'ONGLETS ADMIN : 4 PAR LIGNE MAX */
    .admin-navigation-onglets {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 25px;
    }

    /* Style individuel des boutons d'onglets */
    .admin-navigation-onglets .onglet-btn {
        width: 100%;
        padding: 12px 15px;
        background-color: #ffffff;
        color: #334155;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.2s ease;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        box-sizing: border-box;
    }

    .admin-navigation-onglets .onglet-btn:hover {
        background-color: #f1f5f9;
        border-color: #2563eb;
        color: #2563eb;
    }

    .admin-navigation-onglets .onglet-btn.actif {
        background-color: #2563eb;
        color: #ffffff;
        border-color: #2563eb;
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
    }

    /* RESPONSIVE MOBILE & TABLETTE */
    @media (max-width: 992px) {
        .admin-navigation-onglets {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 576px) {
        .admin-navigation-onglets {
            grid-template-columns: 1fr;
        }
    }
</style>

<!-- RANGÉES D'ONGLETS (MAXIMUM 4 PAR LIGNE) -->
<div class="admin-navigation-onglets">
    <!-- Ligne 1 -->
    <button class="onglet-btn actif" onclick="changerOnglet('onglet-traffic')">📊 Traffic & Connexions</button>
    <button class="onglet-btn" onclick="changerOnglet('onglet-membres')">👥 Gestion des Membres</button>
    <button class="onglet-btn" onclick="changerOnglet('onglet-categories')">📁 Gestion des Catégories</button>
    <button class="onglet-btn" onclick="changerOnglet('onglet-tarifs')">🏷️ Tarifs Publicités</button>

    <!-- Ligne 2 -->
    <button class="onglet-btn" onclick="changerOnglet('onglet-compta')">💰 Statistiques Comptables</button>
    <button class="onglet-btn" onclick="changerOnglet('onglet-admin-ban')">📢 Info Direction</button>
    <button class="onglet-btn" onclick="changerOnglet('onglet-faq')">❓ F.A.Q. Admin</button>
</div>
