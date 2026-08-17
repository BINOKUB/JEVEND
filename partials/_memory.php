<?php
// =============================================================================
// NOM DU SCRIPT : partials/_memory.php
// REVISION     : 3.0 - Affichage du Champion de la Semaine
// =============================================================================
?>
<link rel="stylesheet" href="memory.css">

<div class="memory-container">
    
    <!-- BANNIÈRE DU CHAMPION -->
    <div id="memoryChampionBar" style="background: linear-gradient(135deg, #fef3c7, #fde68a); border: 1px solid #f59e0b; padding: 8px 12px; border-radius: 8px; margin-bottom: 12px; font-size: 0.85rem; font-weight: bold; color: #78350f; text-align: center;">
        🏆 Record de la semaine : 
        <span id="txtChampion">
            <?php if (!empty($champion_semaine)): ?>
                <?= htmlspecialchars($champion_semaine['nom']) ?> (<?= $champion_semaine['nombre_coups'] ?> coups en <?= $champion_semaine['temps_secondes'] ?>s)
            <?php else: ?>
                Aucun record cette semaine. Soyez le premier !
            <?php endif; ?>
        </span>
    </div>

    <div class="memory-header">
        <div style="display: flex; flex-direction: column; gap: 4px;">
            <div class="memory-stats">
                <div>Coups : <span id="memoryCoups">0</span></div>
                <div>Paires : <span id="memoryPaires">0/8</span></div>
            </div>
            <select id="selectKitMemory" onchange="changerKitExplicitely()" style="margin-top: 6px; padding: 4px 8px; border-radius: 6px; border: 1px solid #cbd5e1; font-weight: bold; font-size: 0.8rem; color: #334155; background: #ffffff;">
                <option value="aleatoire">🎲 Aleatoire</option>
    <option value="utilitaire">🏷️ Objets & Utilitaires</option>
    <option value="geometrie">🔷 Formes Géométriques</option>
    <option value="nombres">🔢 Nombres & Chiffres</option>
    <option value="nature">🌱 Nature & Animaux</option>
    <option value="musique">🎸 Instruments de Musique</option>
    <option value="outils">🔨 Ensemble d'Outils</option>
    <option value="lettres">🔤 Lettres Mélangées</option>
    <option value="informatique">💻 Symboles Informatique</option>
    <option value="puces">⚡ Puces & Technologie</option>
    <option value="grec">🏛️ Caractères Grecs</option>
    <option value="binaire">🔢 Binaire & Données</option>
            </select>
        </div>

        <button onclick="nouvellePartieMemory()" style="padding: 8px 14px; background: #0f172a; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">
            ↻ Recommencer
        </button>
    </div>

    <div class="memory-board" id="memoryBoard"></div>

    <div class="memory-win-msg" id="memoryWinMsg">
        🎉 Félicitations ! Vous avez trouvé toutes les paires en <span id="finalCoups">0</span> coups !
    </div>

</div>

<script src="memory_kits.js"></script>
<script src="memory_engine.js"></script>
