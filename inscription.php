<?php
// =============================================================================
// SCRIPT      : inscription.php
// REVISION    : 5.0 - Sécurité Accords Communautaires + Verrouillage Regex
// =============================================================================
session_start();
require_once 'config.php';

if (isset($_SESSION['id_utilisateur'])) {
    if (isset($_SESSION['type_compte']) && $_SESSION['type_compte'] === 'pro') {
        header('Location: espace_membre_pro.php');
    } else {
        header('Location: espace_membre.php');
    }
    exit();
}

$erreur = $_SESSION['erreur_inscription'] ?? '';
unset($_SESSION['erreur_inscription']);

$saisie = $_SESSION['form_inscription'] ?? [];

// Chargement des Villes
try {
    $stmt_villes = $bdd->query("SELECT id_ville, nom_ville FROM jevend_villes ORDER BY nom_ville ASC");
    $villes = $stmt_villes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $villes = [];
    $erreur = "Impossible de charger la liste des villes.";
}

// Chargement de la Charte de la Communauté (FAQ ID = 25)
try {
    $stmt_faq = $bdd->prepare("SELECT reponse FROM jevend_faq WHERE id = 25 AND actif = 1");
    $stmt_faq->execute();
    $faq_regles = $stmt_faq->fetch(PDO::FETCH_ASSOC);
    $texte_regles = $faq_regles ? $faq_regles['reponse'] : "Erreur : La charte de la communauté est momentanément indisponible.";
} catch (PDOException $e) {
    $texte_regles = "Erreur de chargement des règles.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Créer un compte — jevend.com</title>
    <link rel="stylesheet" href="style.css">
    <style>
        html, body { max-width: 100% !important; overflow-x: hidden !important; width: 100% !important; margin: 0; padding: 0; box-sizing: border-box; }
        @media (max-width: 768px) {
            .admin-conteneur { max-width: 100% !important; width: 100% !important; padding: 0 15px !important; margin-top: 20px !important; box-sizing: border-box !important; }
            .form-bloc { width: 100% !important; box-sizing: border-box !important; padding: 20px !important; }
        }
        .msg-erreur-champ {
            color: #dc2626;
            font-size: 0.78rem;
            font-weight: bold;
            margin-top: 4px;
            display: none;
        }
        .input-invalide {
            border-color: #ef4444 !important;
            background-color: #fef2f2 !important;
        }
        .btn-desactive {
            background-color: #cbd5e1 !important;
            cursor: not-allowed !important;
            opacity: 0.7;
        }
        .charte-box {
            font-size: 0.8rem; 
            color: #475569; 
            max-height: 150px; 
            overflow-y: auto; 
            padding: 12px; 
            background: #ffffff; 
            border: 1px solid #e2e8f0; 
            border-radius: 4px; 
            margin-bottom: 15px;
            line-height: 1.4;
        }
    </style>
</head>
<body class="admin-body">

    <?php include 'partials/_nav_publique.php'; ?>

    <div class="admin-conteneur" style="max-width: 500px; margin-top: 40px; margin-left: auto; margin-right: auto; box-sizing: border-box;">
        <div class="form-bloc" style="background: #ffffff; padding: 30px; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); box-sizing: border-box;">
            
            <div style="text-align: center; margin-bottom: 25px;">
                <h2 style="margin: 0 0 5px 0; color: #1e3a8a;">Rejoindre la communauté</h2>
                <p style="color: #64748b; font-size: 0.85rem; margin: 0;">Inscrivez-vous en quelques secondes sans vous soucier d'un mot de passe.</p>
            </div>

            <?php if (!empty($erreur)): ?>
                <div class="erreur-msg" style="background-color: #fef2f2; color: #991b1b; padding: 10px; border-radius: 4px; font-size: 0.85rem; margin-bottom: 15px; border: 1px solid #fecaca; font-weight: bold; text-align: center;">
                    ⚠️ <?= htmlspecialchars($erreur) ?>
                </div>
            <?php endif; ?>

            <form id="formInscription" action="inscription_execute.php" method="POST" novalidate>
                
                <!-- SÉLECTEUR TYPE DE COMPTE -->
                <div class="champ-groupe" style="margin-bottom: 20px;">
                    <label style="font-weight: bold; display: block; margin-bottom: 8px; color: #0f172a;">Type de compte :</label>
                    <div style="display: flex; gap: 15px; align-items: center; background: #f8fafc; padding: 12px; border-radius: 6px; border: 1px solid #cbd5e1;">
                        <label style="cursor: pointer; font-weight: bold; margin: 0; display: flex; align-items: center; gap: 6px; color: #334155;">
                            <input type="radio" name="type_compte" value="particulier" <?= (($saisie['type_compte'] ?? 'particulier') === 'particulier') ? 'checked' : '' ?> onclick="basculerChampsPro(false)"> 
                            👤 Particulier
                        </label>
                        <label style="cursor: pointer; font-weight: bold; margin: 0; display: flex; align-items: center; gap: 6px; color: #2563eb;">
                            <input type="radio" name="type_compte" value="pro" <?= (($saisie['type_compte'] ?? '') === 'pro') ? 'checked' : '' ?> onclick="basculerChampsPro(true)"> 
                            🏢 Commerce / Entreprise
                        </label>
                    </div>
                </div>

                <!-- BLOC DES CHAMPS PRO -->
                <div id="bloc-champs-pro" style="display: <?= (($saisie['type_compte'] ?? '') === 'pro') ? 'block' : 'none' ?>; background: #eff6ff; padding: 15px; border-radius: 6px; border: 1px solid #bfdbfe; margin-bottom: 20px; box-sizing: border-box;">
                    <h4 style="margin-top: 0; color: #1e40af; margin-bottom: 12px; font-size: 0.95rem;">🏢 Profil Commercial</h4>
                    
                    <div class="champ-groupe" style="margin-bottom: 12px;">
                        <label for="nom_entreprise" style="font-weight: bold; display: block; font-size: 0.85rem; color: #1e3a8a; margin-bottom: 4px;">Nom officiel de l'entreprise * :</label>
                        <input type="text" name="nom_entreprise" id="nom_entreprise" placeholder="Ex: Garage Auto Matane Inc." value="<?= htmlspecialchars($saisie['nom_entreprise'] ?? '') ?>" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #93c5fd; box-sizing: border-box;">
                        <div class="msg-erreur-champ" id="err_nom_entreprise">⚠️ Veuillez renseigner le nom officiel de votre entreprise.</div>
                    </div>

                    <div class="champ-groupe" style="margin-bottom: 12px;">
                        <label for="telephone_pro" style="font-weight: bold; display: block; font-size: 0.85rem; color: #1e3a8a; margin-bottom: 4px;">Téléphone commercial direct * :</label>
                        <input type="tel" name="telephone_pro" id="telephone_pro" placeholder="Ex: 418-555-0199" value="<?= htmlspecialchars($saisie['telephone_pro'] ?? '') ?>" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #93c5fd; box-sizing: border-box;">
                        <div class="msg-erreur-champ" id="err_telephone_pro">⚠️ Veuillez entrer un numéro de téléphone commercial direct.</div>
                    </div>

                    <div class="champ-groupe" style="margin-bottom: 12px;">
                        <label for="adresse_pro" style="font-weight: bold; display: block; font-size: 0.85rem; color: #1e3a8a; margin-bottom: 4px;">Adresse commerciale * :</label>
                        <input type="text" name="adresse_pro" id="adresse_pro" placeholder="Ex: 123 Avenue de la Phare, Matane" value="<?= htmlspecialchars($saisie['adresse_pro'] ?? '') ?>" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #93c5fd; box-sizing: border-box;">
                        <div class="msg-erreur-champ" id="err_adresse_pro">⚠️ L'adresse commerciale est obligatoire pour un compte Pro.</div>
                    </div>

                    <div class="champ-groupe">
                        <label for="site_web" style="font-weight: bold; display: block; font-size: 0.85rem; color: #1e3a8a; margin-bottom: 4px;">Site Web de l'entreprise (Facultatif) :</label>
                        <input type="url" name="site_web" id="site_web" placeholder="Ex: https://mon-garage.com" value="<?= htmlspecialchars($saisie['site_web'] ?? '') ?>" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #93c5fd; box-sizing: border-box;">
                    </div>
                </div>

                <!-- CHAMPS COMMUNS OBLIGATOIRES -->
                <div class="champ-groupe" style="margin-bottom: 15px;">
                    <label for="nom">Nom du responsable du compte * :</label>
                    <input type="text" name="nom" id="nom" placeholder="Ex: Jean Tremblay" value="<?= htmlspecialchars($saisie['nom'] ?? '') ?>" style="width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #cbd5e1; border-radius: 4px;">
                    <div class="msg-erreur-champ" id="err_nom">⚠️ Votre nom doit contenir un prénom et un nom (maximum 2 mots).</div>
                </div>

                <div class="champ-groupe" style="margin-bottom: 15px;">
                    <label for="courriel">Votre adresse courriel * :</label>
                    <input type="email" name="courriel" id="courriel" placeholder="Ex: jean.tremblay@gmail.com" value="<?= htmlspecialchars($saisie['courriel'] ?? '') ?>" style="width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #cbd5e1; border-radius: 4px;">
                    <div class="msg-erreur-champ" id="err_courriel">⚠️ Une adresse courriel valide est requise pour recevoir votre code.</div>
                </div>

                <div class="champ-groupe" style="margin-bottom: 15px;">
                    <label for="cellulaire">Numéro de cellulaire * :</label>
                    <input type="tel" name="cellulaire" id="cellulaire" placeholder="Ex: 418-555-1234" value="<?= htmlspecialchars($saisie['cellulaire'] ?? '') ?>" maxlength="12" style="width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #cbd5e1; border-radius: 4px;">
                    <div class="msg-erreur-champ" id="err_cellulaire">⚠️ Format exigé : XXX-XXX-XXXX (Numéro canadien).</div>
                </div>

                <div class="champ-groupe" style="margin-bottom: 20px;">
                    <label for="id_ville">Votre ville de résidence / commerce * :</label>
                    <select name="id_ville" id="id_ville" style="width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #cbd5e1; border-radius: 4px;">
                        <option value="">-- Sélectionnez votre ville --</option>
                        <?php foreach ($villes as $v): ?>
                            <option value="<?= $v['id_ville'] ?>" <?= (($saisie['id_ville'] ?? 0) == $v['id_ville']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($v['nom_ville']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="msg-erreur-champ" id="err_id_ville">⚠️ La sélection d'une municipalité est obligatoire.</div>
                </div>

                <!-- ACCORD DES RÈGLES (FAQ 25) -->
                <div class="champ-groupe" style="margin-bottom: 20px; background: #f8fafc; padding: 15px; border-radius: 6px; border: 1px solid #cbd5e1;">
                    <h4 style="margin: 0 0 10px 0; font-size: 0.95rem; color: #0f172a;">📜 Charte de la communauté</h4>
                    <div class="charte-box">
                        <?= $texte_regles ?>
                    </div>
                    <label style="cursor: pointer; display: flex; align-items: flex-start; gap: 10px; font-weight: bold; font-size: 0.9rem; color: #1e3a8a;">
                        <input type="checkbox" name="accord_regles" id="accord_regles" <?= isset($saisie['accord_regles']) ? 'checked' : '' ?> style="margin-top: 3px; transform: scale(1.2);">
                        J'ai lu, compris et j'accepte la Charte de la communauté ainsi que les règles de proximité.
                    </label>
                    <div class="msg-erreur-champ" id="err_accord_regles">⚠️ Vous devez accepter la charte pour vous inscrire.</div>
                </div>

                <button type="submit" id="btnSubmit" class="btn-action btn-desactive" disabled style="width: 100%; font-weight: bold; padding: 12px; margin-top: 10px; background-color: #2563eb; color: #ffffff; border: none; border-radius: 4px; transition: all 0.2s;">
                    🎯 Créer mon compte
                </button>
            </form>

            <div style="text-align: center; margin-top: 25px; padding-top: 15px; border-top: 1px solid #f1f5f9; font-size: 0.85rem; color: #64748b;">
                Déjà inscrit sur jevend ? <a href="connexion.php" style="color: #2563eb; font-weight: bold; text-decoration: none;">Me connecter</a>
            </div>

        </div>
    </div>

    <script>
    const form = document.getElementById('formInscription');
    const btnSubmit = document.getElementById('btnSubmit');

    const inputsObligatoiresBase = ['nom', 'courriel', 'cellulaire', 'id_ville'];
    const inputsObligatoiresPro  = ['nom_entreprise', 'telephone_pro', 'adresse_pro'];

    // Formatage dynamique du cellulaire (Ajout automatique des tirets)
    const champCellulaire = document.getElementById('cellulaire');
    if(champCellulaire) {
        champCellulaire.addEventListener('input', function (e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
            e.target.value = !x[2] ? x[1] : x[1] + '-' + x[2] + (x[3] ? '-' + x[3] : '');
        });
    }

    function estPro() {
        return document.querySelector('input[name="type_compte"]:checked').value === 'pro';
    }

    function basculerChampsPro(estMembrePro) {
        const blocPro = document.getElementById('bloc-champs-pro');
        blocPro.style.display = estMembrePro ? 'block' : 'none';
        verifierFormulaireComplet();
    }

    function validerChamp(id) {
        const el = document.getElementById(id);
        const err = document.getElementById('err_' + id);
        if (!el) return true;

        let valide = true;
        const val = el.type === 'checkbox' ? el.checked : el.value.trim();

        if (id === 'courriel') {
            valide = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
        } else if (id === 'id_ville') {
            valide = parseInt(val) > 0;
        } else if (id === 'nom') {
            // Validation: Max 2 mots, non vide
            let mots = val.split(/\s+/);
            valide = (val.length > 0 && mots.length <= 2);
        } else if (id === 'cellulaire') {
            // Validation: Format exact XXX-XXX-XXXX
            valide = /^\d{3}-\d{3}-\d{4}$/.test(val);
        } else if (id === 'accord_regles') {
            valide = el.checked;
        } else {
            valide = (val !== false && val.toString().length > 0);
        }

        if (!valide) {
            el.classList.add('input-invalide');
            if (err) err.style.display = 'block';
        } else {
            el.classList.remove('input-invalide');
            if (err) err.style.display = 'none';
        }

        return valide;
    }

    function verifierFormulaireComplet() {
        let formulaireValide = true;

        // Validation des champs communs
        for (const id of inputsObligatoiresBase) {
            const el = document.getElementById(id);
            const val = el ? el.value.trim() : '';

            if (id === 'courriel') {
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) formulaireValide = false;
            } else if (id === 'id_ville') {
                if (parseInt(val) <= 0 || isNaN(parseInt(val))) formulaireValide = false;
            } else if (id === 'nom') {
                let mots = val.split(/\s+/);
                if (val.length === 0 || mots.length > 2) formulaireValide = false;
            } else if (id === 'cellulaire') {
                if (!/^\d{3}-\d{3}-\d{4}$/.test(val)) formulaireValide = false;
            } else {
                if (val.length === 0) formulaireValide = false;
            }
        }

        // Vérification de la case à cocher
        const chkRegles = document.getElementById('accord_regles');
        if (!chkRegles || !chkRegles.checked) {
            formulaireValide = false;
        }

        // Validation des champs Pro si mode Pro actif
        if (estPro()) {
            for (const id of inputsObligatoiresPro) {
                const el = document.getElementById(id);
                if (!el || el.value.trim().length === 0) {
                    formulaireValide = false;
                }
            }
        }

        // Gestion de l'état du bouton
        if (formulaireValide) {
            btnSubmit.disabled = false;
            btnSubmit.classList.remove('btn-desactive');
            btnSubmit.style.cursor = 'pointer';
        } else {
            btnSubmit.disabled = true;
            btnSubmit.classList.add('btn-desactive');
            btnSubmit.style.cursor = 'not-allowed';
        }
    }

    // Attacher les événements dynamiques
    const tousChamps = [...inputsObligatoiresBase, ...inputsObligatoiresPro, 'site_web', 'accord_regles'];
    tousChamps.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            if (el.type === 'checkbox' || el.type === 'radio') {
                el.addEventListener('change', () => {
                    validerChamp(id);
                    verifierFormulaireComplet();
                });
            } else {
                el.addEventListener('blur', () => {
                    validerChamp(id);
                    verifierFormulaireComplet();
                });
                el.addEventListener('input', () => {
                    verifierFormulaireComplet();
                });
            }
        }
    });

    // Évaluation initiale au chargement
    verifierFormulaireComplet();
    </script>
<?php 
if (file_exists('partials/_barre_flottante.php')) {
    include 'partials/_barre_flottante.php';
}
?>
</body>
</html>
