<?php
// =============================================================================
// SCRIPT : sudoku.php
// REVISION : 1.0 - Sudoku Ultra-Fluide, Ergonomique & Responsive
// =============================================================================
session_start();
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Sudoku — jevend.com</title>
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --sudoku-bg: #ffffff;
            --sudoku-border-thick: #0f172a;
            --sudoku-border-thin: #cbd5e1;
            --cell-initial: #0f172a;
            --cell-user: #2563eb;
            --cell-selected: #dbeafe;
            --cell-highlight: #f1f5f9;
            --cell-same-num: #dcfce7;
            --cell-error: #fee2e2;
            --cell-error-text: #dc2626;
        }

        body.admin-body { background-color: #f8fafc; }

        .sudoku-container {
            max-width: 500px;
            margin: 20px auto;
            padding: 15px;
            box-sizing: border-box;
        }

        .sudoku-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            background: #ffffff;
            padding: 12px 20px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .sudoku-timer {
            font-family: monospace;
            font-size: 1.2rem;
            font-weight: bold;
            color: #0f172a;
        }

        /* GRILLE DE SUDOKU */
        .sudoku-board {
            display: grid;
            grid-template-columns: repeat(9, 1fr);
            aspect-ratio: 1;
            background: var(--sudoku-bg);
            border: 3px solid var(--sudoku-border-thick);
            border-radius: 6px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
            user-select: none;
            touch-action: manipulation;
        }

        .sudoku-cell {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: clamp(1.1rem, 4vw, 1.6rem);
            font-weight: 600;
            border-right: 1px solid var(--sudoku-border-thin);
            border-bottom: 1px solid var(--sudoku-border-thin);
            cursor: pointer;
            transition: background-color 0.15s ease;
            position: relative;
        }

        /* Bordures épaisses des blocs 3x3 */
        .sudoku-cell:nth-child(3n) { border-right: 2px solid var(--sudoku-border-thick); }
        .sudoku-cell:nth-child(9n) { border-right: none; }
        .sudoku-cell:nth-child(n+19):nth-child(-n+27),
        .sudoku-cell:nth-child(n+46):nth-child(-n+54) { border-bottom: 2px solid var(--sudoku-border-thick); }

        /* États des cases */
        .sudoku-cell.initial { color: var(--cell-initial); font-weight: 800; }
        .sudoku-cell.user-entry { color: var(--cell-user); }
        .sudoku-cell.highlighted { background-color: var(--cell-highlight); }
        .sudoku-cell.same-number { background-color: var(--cell-same-num); }
        .sudoku-cell.selected { background-color: var(--cell-selected) !important; outline: 2px solid #2563eb; z-index: 2; }
        .sudoku-cell.error { background-color: var(--cell-error) !important; color: var(--cell-error-text) !important; }

        /* Notes / Mode Crayon */
        .sudoku-cell .notes-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            width: 100%;
            height: 100%;
            font-size: 0.65rem;
            color: #64748b;
            text-align: center;
            pointer-events: none;
        }

        /* PAVÉ TACTILE D'ÉCRITURE */
        .sudoku-keypad {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 8px;
            margin-top: 15px;
        }

        .keypad-btn {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px 0;
            font-size: 1.2rem;
            font-weight: bold;
            color: #0f172a;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            transition: all 0.15s ease;
        }

        .keypad-btn:active { transform: scale(0.95); background-color: #eff6ff; }
        .keypad-btn.completed { opacity: 0.3; cursor: not-allowed; }
        .keypad-btn .count-badge { font-size: 0.65rem; color: #64748b; margin-top: 2px; }

        /* BARRE D'ACTIONS */
        .sudoku-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn-tool {
            flex: 1;
            padding: 10px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            border-radius: 6px;
            font-weight: bold;
            font-size: 0.85rem;
            color: #334155;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-tool.active { background-color: #2563eb; color: #ffffff; border-color: #2563eb; }
    </style>
</head>
<body class="admin-body">

    <?php include 'partials/_nav_membre.php'; ?>

    <div class="sudoku-container">
        
        <div class="sudoku-header">
            <div>
                <select id="difficulteSelect" onchange="nouvellePartie()" style="padding: 6px; border-radius: 4px; border: 1px solid #cbd5e1; font-weight: bold;">
                    <option value="facile">Facile</option>
                    <option value="moyen" selected>Moyen</option>
                    <option value="difficile">Difficile</option>
                </select>
            </div>
            <div class="sudoku-timer" id="timer">00:00</div>
            <button onclick="nouvellePartie()" style="padding: 6px 12px; background: #0f172a; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">↻ Nouveau</button>
        </div>

        <div class="sudoku-board" id="board"></div>

        <div class="sudoku-actions">
            <button class="btn-tool" id="btnPencil" onclick="toggleModeCrayon()">✏️ Crayon</button>
            <button class="btn-tool" onclick="effacerCase()">⌫ Effacer</button>
            <button class="btn-tool" onclick="annulerCoup()">↩️ Annuler</button>
        </div>

        <div class="sudoku-keypad" id="keypad"></div>

    </div>

    <script>
    // --- GRILLES PREFAITES PAR NIVEAU ---
    const BANQUE_GRILLLES = {
        facile: [
            "530070000600195000098000060800060003400803001700020006060000280000419005000080079",
            "000000010400000000020000000000050407008000300001090000300400200050100000000806000"
        ],
        moyen: [
            "000260701680070090190004500820100040004602900050003028009300074040050036703018000"
        ],
        difficile: [
            "000000085000210009960080100500800016000000000890006007009070052300054000480000000"
        ]
    };

    const SOLUCE_TEST = "534678912672195348198342567859761423426853791713924856961537284287419635345286179";

    let grilleInitiale = [];
    let grilleActuelle = [];
    let solution = [];
    let notes = Array(81).fill().map(() => new Set());
    let caseSelectionnee = null;
    let modeCrayon = false;
    let historique = [];
    let secondes = 0;
    let timerInterval = null;

    function lancerTimer() {
        clearInterval(timerInterval);
        secondes = 0;
        timerInterval = setInterval(() => {
            secondes++;
            const m = String(Math.floor(secondes / 60)).padStart(2, '0');
            const s = String(secondes % 60).padStart(2, '0');
            document.getElementById('timer').textContent = `${m}:${s}`;
        }, 1000);
    }

    function nouvellePartie() {
        const diff = document.getElementById('difficulteSelect').value;
        const liste = BANQUE_GRILLLES[diff] || BANQUE_GRILLLES.moyen;
        const rawBoard = liste[Math.floor(Math.random() * liste.length)];

        grilleInitiale = rawBoard.split('').map(v => parseInt(v));
        grilleActuelle = [...grilleInitiale];
        solution = SOLUCE_TEST.split('').map(v => parseInt(v)); // Simplifié pour test
        notes = Array(81).fill().map(() => new Set());
        historique = [];
        caseSelectionnee = null;

        lancerTimer();
        genererPlateau();
        genererClavier();
        rafraichirPlateau();
    }

    function genererPlateau() {
        const board = document.getElementById('board');
        board.innerHTML = '';
        for (let i = 0; i < 81; i++) {
            const cell = document.createElement('div');
            cell.className = 'sudoku-cell';
            cell.dataset.index = i;
            cell.addEventListener('click', () => selectionnerCase(i));
            board.appendChild(cell);
        }
    }

    function selectionnerCase(index) {
        caseSelectionnee = index;
        rafraichirPlateau();
    }

    function rafraichirPlateau() {
        const cells = document.querySelectorAll('.sudoku-cell');
        const valSelect = caseSelectionnee !== null ? grilleActuelle[caseSelectionnee] : null;
        const rowSelect = caseSelectionnee !== null ? Math.floor(caseSelectionnee / 9) : null;
        const colSelect = caseSelectionnee !== null ? caseSelectionnee % 9 : null;
        const blockSelect = caseSelectionnee !== null ? Math.floor(rowSelect / 3) * 3 + Math.floor(colSelect / 3) : null;

        cells.forEach((cell, i) => {
            const val = grilleActuelle[i];
            const row = Math.floor(i / 9);
            const col = i % 9;
            const block = Math.floor(row / 3) * 3 + Math.floor(col / 3);

            cell.className = 'sudoku-cell';
            cell.innerHTML = '';

            if (grilleInitiale[i] !== 0) {
                cell.classList.add('initial');
                cell.textContent = val;
            } else if (val !== 0) {
                cell.classList.add('user-entry');
                cell.textContent = val;
            } else if (notes[i].size > 0) {
                const notesGrid = document.createElement('div');
                notesGrid.className = 'notes-grid';
                for (let n = 1; n <= 9; n++) {
                    const sub = document.createElement('div');
                    sub.textContent = notes[i].has(n) ? n : '';
                    notesGrid.appendChild(sub);
                }
                cell.appendChild(notesGrid);
            }

            // Surlignages ergonomiques
            if (i === caseSelectionnee) {
                cell.classList.add('selected');
            } else if (valSelect && val === valSelect) {
                cell.classList.add('same-number');
            } else if (row === rowSelect || col === colSelect || block === blockSelect) {
                cell.classList.add('highlighted');
            }
        });

        mettreAJourCompteurClavier();
    }

    function genererClavier() {
        const keypad = document.getElementById('keypad');
        keypad.innerHTML = '';
        for (let i = 1; i <= 9; i++) {
            const btn = document.createElement('button');
            btn.className = 'keypad-btn';
            btn.id = `key-${i}`;
            btn.innerHTML = `${i}<span class="count-badge" id="badge-${i}">9</span>`;
            btn.addEventListener('click', () => saisirChiffre(i));
            keypad.appendChild(btn);
        }
    }

    function mettreAJourCompteurClavier() {
        for (let num = 1; num <= 9; num++) {
            const count = grilleActuelle.filter(v => v === num).length;
            const reste = 9 - count;
            const badge = document.getElementById(`badge-${num}`);
            const btn = document.getElementById(`key-${num}`);

            if (badge && btn) {
                badge.textContent = reste > 0 ? reste : '✓';
                if (reste <= 0) {
                    btn.classList.add('completed');
                } else {
                    btn.classList.remove('completed');
                }
            }
        }
    }

    function saisirChiffre(num) {
        if (caseSelectionnee === null || grilleInitiale[caseSelectionnee] !== 0) return;

        historique.push({
            index: caseSelectionnee,
            valeurPrecedente: grilleActuelle[caseSelectionnee],
            notesPrecedentes: new Set(notes[caseSelectionnee])
        });

        if (modeCrayon) {
            if (notes[caseSelectionnee].has(num)) {
                notes[caseSelectionnee].delete(num);
            } else {
                notes[caseSelectionnee].add(num);
            }
            grilleActuelle[caseSelectionnee] = 0;
        } else {
            grilleActuelle[caseSelectionnee] = num;
            notes[caseSelectionnee].clear();
        }

        rafraichirPlateau();
    }

    function effacerCase() {
        if (caseSelectionnee === null || grilleInitiale[caseSelectionnee] !== 0) return;
        grilleActuelle[caseSelectionnee] = 0;
        notes[caseSelectionnee].clear();
        rafraichirPlateau();
    }

    function toggleModeCrayon() {
        modeCrayon = !modeCrayon;
        document.getElementById('btnPencil').classList.toggle('active', modeCrayon);
    }

    function annulerCoup() {
        if (historique.length === 0) return;
        const dernier = historique.pop();
        grilleActuelle[dernier.index] = dernier.valeurPrecedente;
        notes[dernier.index] = dernier.notesPrecedentes;
        caseSelectionnee = dernier.index;
        rafraichirPlateau();
    }

    // Gestion du clavier physique PC
    window.addEventListener('keydown', (e) => {
        if (e.key >= '1' && e.key <= '9') {
            saisirChiffre(parseInt(e.key));
        } else if (e.key === 'Backspace' || e.key === 'Delete') {
            effacerCase();
        }
    });

    // Démarrage automatique
    window.addEventListener('DOMContentLoaded', nouvellePartie);
    </script>
</body>
</html>
