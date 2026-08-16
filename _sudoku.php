<?php
// =============================================================================
// NOM DU SCRIPT : partials/_sudoku.php
// REVISION     : 2.0 - Ajout du sélecteur Mode Assisté / Mode Expert
// =============================================================================
?>
<link rel="stylesheet" href="sudoku.css">

<div class="sudoku-container">
    
    <div class="sudoku-header">
        <div style="display: flex; gap: 8px; align-items: center;">
            <select id="difficulteSelect" onchange="nouvellePartieSudoku()" style="padding: 6px; border-radius: 4px; border: 1px solid #cbd5e1; font-weight: bold;">
                <option value="facile">Facile</option>
                <option value="moyen" selected>Moyen</option>
                <option value="difficile">Difficile</option>
            </select>

            <select id="modeJeuSelect" onchange="changerModeJeuSudoku()" style="padding: 6px; border-radius: 4px; border: 1px solid #cbd5e1; font-weight: bold; background-color: #eff6ff; color: #1e40af;">
                <option value="assiste" selected>🛡️ Assisté</option>
                <option value="expert">🧠 Expert</option>
            </select>
        </div>

        <div class="sudoku-timer" id="sudokuTimer">00:00</div>
        <button onclick="nouvellePartieSudoku()" style="padding: 6px 12px; background: #0f172a; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">↻ Nouveau</button>
    </div>

    <div class="sudoku-board" id="sudokuBoard"></div>

    <div class="sudoku-actions">
        <button class="btn-tool" id="btnPencilSudoku" onclick="toggleModeCrayonSudoku()">✏️ Crayon</button>
        <button class="btn-tool" onclick="effacerCaseSudoku()">⌫ Effacer</button>
        <button class="btn-tool" onclick="annulerCoupSudoku()">↩️ Annuler</button>
    </div>

    <div class="sudoku-keypad" id="sudokuKeypad"></div>

</div>

<!-- Chargement du moteur logique JS -->
<script src="sudoku_engine.js"></script>
