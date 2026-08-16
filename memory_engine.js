// =============================================================================
// NOM DU SCRIPT : memory_engine.js
// REVISION     : 3.0 - Intégration du chrono, kits et envoi AJAX des scores
// =============================================================================

let cartesMemory = [];
let cartesRetournees = [];
let pairesTrouvees = 0;
let nombreCoups = 0;
let verouillagePlateau = false;
let kitActuelSymboles = [];

// Gestion du Chronomètre
let secondesMemory = 0;
let timerMemoryInterval = null;

function demarrerTimerMemory(tempsDepart = 0) {
    clearInterval(timerMemoryInterval);
    secondesMemory = tempsDepart;
    timerMemoryInterval = setInterval(() => {
        secondesMemory++;
    }, 1000);
}

function arreterTimerMemory() {
    clearInterval(timerMemoryInterval);
}

function obtenirSymbolesPourPartie() {
    const elSelect = document.getElementById('selectKitMemory');
    const choix = elSelect ? elSelect.value : 'aleatoire';

    if (choix !== 'aleatoire' && typeof KITS_MEMORY !== 'undefined' && KITS_MEMORY[choix]) {
        return KITS_MEMORY[choix].symboles;
    }
    if (typeof obtenirKitAleatoire === 'function') {
        return obtenirKitAleatoire().symboles;
    }
    return ['🚗', '🏠', '📱', '🎸', '📷', '🛠️', '💻', '🚲'];
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
            secondesMemory = etat.secondes || 0;
            kitActuelSymboles = etat.kitSymboles || ['🚗', '🏠', '📱', '🎸', '📷', '🛠️', '💻', '🚲'];
            
            if (document.getElementById('selectKitMemory') && etat.choixKit) {
                document.getElementById('selectKitMemory').value = etat.choixKit;
            }

            rafraichirStatsMemory();
            genererGrilleMemoryHTML();
            demarrerTimerMemory(secondesMemory);
            
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
    demarrerTimerMemory(0);
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

        // VICTOIRE
        if (pairesTrouvees === kitActuelSymboles.length) {
            arreterTimerMemory();

            const winMsg = document.getElementById('memoryWinMsg');
            const finalCoups = document.getElementById('finalCoups');
            if (winMsg && finalCoups) {
                finalCoups.textContent = `${nombreCoups} (${secondesMemory}s)`;
                winMsg.style.display = 'block';
            }

            // Envoi AJAX du score au serveur
            const elSelect = document.getElementById('selectKitMemory');
            const formData = new FormData();
            formData.append('coups', nombreCoups);
            formData.append('temps', secondesMemory);
            formData.append('kit', elSelect ? elSelect.value : 'utilitaire');

            fetch('enregistrer_score_memory.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'succes' && data.top) {
                    const elTxt = document.getElementById('txtChampion');
                    if (elTxt) {
                        elTxt.textContent = `${data.top.nom} (${data.top.nombre_coups} coups en ${data.top.temps_secondes}s)`;
                    }
                }
            })
            .catch(err => console.error("Erreur enregistrement score :", err));

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
        secondes: secondesMemory,
        kitSymboles: kitActuelSymboles,
        choixKit: elSelect ? elSelect.value : 'aleatoire'
    };
    localStorage.setItem('jevend_memory_partie', JSON.stringify(etat));
}

window.addEventListener('DOMContentLoaded', initialiserMemory);
