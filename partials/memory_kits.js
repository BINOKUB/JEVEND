// =============================================================================
// NOM DU SCRIPT : memory_kits.js
// REVISION     : 1.0 - Kits de symboles modulaires pour le Memory
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
    }
};

// Fonction utilitaire pour récupérer un kit au hasard
function obtenirKitAleatoire() {
    const cles = Object.keys(KITS_MEMORY);
    const cleHasard = cles[Math.floor(Math.random() * cles.length)];
    return KITS_MEMORY[cleHasard];
}
