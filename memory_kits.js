// =============================================================================
// NOM DU SCRIPT : memory_kits.js
// REVISION     : 1.1 - Ajout des kits musique, outils, lettres, informatique, puces, grec et binaire
// =============================================================================

const KITS_MEMORY = {
    utilitaire: {
        nom: "🏷️ Objets & Utilitaires",
        symboles: ['🚗', '🏠', '📱', '🎸', '📷', '🛠️', '💻', '🚲']
    },
    geometrie: {
        nom: "🔷 Formes Géométriques",
        symboles: ['🔴', '🟦', '🔺', '⭐', '🔷', '🟣', '⬛', '🔶']
    },
    nombres: {
        nom: "🔢 Nombres & Chiffres",
        symboles: ['1️⃣', '2️⃣', '3️⃣', '4️⃣', '5️⃣', '6️⃣', '7️⃣', '8️⃣']
    },
    nature: {
        nom: "🌱 Nature & Animaux",
        symboles: ['🐶', '🐱', '🌲', '🌸', '🦁', '🐬', '🦉', '🍎']
    },
    musique: {
        nom: "🎸 Instruments de Musique",
        symboles: ['🎸', '🥁', '🎷', '🎺', '🎻', '🎹', '🪕', '🪘']
    },
    outils: {
        nom: "🔨 Ensemble d'Outils",
        symboles: ['🔨', '🪛', '🔧', '🪓', '🪚', '🪜', '⛏️', '🧹']
    },
    lettres: {
        nom: "🔤 Lettres Mélangées",
        symboles: ['a', 'B', 'c', 'D', 'e', 'F', 'g', 'H']
    },
    informatique: {
        nom: "💻 Symboles Informatique",
        symboles: ['</>', '{}', '[]', '()', '&&', '||', '!=', '==']
    },
    puces: {
        nom: "⚡ Puces & Technologie",
        symboles: ['💾', '⚡', '🔋', '🔌', '🎛️', '🛜', '📡', '🕹️']
    },
    grec: {
        nom: "🏛️ Caractères Grecs",
        symboles: ['α', 'β', 'γ', 'δ', 'ε', 'ζ', 'η', 'Ω']
    },
    binaire: {
        nom: "🔢 Binaire & Données",
        symboles: ['00', '01', '10', '11', '0', '1', '000', '111']
    }
};

// Fonction utilitaire pour récupérer un kit au hasard
function obtenirKitAleatoire() {
    const cles = Object.keys(KITS_MEMORY);
    const cleHasard = cles[Math.floor(Math.random() * cles.length)];
    return KITS_MEMORY[cleHasard];
}
