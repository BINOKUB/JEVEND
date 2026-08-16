// =============================================================================
// NOM DU SCRIPT : memory_engine.js
// REVISION     : 2.0 - Gestion dynamique des kits de symboles
// =============================================================================

let cartesMemory = [];
let cartesRetournees = [];
let pairesTrouvees = 0;
let nombreCoups = 0;
let verouillagePlateau = false;
let kitActuelSymboles = [];

function obtenirSymbolesPourPartie() {
    const elSelect = document.getElementById('selectKitMemory');
    const choix = elSelect ? elSelect.value : 'aleatoire';

    if (choix !== 'aleatoire' && KITS_MEMORY[choix]) {
        return KITS_MEMORY[choix].symboles;
    }
    // Si aléatoire
    return obtenirKitAleatoire().symboles;
}

function changerKitExplicitely() {
    nouvellePartieMemory();
}

function initialiserMemory() {
    const sauvegarde = localStorage.getItem('jevend_memory_partie');
    if (sauvegarde) {
        try {
            const etat = JSON.parse(sauvegarde);
            cartesMemory = etat.cartes;
            pairesTrouvees = etat.paires;
            nombreCoups = etat.coups;
            kitActuelSymboles = etat.kitSymboles || KITS_MEMORY.utilitaire.symboles;
            
            if (document.getElementById('selectKitMemory') && etat.choixKit) {
                document.getElementById('selectKitMemory').value = etat.choixKit;
            }

            rafraichirStatsMemory();
            genererGrilleMemoryHTML();
            
            cartesMemory.forEach((c, idx) => {
                const el = document.querySelector(`.memory-card[data-index="${idx}"]`);
                if (el) {
                    if (c.trouvee) {
                        el.classList.add('matched', 'flipped');
                    } else if (c.revelee) {
                        el.classList.add('flipped');
                    }
                }
            });
            return;
        } catch(e) { }
    }
    nouvellePartieMemory();
}

function nouvellePartieMemory() {
    localStorage.removeItem('jevend_memory_partie');
    
    const symbolesDuKit = obtenirSymbolesPourPartie();
    kitActuelSymboles = symbolesDuKit;

    const paires = [...symbolesDuKit, ...symbolesDuKit];
    
    for (let i = paires.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [paires[i], paires[j]] = [paires[j], paires[i]];
    }

    cartesMemory = paires.map(symbole => ({
        symbole: symbole,
        revelee: false,
        trouvee: false
    }));

    cartesRetournees = [];
    pairesTrouvees = 0;
    nombreCoups = 0;
    verouillagePlateau = false;

    const winMsg = document.getElementById('memoryWinMsg');
    if (winMsg) winMsg.style.display = 'none';

    rafraichirStatsMemory();
    genererGrilleMemoryHTML();
    sauvegarderEtatMemory();
}

function genererGrilleMemoryHTML() {
    const board = document.getElementById('memoryBoard');
    if (!board) return;
    
    board.innerHTML = '';
    cartesMemory.forEach((carte, index) => {
        const cardEl = document.createElement('div');
        cardEl.className = 'memory-card';
        cardEl.dataset.index = index;

        cardEl.innerHTML = `
            <div class="card-face card-back"></div>
            <div class="card-face card-front">${carte.symbole}</div>
        `;

        cardEl.addEventListener('click', () => retournerCarteMemory(index));
        board.appendChild(cardEl);
    });
}

function retournerCarteMemory(index) {
    if (verouillagePlateau) return;
    const carte = cartesMemory[index];
    if (carte.trouvee || carte.revelee) return;

    const cardEl = document.querySelector(`.memory-card[data-index="${index}"]`);
    if (!cardEl) return;

    carte.revelee = true;
    cardEl.classList.add('flipped');
    cartesRetournees.push(index);

    if (cartesRetournees.length === 2) {
        nombreCoups++;
        rafraichirStatsMemory();
        verifierPaireMemory();
    }
}

function verifierPaireMemory() {
    verouillagePlateau = true;
    const [idx1, idx2] = cartesRetournees;
    const carte1 = cartesMemory[idx1];
    const carte2 = cartesMemory[idx2];

    const el1 = document.querySelector(`.memory-card[data-index="${idx1}"]`);
    const el2 = document.querySelector(`.memory-card[data-index="${idx2}"]`);

    if (carte1.symbole === carte2.symbole) {
        carte1.trouvee = true;
        carte2.trouvee = true;
        el1.classList.add('matched');
        el2.classList.add('matched');
        
        pairesTrouvees++;
        rafraichirStatsMemory();
        cartesRetournees = [];
        verouillagePlateau = false;

        sauvegarderEtatMemory();

        if (pairesTrouvees === (kitActuelSymboles.length)) {
            const winMsg = document.getElementById('memoryWinMsg');
            const finalCoups = document.getElementById('finalCoups');
            if (winMsg && finalCoups) {
                finalCoups.textContent = nombreCoups;
                winMsg.style.display = 'block';
            }
            localStorage.removeItem('jevend_memory_partie');
        }
    } else {
        setTimeout(() => {
            carte1.revelee = false;
            carte2.revelee = false;
            if (el1) el1.classList.remove('flipped');
            if (el2) el2.classList.remove('flipped');
            
            cartesRetournees = [];
            verouillagePlateau = false;
            sauvegarderEtatMemory();
        }, 900);
    }
}

function rafraichirStatsMemory() {
    const elCoups = document.getElementById('memoryCoups');
    const elPaires = document.getElementById('memoryPaires');
    const totalPaires = kitActuelSymboles.length || 8;
    if (elCoups) elCoups.textContent = nombreCoups;
    if (elPaires) elPaires.textContent = `${pairesTrouvees}/${totalPaires}`;
}

function sauvegarderEtatMemory() {
    const elSelect = document.getElementById('selectKitMemory');
    const etat = {
        cartes: cartesMemory,
        paires: pairesTrouvees,
        coups: nombreCoups,
        kitSymboles: kitActuelSymboles,
        choixKit: elSelect ? elSelect.value : 'aleatoire'
    };
    localStorage.setItem('jevend_memory_partie', JSON.stringify(etat));
}

window.addEventListener('DOMContentLoaded', initialiserMemory);
