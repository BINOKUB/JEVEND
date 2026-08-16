<?php
// =============================================================================
// NOM DU SCRIPT : partials/_memory.php
// REVISION     : 2.0 - Intégration des kits modulaires
// =============================================================================
?>
<link rel="stylesheet" href="memory.css">

<div class="memory-container">
    
    <div class="memory-header">
        <div style="display: flex; flex-direction: column; gap: 4px;">
            <div class="memory-stats">
                <div>Coups : <span id="memoryCoups">0</span></div>
                <div>Paires : <span id="memoryPaires">0/8</span></div>
            </div>
            <!-- Sélecteur de kit -->
            <select id="selectKitMemory" onchange="changerKitExplicitely()" style="margin-top: 6px; padding: 4px 8px; border-radius: 6px; border: 1px solid #cbd5e1; font-weight: bold; font-size: 0.8rem; color: #334155; background: #ffffff;">
                <option value="aleatoire">🎲 Kit Aléatoire</option>
                <option value="utilitaire">🏷️ Objets & Utilitaires</option>
                <option value="geometrie">🔷 Formes Géométriques</option>
                <option value="nombres">🔢 Nombres & Chiffres</option>
                <option value="nature">🌱 Nature & Animaux</option>
            </select>
        </div>

        <button onclick="nouvellePartieMemory()" style="padding: 8px 14px; background: #0f172a; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: background 0.2s;">
            ↻ Recommencer
        </button>
    </div>

    <div class="memory-board" id="memoryBoard"></div>

    <div class="memory-win-msg" id="memoryWinMsg">
        🎉 Félicitations ! Vous avez trouvé toutes les paires en <span id="finalCoups">0</span> coups !
    </div>

</div>

<!-- Chargement des scripts -->
<script src="memory_kits.js"></script>
<script src="memory_engine.js"></script>
