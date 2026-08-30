<!-- partials/_membre-banniere.php -->
<?php
// =============================================================================
// NOM DU SCRIPT : partials/_membre-banniere.php
// REVISION     : 4.1 - Correction stricte du verrouillage du bouton si le slogan est vide
// =============================================================================
if (!isset($_SESSION['id_utilisateur'])) { exit(); }

$p_reg = $tarifs['reguliere']['prix'] ?? 1.00;
$m_reg = 10; // Minimum absolu de 10 jours

// FILTRAGE DES ANNONCES ÉLIGIBLES (> 10 JOURS DE VALIDITÉ RESTANTE)
if (isset($annonces_eligibles) && is_array($annonces_eligibles) && count($annonces_eligibles) > 0) {
    try {
        $stmt_bloquees = $bdd->query("SELECT id_annonce FROM jevend_bannieres_actives WHERE id_annonce IS NOT NULL");
        $ids_bloquees = $stmt_bloquees->fetchAll(PDO::FETCH_COLUMN);

        $annonces_nettoyees = [];
        foreach ($annonces_eligibles as $annonce) {
            $vendu_par_statut_vente = (isset($annonce['statut_vente']) && $annonce['statut_vente'] === 'vendu');
            $vendu_par_statut = (isset($annonce['statut']) && $annonce['statut'] === 'vendu');
            $deja_booste = in_array($annonce['id_annonces'], $ids_bloquees);

            if ($vendu_par_statut_vente || $vendu_par_statut || $deja_booste) {
                continue; 
            }

            // Calcul du temps exact restant
            $stmt_check_exp = $bdd->prepare("SELECT DATEDIFF(date_expiration, NOW()) AS jours_restants FROM jevend_annonces WHERE id_annonces = ?");
            $stmt_check_exp->execute([$annonce['id_annonces']]);
            $jours_restants = (int)$stmt_check_exp->fetchColumn();

            // Règle : 10 jours ou moins restants = pas de bannière possible
            if ($jours_restants <= 10) {
                continue; 
            }

            $annonce['jours_restants'] = $jours_restants;
            $annonces_nettoyees[] = $annonce;
        }
        $annonces_eligibles = $annonces_nettoyees;
    } catch (PDOException $e) { }
}
?>

<style>
    #form-achat-banniere { width: 100% !important; max-width: 100% !important; box-sizing: border-box !important; }
    #form-achat-banniere select, 
    #form-achat-banniere input[type="text"] {
        width: 100% !important;
        padding: 10px 12px !important;
        font-size: 0.95rem !important;
        color: #1e293b !important;
        background-color: #f8fafc !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 6px !important;
        box-sizing: border-box !important;
        outline: none !important;
        transition: border-color 0.2s ease, box-shadow 0.2s ease !important;
    }
    #form-achat-banniere select:focus, 
    #form-achat-banniere input[type="text"]:focus {
        border-color: #2563eb !important;
        background-color: #ffffff !important;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1) !important;
    }
    .champ-groupe { margin-bottom: 20px; }
    .champ-groupe label { display: block; font-weight: 600; color: #334155; font-size: 0.9rem; margin-bottom: 6px; }
    .voyant-badge { font-size: 0.9rem; font-weight: bold; display: flex; align-items: center; gap: 6px; background: #ffffff; padding: 6px 14px; border-radius: 20px; border: 1px solid #e2e8f0; }
</style>

<div class="form-bloc" style="max-width: 100%; margin-bottom: 30px; padding: 25px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box;">
    
    <h2 style="margin-top: 0; margin-bottom: 6px; color: #0f172a; text-align: center; font-size: 1.4rem;">
        🚀 Propulser une annonce en tête de flux
    </h2>
    <p style="text-align: center; color: #64748b; font-size: 0.9rem; margin-top: 0; margin-bottom: 20px;">
        Votre bannière s'insérera proprement aux emplacements dédiés du site.
    </p>

    <div style="display: flex; gap: 12px; justify-content: center; margin-bottom: 25px; background: #f8fafc; padding: 12px 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
        <div class="voyant-badge"><span id="voyant-reguliere">⚪</span> Campagne Régulière (<?= number_format($p_reg, 2, ',', ' ') ?> $ / jour)</div>
    </div>

    <form action="traitement_simulation_stripe.php" method="POST" id="form-achat-banniere">
        <input type="hidden" name="type_banniere" id="type_banniere" value="reguliere" data-prix="<?= $p_reg ?>" data-min="<?= $m_reg ?>">
        
        <div class="champ-groupe">
            <label for="id_annonce">Sélectionnez l'annonce à promouvoir :</label>
            <select name="id_annonce" id="id_annonce" required>
                <?php if (empty($annonces_eligibles)): ?>
                    <option value="">-- Aucune annonce éligible (Requiert plus de 10 jours restants) --</option>
                <?php else: ?>
                    <option value="">-- Choisissez une annonce éligible --</option>
                    <?php foreach ($annonces_eligibles as $elg): ?>
                        <option value="<?= $elg['id_annonces'] ?>" data-jours-restants="<?= $elg['jours_restants'] ?>">
                            <?= htmlspecialchars($elg['titre_objet_nettoye']) ?> (<?= number_format($elg['prix'], 2, ',', ' ') ?> $) — ⏳ <?= $elg['jours_restants'] ?> jours restants
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <small style="display: block; color: #64748b; font-size: 0.78rem; margin-top: 5px; font-style: italic;">
                ℹ️ Seules les annonces disposant de plus de 10 jours de validité s'affichent ici. Les options de durée s'adapteront automatiquement.
            </small>
        </div>

        <div class="champ-groupe">
            <label for="duree_jours">Durée d'affichage (calculée selon le temps restant) :</label>
            <select name="duree_jours" id="duree_jours" required>
                <option value="">-- Choisissez d'abord une annonce --</option>
            </select>
        </div>

        <div id="bloc-prix-total" style="border: 1px solid #bbf7d0; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; text-align: center; display: none; box-sizing: border-box;">
            <span id="prix-total-message">💰 Coût total estimé : <span id="prix-total-valeur">0.00</span> $ CAD</span>
        </div>

        <div class="champ-groupe">
            <label for="texte_banniere">Votre slogan publicitaire (Max 120 caractères) :</label>
            <input type="text" name="texte_banniere" id="texte_banniere" maxlength="120" placeholder="Ex: Profitez d'une estimation gratuite à Matane..." required>
            <small id="char-count" style="display: block; text-align: right; color: #64748b; font-size: 0.8rem; margin-top: 4px;">0 / 120</small>
        </div>

        <div id="bloc-alerte-indisponible" style="display: none; background-color: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 15px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem; font-weight: bold; text-align: center;">
            🚫 Les espaces publicitaires réguliers sont complets pour le moment. Veuillez repasser plus tard.
        </div>

        <button type="submit" id="btn-soumission-banniere" class="btn-action" style="width: 100%; box-sizing: border-box; padding: 12px; font-size: 1rem; font-weight: bold; background-color: #2563eb; color: #ffffff; border: none; border-radius: 6px; cursor: pointer;">
            💳 Passer au paiement sécurisé Stripe
        </button>
    </form>
</div>

<script>
const selectAnnonce = document.getElementById('id_annonce');
const inputForfait = document.getElementById('type_banniere');
const selectDuree = document.getElementById('duree_jours');
const inputSlogan = document.getElementById('texte_banniere');
const btnSoumettre = document.getElementById('btn-soumission-banniere');
const blocPrixTotal = document.getElementById('bloc-prix-total');
const msgPrixTotal = document.getElementById('prix-total-message');
const blocAlerte = document.getElementById('bloc-alerte-indisponible');

let etatReguliere = 'libre';

// Génération dynamique des paliers selon vos règles exactes
function mettreAJourDureesDymaniques() {
    const selectedOption = selectAnnonce.options[selectAnnonce.selectedIndex];
    const joursRestants = parseInt(selectedOption.getAttribute('data-jours-restants')) || 0;

    selectDuree.innerHTML = '<option value="">-- Nombre de jours --</option>';

    if (joursRestants > 10) {
        const paliers = [];
        
        // 1. Toujours inclure le max exact (ex: 28, 15)
        paliers.push(joursRestants);

        // 2. Inclure 20 si les jours restants >= 20
        if (joursRestants >= 20 && !paliers.includes(20)) {
            paliers.push(20);
        }

        // 3. Inclure 10 si les jours restants >= 11
        if (joursRestants >= 10 && !paliers.includes(10)) {
            paliers.push(10);
        }

        // Tri décroissant (ex: 28, 20, 10 ou 15, 10)
        paliers.sort((a, b) => b - a);

        paliers.forEach(jours => {
            const opt = document.createElement('option');
            opt.value = jours;
            opt.textContent = jours + " jours";
            selectDuree.appendChild(opt);
        });
    }

    recalculerPrix();
    validerFormulaire();
}

function recalculerPrix() {
    const jours = parseInt(selectDuree.value) || 0;
    const prixParJour = parseFloat(inputForfait.getAttribute('data-prix')) || 1.00;
    let total = 0; 
    let messageErreur = "";
    selectDuree.setCustomValidity("");

    if (jours > 0) {
        if (jours < 10) {
            messageErreur = "La durée minimale est de 10 jours.";
        } else {
            total = jours * prixParJour;
        }

        if (messageErreur === "" && total > 0) {
            blocPrixTotal.style.backgroundColor = '#f0fdf4'; 
            blocPrixTotal.style.color = '#166534'; 
            blocPrixTotal.style.borderColor = '#bbf7d0';
            msgPrixTotal.innerHTML = `💰 Coût total estimé : <span style="font-size: 1.1rem; font-weight: 800;">${total.toFixed(2).replace('.', ',')}</span> $ CAD`;
            blocPrixTotal.style.display = 'block';
        } else {
            selectDuree.setCustomValidity(messageErreur);
            blocPrixTotal.style.backgroundColor = '#fef2f2'; 
            blocPrixTotal.style.color = '#991b1b'; 
            blocPrixTotal.style.borderColor = '#fecaca';
            msgPrixTotal.innerHTML = `⚠️ Option invalide : ${messageErreur}`;
            blocPrixTotal.style.display = 'block';
        }
    } else { 
        blocPrixTotal.style.display = 'none'; 
    }
}

function validerFormulaire() {
    const annonceOk = selectAnnonce.value !== "";
    const forfaitOk = etatReguliere === 'libre';
    const dureeOk = selectDuree.value !== "" && selectDuree.checkValidity();
    
    // Correction : Le slogan ne doit pas être vide (longueur > 0 après suppression des espaces)
    const sloganOk = inputSlogan.value.trim().length > 0;

    if (annonceOk && forfaitOk && dureeOk && sloganOk) {
        btnSoumettre.disabled = false; 
        btnSoumettre.style.opacity = "1"; 
        btnSoumettre.style.cursor = "pointer";
    } else {
        btnSoumettre.disabled = true; 
        btnSoumettre.style.opacity = "0.5"; 
        btnSoumettre.style.cursor = "not-allowed";
    }
}

function rafraichirVoyants() {
    fetch('verifier_file_queue.php', { method: 'POST' })
    .then(res => res.json())
    .then(data => {
        if (data.statut === 'succes') {
            etatReguliere = data.reguliere || 'libre';
            const voyant = document.getElementById('voyant-reguliere');
            if (etatReguliere === 'libre') {
                voyant.textContent = '🟢';
                blocAlerte.style.display = 'none';
            } else {
                voyant.textContent = '🔴';
                blocAlerte.style.display = 'block';
            }
            validerFormulaire();
        }
    })
    .catch(err => console.error('Erreur d\'état du voyant:', err));
}

selectAnnonce.addEventListener('change', mettreAJourDureesDymaniques);
inputSlogan.addEventListener('input', validerFormulaire);
inputSlogan.addEventListener('input', function() { document.getElementById('char-count').textContent = this.value.length + " / 120"; });
selectDuree.addEventListener('change', () => { recalculerPrix(); validerFormulaire(); });

window.addEventListener('DOMContentLoaded', () => {
    rafraichirVoyants();
    validerFormulaire(); // Force l'évaluation au chargement initial pour désactiver le bouton proprement
});
</script>
