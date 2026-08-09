<?php
// =============================================================================
// NOM DU SCRIPT : espace_membre.php
// REVISION     : 3.9 - Intégration du contrôle de quota global des annonces (RPM)
// DESCRIPTION  : Espace membre avec vérification et affichage dynamique du quota global.
// =============================================================================
session_start();

if (!isset($_SESSION['id_utilisateur'])) { 
    header('Location: connexion.php'); 
    exit(); 
}

require_once 'config.php';
require_once 'fonctions_geoloc.php';
require_once 'partials/_nombre_annonces.php'; // <-- INCLUSION DU CALCUL DE QUOTA GLOBAL

$id_utilisateur = $_SESSION['id_utilisateur'];
$nom_membre = $_SESSION['nom'];
$erreur = "";

$id_ville_acheteur = null;
$description_magasin = "";

try {
    $stmt_acheteur = $bdd->prepare("SELECT id_ville, description_magasin FROM jevend_utilisateurs WHERE id_utilisateur = ?");
    $stmt_acheteur->execute([$id_utilisateur]);
    $user_data = $stmt_acheteur->fetch(PDO::FETCH_ASSOC);
    if ($user_data) {
        $id_ville_acheteur = $user_data['id_ville'];
        $description_magasin = $user_data['description_magasin'] ?? "";
    }
} catch (PDOException $e) { }

try {
    // 1. Extraction de tes propres annonces avec nombre de prospects en Liste d'Envie
    $annonces = $bdd->prepare("
        SELECT a.*, c.nom_fr AS categorie_nom,
               (SELECT COUNT(*) FROM jevend_listes_envie WHERE id_annonce = a.id_annonces) AS nb_prospects
        FROM jevend_annonces a 
        JOIN jevend_categories c ON a.id_categorie = c.id_categorie 
        WHERE a.id_utilisateur = ? 
        ORDER BY a.date_creation DESC
    ");
    $annonces->execute([$id_utilisateur]);
    $liste_annonces = $annonces->fetchAll(PDO::FETCH_ASSOC);

    // 2. EXTRACTION ENRICHIE (Liste d'envie de l'utilisateur connecté)
    $stmt_favoris = $bdd->prepare("
        SELECT a.*, c.nom_fr AS categorie_nom, 
               u.nom AS vendeur_nom, u.cellulaire AS vendeur_tel, u.id_ville AS vendeur_ville_id, v.nom_ville AS vendeur_ville_nom,
               (SELECT COUNT(*) FROM jevend_listes_envie WHERE id_annonce = a.id_annonces) AS nb_envies
        FROM jevend_listes_envie f
        LEFT JOIN jevend_annonces a ON f.id_annonce = a.id_annonces
        LEFT JOIN jevend_categories c ON a.id_categorie = c.id_categorie
        LEFT JOIN jevend_utilisateurs u ON a.id_utilisateur = u.id_utilisateur
        LEFT JOIN jevend_villes v ON u.id_ville = v.id_ville
        WHERE f.id_utilisateur = ?
        ORDER BY f.date_ajout DESC
    ");
    $stmt_favoris->execute([$id_utilisateur]);
    $liste_favoris = $stmt_favoris->fetchAll(PDO::FETCH_ASSOC);

    // 3. Extraction des annonces ÉLIGIBLES pour une nouvelle bannière
    $sql_eligibles = "
        SELECT a.id_annonces, a.titre_objet_nettoye, a.prix 
        FROM jevend_annonces a
        LEFT JOIN jevend_bannieres_actives b ON a.id_annonces = b.id_annonce
        WHERE a.id_utilisateur = ? 
          AND a.statut = 'actif' 
          AND a.statut_vente != 'vendu'
          AND b.id_annonce IS NULL
        ORDER BY a.id_annonces DESC
    ";
    $stmt_eligibles = $bdd->prepare($sql_eligibles);
    $stmt_eligibles->execute([$id_utilisateur]);
    $annonces_eligibles = $stmt_eligibles->fetchAll();

    // 4. Extraction de l'historique d'achat à vie
    $stmt_achats = $bdd->prepare("
        SELECT * FROM jevend_preuve_dachat 
        WHERE id_utilisateur = ? AND (type_client = 'regulier' OR type_client IS NULL) 
        ORDER BY id_preuve DESC
    ");
    $stmt_achats->execute([$id_utilisateur]);
    $historique_achats = $stmt_achats->fetchAll(PDO::FETCH_ASSOC);

    // 5. Extraction de l'état en direct des bannières
    $stmt_bannieres_actives = $bdd->prepare("
        SELECT b.*, a.titre_objet_nettoye 
        FROM jevend_bannieres_actives b
        LEFT JOIN jevend_annonces a ON b.id_annonce = a.id_annonces
        WHERE b.id_utilisateur = ? 
        ORDER BY b.date_enregistrement ASC
    ");
    $stmt_bannieres_actives->execute([$id_utilisateur]);
    $bannieres_du_membre = $stmt_bannieres_actives->fetchAll();

    // 6. Extraction dynamique des tarifs
    $stmt_tarifs = $bdd->query("SELECT * FROM jevend_tarifs_publicites");
    $tarifs_bruts = $stmt_tarifs->fetchAll(PDO::FETCH_ASSOC);
    
    $tarifs = [];
    foreach ($tarifs_bruts as $t) {
        $tarifs[$t['type_produit']] = [
            'prix' => (float)$t['prix_par_jour'],
            'min'  => (int)$t['duree_min_jours']
        ];
    }

} catch (PDOException $e) {
    $erreur = "Erreur SQL : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Espace - jevend.com</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="style_membre.css?v=1.1">
</head>
<body class="admin-body">
<?php include 'partials/_nav_membre.php'; ?>
    <div class="admin-conteneur">

        <?php if (!empty($erreur)): ?>
            <div class="erreur-msg"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <div class="barre-outils-membre">
            <div class="onglets-navigation">
                <button class="onglet-btn actif" onclick="changerOnglet('vitrines')">📋 Mes Vitrines</button>
                <button class="onglet-btn" onclick="changerOnglet('bon-plan')">🚀 Bon Plan de Vente</button>
                <button class="onglet-btn" onclick="changerOnglet('magasin')">🏪 Mon Magasin</button>
            </div>
            <a href="index.php" class="btn-retour-fil">🌐 Fil d'actualité public</a>
        </div>

        <!-- ONGLET 1 : MES VITRINES -->
        <div id="onglet-vitrines" class="contenu-onglet actif">
            <h2>Gestion de vos vitrines</h2>

            <!-- VÉRIFICATION DU QUOTA GLOBAL RPM : ALERTE ET BLOCAGE PRÉVENTIF -->
            <?php if (isset($quota_annonces_atteint) && $quota_annonces_atteint): ?>
                <div style="background-color: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 18px; border-radius: 8px; margin-bottom: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
                    <div style="font-weight: 900; font-size: 1.05rem; display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                        <span>⚠️</span> Capacité maximale du réseau atteinte (<?= $total_annonces_reseau ?> / <?= $limite_globale_annonces ?> annonces)
                    </div>
                    <div style="font-size: 0.9rem; line-height: 1.4; color: #7f1d1d;">
                        Le quota global des annonces fixé par l'administration est actuellement atteint. La création de nouvelles vitrines est temporairement suspendue. Veuillez s'il vous plaît revenir plus tard si vous souhaitez en ajouter une nouvelle.
                    </div>
                </div>

                <div style="background-color: #f8fafc; border: 1px dashed #cbd5e1; padding: 15px; border-radius: 6px; text-align: center; color: #64748b; margin-bottom: 25px; font-weight: bold; font-size: 0.9rem;">
                    🔒 Bouton d'ajout d'annonce temporairement désactivé par mesure de régulation.
                </div>
            <?php else: ?>
                <!-- BOUTON D'AJOUT CLASSIQUE (Affiché uniquement si le quota n'est pas atteint) -->
                <div style="margin-bottom: 25px;">
                    <a href="creer_annonce.php" class="btn-action" style="display: inline-block; background-color: #2563eb; color: #fff; text-decoration: none; padding: 12px 20px; border-radius: 6px; font-weight: bold; font-size: 0.9rem;">
                        ✨ + Créer une nouvelle annonce
                    </a>
                </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                <?php if (count($liste_annonces) > 0): ?>
                    <?php foreach ($liste_annonces as $annonce): ?>
                        <?php include 'partials/_card.php'; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="admin-bloc-vide">Vous n'avez aucune annonce en ligne actuellement.</div>
                <?php endif; ?>
            </div>
            
            <?php include 'partials/_liste_envie.php'; ?>
        </div>

        <!-- ONGLET 2 : BON PLAN DE VENTE -->
        <div id="onglet-bon-plan" class="contenu-onglet">
            <?php include 'partials/_bon_plan_vente.php'; ?>
        </div>

        <!-- ONGLET 3 : MON MAGASIN -->
        <div id="onglet-magasin" class="contenu-onglet">
            
            <div class="zone-description-magasin">
                <label class="case-bascule-container">
                    <input type="checkbox" id="chk-activer-description" onchange="basculerFormulaireDescription()" <?= !empty($description_magasin) ? 'checked' : '' ?>>
                    ✍️ Personnaliser l'accueil de votre boutique (Mot de bienvenue / Promotions)
                </label>
                
                <div class="bloc-form-description" id="bloc-form-description" style="<?= !empty($description_magasin) ? 'display: block;' : '' ?>">
                    <p style="color: #64748b; font-size: 0.85rem; margin-top: 0; margin-bottom: 12px;">
                        Ce texte sera affiché tout en haut de votre boutique publique pour accueillir vos clients et mettre en valeur vos services.
                    </p>
                    <textarea id="txt-description-magasin" style="width: 100%; height: 100px; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit; font-size: 0.9rem; box-sizing: border-box; resize: vertical;" placeholder="Ex: Bienvenue dans mon magasin d'instruments de musique d'occasion ! Retrouvez nos nouveautés chaque semaine..."><?= htmlspecialchars($description_magasin) ?></textarea>
                    
                    <div style="display: flex; justify-content: flex-end; margin-top: 10px; gap: 10px; align-items: center;">
                        <span id="statut-sauvegarde-desc" style="font-size: 0.85rem; font-weight: bold; transition: color 0.2s;"></span>
                        <button onclick="sauvegarderDescriptionBoutique()" class="btn-action" style="margin: 0; padding: 8px 16px; width: auto; font-size: 0.85rem;">💾 Enregistrer la présentation</button>
                    </div>
                </div>
            </div>

            <div class="zone-campagnes-pub">
                <h3 style="margin-top:0; display:flex; align-items:center; gap:8px; color:#1e3a8a;"><span style="color:#10b981;">🟢</span> Vos campagnes publicitaires en direct</h3>
                
                <?php if (count($bannieres_du_membre) > 0): ?>
                    <div style="overflow-x: auto; width: 100%;">
                        <table class="table-pub">
                            <thead>
                                <tr>
                                    <th>Annonce liée</th>
                                    <th>Slogan de la campagne</th>
                                    <th>Type</th>
                                    <th>Statut</th>
                                    <th>Échéance & Restant</th>
                                    <th style="text-align: center; width: 130px;">Performances</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bannieres_du_membre as $campagne): ?>
                                    <?php
                                        $duree_jours = (int)($campagne['duree_jours'] ?? 0);
                                        $date_ref = !empty($campagne['date_debut_activation']) ? $campagne['date_debut_activation'] : ($campagne['date_enregistrement'] ?? null);
                                        $affichage_temps = "<span style='color:#94a3b8;'>Non spécifié</span>";
                                        
                                        if ($date_ref && $duree_jours > 0) {
                                            $dt_debut = new DateTime($date_ref);
                                            $dt_fin = (clone $dt_debut)->modify("+" . $duree_jours . " days");
                                            $dt_actuel = new DateTime();
                                            
                                            if ($dt_actuel > $dt_fin) {
                                                $affichage_temps = "<span style='color:#dc2626; font-weight:bold;'>Expiré</span>";
                                            } else {
                                                $diff_jours = $dt_actuel->diff($dt_fin)->days;
                                                
                                                if ($campagne['statut_affichage'] === 'en_attente' && empty($campagne['date_debut_activation'])) {
                                                    $affichage_temps = "<strong>" . $duree_jours . " jours</strong><br><small style='color:#b45309; font-weight:bold;'>⏳ En attente d'activation</small>";
                                                } else {
                                                    $affichage_temps = "<strong>" . $dt_fin->format('Y-m-d') . "</strong><br><small style='color:#2563eb; font-weight:bold;'>⏳ " . $diff_jours . " jour(s) restant(s)</small>";
                                                }
                                            }
                                        }
                                    ?>
                                    <tr>
                                        <td style="font-weight: 500;">
                                            <?= !empty($campagne['titre_objet_nettoye']) ? htmlspecialchars($campagne['titre_objet_nettoye']) : '<span style="color:#94a3b8; font-style:italic;">Aucune (Lien Boutique)</span>' ?>
                                        </td>
                                        <td style="font-style: italic; color:#475569;">"<?= htmlspecialchars($campagne['texte_banniere']) ?>"</td>
                                        <td style="font-weight: bold; color:#1e3a8a; text-transform:uppercase; font-size:0.8rem;"><?= htmlspecialchars($campagne['type_banniere'] ?? 'Régulière') ?></td>
                                        <td>
                                            <?php if ($campagne['statut_affichage'] === 'active'): ?>
                                                <span style="background-color: #e6f4ea; color: #15803d; padding: 4px 10px; border-radius: 12px; font-weight: bold; font-size: 0.8rem; border: 1px solid #bbf7d0; white-space: nowrap;">🟢 En circulation</span>
                                            <?php else: ?>
                                                <span style="background-color: #fef3c7; color: #b45309; padding: 4px 10px; border-radius: 12px; font-weight: bold; font-size: 0.8rem; border: 1px solid #fde68a; white-space: nowrap;">⏳ En attente</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= $affichage_temps ?>
                                        </td>
                                        <td style="text-align: center; white-space: nowrap;">
                                            <span class="badge-perf badge-vue" title="Nombre d'affichages sur l'index">
                                                👁️ <?= (int)($campagne['nb_vues'] ?? 0) ?>
                                            </span>
                                            <span class="badge-perf badge-clic" title="Nombre de clics enregistrés">
                                                🎯 <?= (int)($campagne['nb_clics'] ?? 0) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="background-color:#f8fafc; border:1px dashed #cbd5e1; padding:20px; border-radius:6px; text-align:center; color:#64748b; font-style:italic;">
                        Vous n'avez aucune campagne publicitaire active ou en attente pour le moment.
                    </div>
                <?php endif; ?>
            </div>

            <!-- INCLUSION DU MODULE D'ACHAT DE BANNIÈRE RÉGULIÈRE -->
            <div style="margin-top: 30px; width: 100%;">
                <?php include 'partials/_membre-banniere.php'; ?>
            </div>

            <!-- ACCORDÉON COMPTABILITÉ UNIQUE -->
            <details class="accordeon-recus">
                <summary class="accordeon-header">
                    <span>🧾 Historique des reçus & factures (<?= count($historique_achats) ?>)</span>
                    <span class="accordeon-icon">+</span>
                </summary>
                <div class="accordeon-contenu">
                    <?php if (count($historique_achats) > 0): ?>
                        <div style="overflow-x: auto; width: 100%;">
                            <table class="table-pub">
                                <thead>
                                    <tr>
                                        <th>N° Transaction</th>
                                        <th>Date d'achat</th>
                                        <th>Description du service</th>
                                        <th>Montant payé</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historique_achats as $achat): ?>
                                        <?php 
                                            $date_recu = !empty($achat['date_achat']) ? date('Y-m-d H:i', strtotime($achat['date_achat'])) : date('Y-m-d H:i');
                                            $ref_recu = !empty($achat['no_transaction']) ? htmlspecialchars($achat['no_transaction']) : ("#REG-" . str_pad((string)$achat['id_preuve'], 5, '0', STR_PAD_LEFT));
                                            $desc_txt = !empty($achat['description_achat']) ? htmlspecialchars($achat['description_achat']) : 'Campagne publicitaire';
                                            $prix_txt = number_format((float)($achat['prix_paye'] ?? 0), 2, ',', ' ') . " $ CAD";
                                            $statut_txt = htmlspecialchars($achat['statut_paiement'] ?? 'Payé');
                                        ?>
                                        <tr>
                                            <td style="font-weight: bold; color: #1e3a8a; font-family: monospace;">
                                                <?= $ref_recu ?>
                                            </td>
                                            <td><?= $date_recu ?></td>
                                            <td><?= $desc_txt ?></td>
                                            <td style="font-weight: bold; color: #16a34a;">
                                                <?= $prix_txt ?>
                                            </td>
                                            <td>
                                                <span style="background-color: #dcfce7; color: #15803d; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 0.8rem; border: 1px solid #bbf7d0;">
                                                    <?= $statut_txt ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="padding: 10px; color: #64748b; font-style: italic; text-align: center;">
                            Aucun reçu ou facture disponible dans votre historique pour le moment.
                        </div>
                    <?php endif; ?>
                </div>
            </details>

        </div>
    </div>

    <script>
    function changerOnglet(nomOnglet) {
        document.querySelectorAll('.onglet-btn').forEach(btn => btn.classList.remove('actif'));
        document.querySelectorAll('.contenu-onglet').forEach(content => content.classList.remove('actif'));

        if (nomOnglet === 'vitrines') {
            document.querySelector('.onglets-navigation button:nth-child(1)').classList.add('actif');
            document.getElementById('onglet-vitrines').classList.add('actif');
        } else if (nomOnglet === 'bon-plan') {
            document.querySelector('.onglets-navigation button:nth-child(2)').classList.add('actif');
            document.getElementById('onglet-bon-plan').classList.add('actif');
        } else if (nomOnglet === 'magasin') {
            document.querySelector('.onglets-navigation button:nth-child(3)').classList.add('actif');
            document.getElementById('onglet-magasin').classList.add('actif');
        }
    }

    function marquerObjetVendu(idAnnonce, boutonElement) {
        if (confirm("Voulez-vous vraiment déclarer cet objet comme vendu ? Cette action coupera définitivement les appels et messages sur votre cellulaire pour cette vitrine.")) {
            const donnees = new FormData();
            donnees.append('id_annonce', idAnnonce);

            fetch('marquer_vendu.php', {
                method: 'POST',
                body: donnees
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'succes') {
                    const containerAction = boutonElement.parentNode;
                    containerAction.innerHTML = '<div style="background-color: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; text-align: center; padding: 8px; border-radius: 4px; font-weight: bold; font-size: 0.85rem;">🟥 Objet marqué comme VENDU</div>';
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error('Erreur lors de la bascule de statut :', error));
        }
    }

    function basculerFormulaireDescription() {
        const checkbox = document.getElementById('chk-activer-description');
        const blocForm = document.getElementById('bloc-form-description');
        
        if (checkbox.checked) {
            blocForm.style.display = 'block';
        } else {
            if (confirm("En décochant cette case, votre mot de bienvenue sera masqué de votre boutique publique. Voulez-vous également vider le texte enregistré ?")) {
                document.getElementById('txt-description-magasin').value = "";
                sauvegarderDescriptionBoutique(true);
            }
            blocForm.style.display = 'none';
        }
    }

    function sauvegarderDescriptionBoutique(silencieux = false) {
        const texte = document.getElementById('txt-description-magasin').value;
        const statutLabel = document.getElementById('statut-sauvegarde-desc');
        
        if (!silencieux) {
            statutLabel.style.color = '#1e3a8a';
            statutLabel.textContent = "⏳ Sauvegarde en cours...";
        }

        const donnees = new FormData();
        donnees.append('description_magasin', texte);

        fetch('sauvegarder_description.php', {
            method: 'POST',
            body: donnees
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'succes') {
                if (!silencieux) {
                    statutLabel.style.color = '#16a34a';
                    statutLabel.textContent = "✅ Enregistré !";
                    setTimeout(() => { statutLabel.textContent = ""; }, 3000);
                }
            } else {
                statutLabel.style.color = '#dc2626';
                statutLabel.textContent = "❌ " + data.message;
            }
        })
        .catch(error => {
            console.error('Erreur de sauvegarde de description :', error);
            statutLabel.style.color = '#dc2626';
            statutLabel.textContent = "❌ Erreur de connexion";
        });
    }

    // --- FONCTION DE SOUMISSION DU PLAN DE VENTE ---
    function executerPlanDeVente(idAnnonce, action) {
        const msgStatut = document.getElementById('msg-plan-' + idAnnonce);
        const inputPrix = document.getElementById('prix-promo-' + idAnnonce);
        const selectDuree = document.getElementById('duree-promo-' + idAnnonce);

        const donnees = new FormData();
        donnees.append('id_annonce', idAnnonce);
        donnees.append('action', action);

        if (action === 'activer') {
            if (!inputPrix || !inputPrix.value || parseFloat(inputPrix.value) <= 0) {
                alert("Veuillez saisir un Prix Spécial valide.");
                return;
            }
            donnees.append('prix_promo', inputPrix.value);
            donnees.append('duree_heures', selectDuree.value);
        }

        if (msgStatut) {
            msgStatut.style.color = '#2563eb';
            msgStatut.textContent = "⏳ Traitement en cours...";
        }

        fetch('sauvegarder_plan_vente.php', {
            method: 'POST',
            body: donnees
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'succes') {
                alert(data.message);
                location.reload(); // Recharger pour afficher les nouveaux états
            } else {
                alert("❌ Erreur : " + data.message);
                if (msgStatut) msgStatut.textContent = "";
            }
        })
        .catch(err => {
            console.error(err);
            alert("❌ Erreur de réseau.");
            if (msgStatut) msgStatut.textContent = "";
        });
    }
    </script>
</body>
</html>
