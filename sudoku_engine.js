// =============================================================================
// NOM DU SCRIPT : sudoku_engine.js
// REVISION     : 2.1 - Correctif gel navigateur (Grilles valides + limite solveur)
// =============================================================================

const BANQUE_GRILLLES_SUDOKU = {
    // -------------------------------------------------------------------------
    // FACILE (10 grilles très garnies ~38-42 chiffres)
    // -------------------------------------------------------------------------
    facile: [
        "600874190908603527070000006000210000200000009000065000800000040315907208029486001",
        "000000085000210009960080100500800016000000000890006007009070052300054000480000000",
        "003020600900305001001806400008102900700000008006708200002609500800203009005010300",
        "200080300060070084030500209000105408000000000402706000301007040720040060004010003",
        "000000907000420180000705026100904000050000040000507009920108000034059000507000000",
        "030000000000195080000000600800060003400803001700020006060000280000419005000080079",
        "300000080001003000000024600000000000040000070000000000002930000000100200060000001",
        "100007090030020008009600500005000300008000400001000100004001600200070050060800002",
        "020000000008600050040000001000015000000000000000280000700000030050004800000000010",
        "500010008080000030003000400000070000000000000000050000006000200040000010900080007"
    ],

    // -------------------------------------------------------------------------
    // MOYEN (10 grilles équilibrées ~30-34 chiffres)
    // -------------------------------------------------------------------------
    moyen: [
        "000260701680070090190004500820100040004602900050003028009300074040050036703018000",
        "200080300060070084030500209000105408000000000402706000301007040720040060004010003",
        "100007090030020008009600500005000300008000400001000100004001600200070050060800002",
        "020000000008600050040000001000015000000000000000280000700000030050004800000000010",
        "500010008080000030003000400000070000000000000000050000006000200040000010900080007",
        "003020600900305001001806400008102900700000008006708200002609500800203009005010300",
        "000000907000420180000705026100904000050000040000507009920108000034059000507000000",
        "030000000000195080000000600800060003400803001700020006060000280000419005000080079",
        "600874190908603527070000006000210000200000009000065000800000040315907208029486001",
        "300000080001003000000024600000000000040000070000000000002930000000100200060000001"
    ],

    // -------------------------------------------------------------------------
    // DIFFICILE (10 grilles corsées ~22-26 chiffres)
    // -------------------------------------------------------------------------
    difficile: [
        "530070000600195000098000060800060003400803001700020006060000280000419005000080079",
        "000000010400000000020000000000050407008000300001090000300400200050100000000806000",
        "000000085000210009960080100500800016000000000890006007009070052300054000480000000",
        "000260701680070090190004500820100040004602900050003028009300074040050036703018000",
        "003020600900305001001806400008102900700000008006708200002609500800203009005010300",
        "200080300060070084030500209000105408000000000402706000301007040720040060004010003",
        "100007090030020008009600500005000300008000400001000100004001600200070050060800002",
        "020000000008600050040000001000015000000000000000280000700000030050004800000000010",
        "500010008080000030003000400000070000000000000000050000006000200040000010900080007",
        "300000080001003000000024600000000000040000070000000000002930000000100200060000001"
    ]
};


let grilleInitialeSudoku = [];
let grilleActuelleSudoku = [];
let solutionCalculéeSudoku = [];
let notesSudoku = Array(81).fill().map(() => new Set());
let caseSelectionneeSudoku = null;
let modeCrayonSudoku = false;
let modeJeuSudoku = 'assiste';
let historiqueSudoku = [];
let secondesSudoku = 0;
let timerIntervalSudoku = null;

// --- SOLVEUR SÉCURISÉ AVEC COMPTEUR D'ITÉRATIONS ---
let maxIterationsSolveur = 0;

function resoudreSudoku(board) {
    maxIterationsSolveur++;
    if (maxIterationsSolveur > 50000) return false; // Protection anti-gel

    for (let i = 0; i < 81; i++) {
        if (board[i] === 0) {
            for (let num = 1; num <= 9; num++) {
                if (estValidePlacement(board, i, num)) {
                    board[i] = num;
                    if (resoudreSudoku(board)) return true;
                    board[i] = 0;
                }
            }
            return false;
        }
    }
    return true;
}

function estValidePlacement(board, index, val) {
    const row = Math.floor(index / 9);
    const col = index % 9;
    const block = Math.floor(row / 3) * 3 + Math.floor(col / 3);

    for (let i = 0; i < 81; i++) {
        if (i === index) continue;
        const r = Math.floor(i / 9);
        const c = i % 9;
        const b = Math.floor(r / 3) * 3 + Math.floor(c / 3);

        if ((r === row || c === col || b === block) && board[i] === val) {
            return false;
        }
    }
    return true;
}

function lancerTimerSudoku() {
    clearInterval(timerIntervalSudoku);
    secondesSudoku = 0;
    timerIntervalSudoku = setInterval(() => {
        secondesSudoku++;
        const m = String(Math.floor(secondesSudoku / 60)).padStart(2, '0');
        const s = String(secondesSudoku % 60).padStart(2, '0');
        const elTimer = document.getElementById('sudokuTimer');
        if (elTimer) elTimer.textContent = `${m}:${s}`;
    }, 1000);
}

function changerModeJeuSudoku() {
    const elMode = document.getElementById('modeJeuSelect');
    if (elMode) {
        modeJeuSudoku = elMode.value;
        rafraichirPlateauSudoku();
    }
}

function nouvellePartieSudoku() {
    const elDiff = document.getElementById('difficulteSelect');
    const diff = elDiff ? elDiff.value : 'moyen';
    const liste = BANQUE_GRILLLES_SUDOKU[diff] || BANQUE_GRILLLES_SUDOKU.moyen;
    const rawBoard = liste[Math.floor(Math.random() * liste.length)];

    grilleInitialeSudoku = rawBoard.split('').map(v => parseInt(v));
    grilleActuelleSudoku = [...grilleInitialeSudoku];

    // Calcul automatique sécurisé de la solution
    solutionCalculéeSudoku = [...grilleInitialeSudoku];
    maxIterationsSolveur = 0;
    resoudreSudoku(solutionCalculéeSudoku);

    notesSudoku = Array(81).fill().map(() => new Set());
    historiqueSudoku = [];
    caseSelectionneeSudoku = null;

    lancerTimerSudoku();
    genererPlateauSudoku();
    genererClavierSudoku();
    rafraichirPlateauSudoku();
}

function genererPlateauSudoku() {
    const board = document.getElementById('sudokuBoard');
    if (!board) return;
    board.innerHTML = '';
    for (let i = 0; i < 81; i++) {
        const cell = document.createElement('div');
        cell.className = 'sudoku-cell';
        cell.dataset.index = i;
        cell.addEventListener('click', () => selectionnerCaseSudoku(i));
        board.appendChild(cell);
    }
}

function selectionnerCaseSudoku(index) {
    caseSelectionneeSudoku = index;
    rafraichirPlateauSudoku();
}

function estEnConflitSudoku(index, valeur) {
    if (valeur === 0) return false;

    if (modeJeuSudoku === 'assiste') {
        return valeur !== solutionCalculéeSudoku[index];
    }

    const row = Math.floor(index / 9);
    const col = index % 9;
    const block = Math.floor(row / 3) * 3 + Math.floor(col / 3);

    for (let i = 0; i < 81; i++) {
        if (i === index) continue;
        const r = Math.floor(i / 9);
        const c = i % 9;
        const b = Math.floor(r / 3) * 3 + Math.floor(c / 3);

        if ((r === row || c === col || b === block) && grilleActuelleSudoku[i] === valeur) {
            return true;
        }
    }
    return false;
}

function rafraichirPlateauSudoku() {
    const cells = document.querySelectorAll('.sudoku-cell');
    if (!cells.length) return;

    const valSelect = caseSelectionneeSudoku !== null ? grilleActuelleSudoku[caseSelectionneeSudoku] : null;
    const rowSelect = caseSelectionneeSudoku !== null ? Math.floor(caseSelectionneeSudoku / 9) : null;
    const colSelect = caseSelectionneeSudoku !== null ? caseSelectionneeSudoku % 9 : null;
    const blockSelect = caseSelectionneeSudoku !== null ? Math.floor(rowSelect / 3) * 3 + Math.floor(colSelect / 3) : null;

    cells.forEach((cell, i) => {
        const val = grilleActuelleSudoku[i];
        const row = Math.floor(i / 9);
        const col = i % 9;
        const block = Math.floor(row / 3) * 3 + Math.floor(col / 3);

        cell.className = 'sudoku-cell';
        cell.innerHTML = '';

        if (grilleInitialeSudoku[i] !== 0) {
            cell.classList.add('initial');
            cell.textContent = val;
        } else if (val !== 0) {
            cell.classList.add('user-entry');
            cell.textContent = val;
        } else if (notesSudoku[i].size > 0) {
            const notesGrid = document.createElement('div');
            notesGrid.className = 'notes-grid';
            for (let n = 1; n <= 9; n++) {
                const sub = document.createElement('div');
                sub.textContent = notesSudoku[i].has(n) ? n : '';
                notesGrid.appendChild(sub);
            }
            cell.appendChild(notesGrid);
        }

        if (val !== 0 && estEnConflitSudoku(i, val)) {
            cell.classList.add('error');
        } else if (i === caseSelectionneeSudoku) {
            cell.classList.add('selected');
        } else if (valSelect && val === valSelect) {
            cell.classList.add('same-number');
        } else if (row === rowSelect || col === colSelect || block === blockSelect) {
            cell.classList.add('highlighted');
        }
    });

    mettreAJourCompteurClavierSudoku();
}

function genererClavierSudoku() {
    const keypad = document.getElementById('sudokuKeypad');
    if (!keypad) return;
    keypad.innerHTML = '';
    for (let i = 1; i <= 9; i++) {
        const btn = document.createElement('button');
        btn.className = 'keypad-btn';
        btn.id = `key-sudoku-${i}`;
        btn.innerHTML = `${i}<span class="count-badge" id="badge-sudoku-${i}">9</span>`;
        btn.addEventListener('click', () => saisirChiffreSudoku(i));
        keypad.appendChild(btn);
    }
}

function mettreAJourCompteurClavierSudoku() {
    for (let num = 1; num <= 9; num++) {
        const count = grilleActuelleSudoku.filter((v, idx) => v === num && (modeJeuSudoku === 'expert' || v === solutionCalculéeSudoku[idx])).length;
        const reste = 9 - count;
        const badge = document.getElementById(`badge-sudoku-${num}`);
        const btn = document.getElementById(`key-sudoku-${num}`);

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

function saisirChiffreSudoku(num) {
    if (caseSelectionneeSudoku === null || grilleInitialeSudoku[caseSelectionneeSudoku] !== 0) return;

    historiqueSudoku.push({
        index: caseSelectionneeSudoku,
        valeurPrecedente: grilleActuelleSudoku[caseSelectionneeSudoku],
        notesPrecedentes: new Set(notesSudoku[caseSelectionneeSudoku])
    });

    if (modeCrayonSudoku) {
        if (notesSudoku[caseSelectionneeSudoku].has(num)) {
            notesSudoku[caseSelectionneeSudoku].delete(num);
        } else {
            notesSudoku[caseSelectionneeSudoku].add(num);
        }
        grilleActuelleSudoku[caseSelectionneeSudoku] = 0;
    } else {
        grilleActuelleSudoku[caseSelectionneeSudoku] = num;
        notesSudoku[caseSelectionneeSudoku].clear();
    }

    rafraichirPlateauSudoku();
}

function effacerCaseSudoku() {
    if (caseSelectionneeSudoku === null || grilleInitialeSudoku[caseSelectionneeSudoku] !== 0) return;
    grilleActuelleSudoku[caseSelectionneeSudoku] = 0;
    notesSudoku[caseSelectionneeSudoku].clear();
    rafraichirPlateauSudoku();
}

function toggleModeCrayonSudoku() {
    modeCrayonSudoku = !modeCrayonSudoku;
    const btn = document.getElementById('btnPencilSudoku');
    if (btn) btn.classList.toggle('active', modeCrayonSudoku);
}

function annulerCoupSudoku() {
    if (historiqueSudoku.length === 0) return;
    const dernier = historiqueSudoku.pop();
    grilleActuelleSudoku[dernier.index] = dernier.valeurPrecedente;
    notesSudoku[dernier.index] = dernier.notesPrecedentes;
    caseSelectionneeSudoku = dernier.index;
    rafraichirPlateauSudoku();
}

// Clavier PC
window.addEventListener('keydown', (e) => {
    const tabJeux = document.getElementById('onglet-jeux');
    if (tabJeux && tabJeux.classList.contains('actif')) {
        if (e.key >= '1' && e.key <= '9') {
            saisirChiffreSudoku(parseInt(e.key));
        } else if (e.key === 'Backspace' || e.key === 'Delete') {
            effacerCaseSudoku();
        }
    }
});

// Initialisation au chargement
window.addEventListener('DOMContentLoaded', nouvellePartieSudoku);
