<?php
// =============================================================================
// NOM DU SCRIPT : admin_modules/_admin_sponsorise.php
// REVISION : 1.1 - Gestion unifiée des clients et contrats publicitaires
// =============================================================================

$message_admin = "";
$type_message = "";



// =============================================================================
// AJOUT DE LA FONCTION INTELLIGENTE (À mettre tout en haut)
// =============================================================================
function getBaseUrl() {
    $protocole = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $hote = $_SERVER['HTTP_HOST'];
    // Calcule la racine proprement, même si on est dans un sous-dossier
    $dossier_script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $dossier_racine = str_replace('/admin_modules', '', $dossier_script);
    $dossier_racine = rtrim($dossier_racine, '/');
    return $protocole . $hote . $dossier_racine;
}
// =============================================================================



// 1. TRAITEMENT DU FORMULAIRE (AJOUT DE PUB ET/OU DE CLIENT)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_ajouter_sponsor'])) {
    $choix_client   = $_POST['choix_client'] ?? 'nouveau';
    $message_pub    = trim($_POST['message_pub'] ?? '');
    $url_redirection= trim($_POST['url_redirection'] ?? '');
    $fond_couleur   = $_POST['fond_couleur'] ?? 'rouge';
    $couleur_police = $_POST['couleur_police'] ?? 'blanc';
    $nb_semaines    = max(1, (int)($_POST['nb_semaines'] ?? 1));

    if (empty($message_pub)) {
        $message_admin = "Le message publicitaire est obligatoire.";
        $type_message = "erreur";
    } else {
        try {
            $bdd->beginTransaction();

            // Gestion du client : soit on prend un existant, soit on en crée un nouveau
            if ($choix_client === 'nouveau') {
                $nom_prenom = trim($_POST['nouveau_nom'] ?? '');
                $site_web   = trim($_POST['nouveau_site'] ?? '');
                $cel        = trim($_POST['nouveau_cel'] ?? '');
                $tel        = trim($_POST['nouveau_tel'] ?? '');

                if (empty($nom_prenom)) {
                    throw new Exception("Le nom du nouveau client est obligatoire.");
                }

                $stmt_client = $bdd->prepare("
                    INSERT INTO jevend_sponsorise_client (nom_prenom, site_web, cel, tel) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt_client->execute([$nom_prenom, $site_web, $cel, $tel]);
                $id_client = $bdd->lastInsertId();
            } else {
                $id_client = (int)$choix_client;
                if ($id_client <= 0) {
                    throw new Exception("Veuillez sélectionner un client valide.");
                }
            }

            // Calcul du montant (35$ par semaine fixe)
            $tarif_semaine = 35.00;
            $montant_total = $nb_semaines * $tarif_semaine;

            // Dates de début et de fin
            $date_debut = date('Y-m-d H:i:s');
            $date_fin   = date('Y-m-d H:i:s', strtotime("+$nb_semaines weeks"));

            // Token unique sécurisé pour la page client
            $token_paiement = bin2hex(random_bytes(16));

            // Insertion du bandeau
            $stmt_bandeau = $bdd->prepare("
                INSERT INTO jevend_bandeau_sponsorise 
                (id_client, message, url_redirection, fond_couleur, couleur_police, montant_paye, date_debut, date_fin, token_paiement, statut)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_attente_paiement')
            ");
            $stmt_bandeau->execute([
                $id_client,
                $message_pub,
                $url_redirection,
                $fond_couleur,
                $couleur_police,
                $montant_total,
                $date_debut,
                $date_fin,
                $token_paiement
            ]);

            $bdd->commit();
            $message_admin = "Contrat publicitaire créé avec succès ! Le lien de paiement unique a été généré.";
            $type_message = "succes";

        } catch (Exception $e) {
            $bdd->rollBack();
            $message_admin = "Erreur : " . $e->getMessage();
            $type_message = "erreur";
        }
    }
}

// 2. SUPPRESSION D'UN BANDEAU
if (isset($_GET['action_supprimer']) && (int)$_GET['action_supprimer'] > 0) {
    $id_del = (int)$_GET['action_supprimer'];
    try {
        $stmt_del = $bdd->prepare("DELETE FROM jevend_bandeau_sponsorise WHERE id_bandeau = ?");
        $stmt_del->execute([$id_del]);
        $message_admin = "Annonce sponsorisée supprimée avec succès.";
        $type_message = "succes";
    } catch (Exception $e) {
        $message_admin = "Erreur lors de la suppression.";
        $type_message = "erreur";
    }
}

// Récupération de la liste des clients pour le menu déroulant
$stmt_c_list = $bdd->query("SELECT * FROM jevend_sponsorise_client ORDER BY nom_prenom ASC");
$tous_les_clients = $stmt_c_list->fetchAll(PDO::FETCH_ASSOC);


// recuperation des prix

// Récupérer les prix actifs pour le menu déroulant du formulaire
$stmt_forfaits = $bdd->query("SELECT * FROM jevend_bandeau_sponsorise_prix WHERE actif = 1 ORDER BY id_prix ASC");
$les_forfaits = $stmt_forfaits->fetchAll(PDO::FETCH_ASSOC);

// Récupération de la liste des bandeaux avec leurs clients
$stmt_liste = $bdd->query("
    SELECT b.*, c.nom_prenom, c.cel, c.site_web 
    FROM jevend_bandeau_sponsorise b
    JOIN jevend_sponsorise_client c ON b.id_client = c.id_client
    ORDER BY b.id_bandeau DESC
");
$liste_sponsors = $stmt_liste->fetchAll(PDO::FETCH_ASSOC);
?>

<div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 25px; margin-top: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
    
    <h3 style="margin-top: 0; color: #0f172a; font-size: 1.3rem; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;">
        📢 Gestion des Bandeaux Publicitaires Sponsorisés (35 $ / semaine)
    </h3>

    <?php if (!empty($message_admin)): ?>
        <div style="padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; text-align: center; background: <?= ($type_message === 'succes') ? '#f0fdf4; color: #166534; border: 1px solid #bbf7d0;' : '#fef2f2; color: #991b1b; border: 1px solid #fecaca;' ?>">
            <?= htmlspecialchars($message_admin) ?>
        </div>
    <?php endif; ?>

    <!-- FORMULAIRE DE CRÉATION -->
    <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
        <h4 style="margin-top: 0; color: #1e293b; font-size: 1.05rem;">➕ Créer un nouveau contrat publicitaire</h4>
        
        <form action="" method="POST">
            <input type="hidden" name="action_ajouter_sponsor" value="1">

            <!-- SÉLECTION OU CRÉATION DU CLIENT -->
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: bold; font-size: 0.85rem; margin-bottom: 5px; color: #334155;">Client / Annonceur : *</label>
                <select name="choix_client" id="choix_client" onchange="toggleNouveauClient(this.value);" style="width: 100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff;">
                    <option value="nouveau">➕ [ Enregistrer un nouveau client ]</option>
                    <?php foreach ($tous_les_clients as $client): ?>
                        <option value="<?= $client['id_client'] ?>">👤 <?= htmlspecialchars($client['nom_prenom']) ?> (<?= htmlspecialchars($client['cel'] ?? 'Sans tel') ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- BLOC CHAMPS DU NOUVEAU CLIENT (Affiché dynamiquement si "nouveau" est sélectionné) -->
            <div id="bloc_nouveau_client" style="background: #f1f5f9; padding: 15px; border-radius: 6px; border: 1px dashed #cbd5e1; margin-bottom: 15px;">
                <h5 style="margin: 0 0 10px 0; color: #0f172a; font-size: 0.9rem;">📝 Coordonnées du nouveau client</h5>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 10px;">
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: bold; color: #475569; margin-bottom: 3px;">Nom / Prénom ou Organisme : *</label>
                        <input type="text" name="nouveau_nom" placeholder="Ex: Boutique Electro Paspébiac" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; background: #fff;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: bold; color: #475569; margin-bottom: 3px;">Site Web / Page Facebook :</label>
                        <input type="url" name="nouveau_site" placeholder="https://..." style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; background: #fff;">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: bold; color: #475569; margin-bottom: 3px;">Cellulaire :</label>
                        <input type="text" name="nouveau_cel" placeholder="418 000-0000" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; background: #fff;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: bold; color: #475569; margin-bottom: 3px;">Téléphone fixe :</label>
                        <input type="text" name="nouveau_tel" placeholder="418 000-0000" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; background: #fff;">
                    </div>
                </div>
            </div>

            <!-- PARAMÈTRES DU CONTRAT ET DU MESSAGE -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; font-weight: bold; font-size: 0.85rem; margin-bottom: 5px; color: #334155;">Durée de diffusion :</label>
                    <select name="nb_semaines" style="width: 100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff;">
    <?php foreach ($les_forfaits as $index => $forfait): ?>
        <?php $semaines = $index + 1; // 1, 2, 3 ou 4 semaines ?>
        <option value="<?= $semaines ?>">
            <?= htmlspecialchars($forfait['libelle_forfait']) ?> (<?= number_format((float)$forfait['montant'], 2, ',', ' ') ?> $)
        </option>
    <?php endforeach; ?>
                </select>
                </div>
                <div>
                    <label style="display: block; font-weight: bold; font-size: 0.85rem; margin-bottom: 5px; color: #334155;">Lien de redirection du clic (Optionnel) :</label>
                    <input type="url" name="url_redirection" placeholder="https://..." style="width: 100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff;">
                </div>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: bold; font-size: 0.85rem; margin-bottom: 5px; color: #334155;">Message publicitaire (Max 150 caractères) : *</label>
                <textarea name="message_pub" rows="2" maxlength="150" placeholder="⚡ Vente de garage ce samedi chez Electro-Paspébiac ! Venez en grand nombre." required style="width: 100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff;"></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-weight: bold; font-size: 0.85rem; margin-bottom: 5px; color: #334155;">Couleur de fond :</label>
                    <select name="fond_couleur" style="width: 100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff;">
                        <option value="rouge">Rouge vif</option>
                        <option value="bleu nuit">Bleu nuit</option>
                        <option value="noir">Noir pro</option>
                        <option value="blanc">Blanc épuré</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-weight: bold; font-size: 0.85rem; margin-bottom: 5px; color: #334155;">Couleur du texte :</label>
                    <select name="couleur_police" style="width: 100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff;">
                        <option value="blanc">Blanc</option>
                        <option value="noir">Noir</option>
                        <option value="vert fluo">Vert fluo</option>
                    </select>
                </div>
            </div>

            <button type="submit" style="background-color: #2563eb; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 0.9rem;">
                💾 Enregistrer le contrat et générer le lien de paiement
            </button>
        </form>
    </div>

    <!-- LISTE DES CONTRATS -->
    <h4 style="color: #0f172a; font-size: 1.1rem; margin-bottom: 10px;">📋 Historique et suivi des publicités</h4>

    <?php if (empty($liste_sponsors)): ?>
        <p style="color: #64748b; font-style: italic; background: #f8fafc; padding: 15px; border-radius: 6px; text-align: center;">Aucun bandeau publicitaire enregistré pour le moment.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: left;">
                <thead>
                    <tr style="background: #f1f5f9; color: #334155; border-bottom: 2px solid #cbd5e1;">
                        <th style="padding: 10px;">Client</th>
                        <th style="padding: 10px;">Message & Style</th>
                        <th style="padding: 10px;">Montant</th>
                        <th style="padding: 10px;">Période</th>
                        <th style="padding: 10px;">Statut</th>
                        <th style="padding: 10px; text-align: center;">Actions / Lien client</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($liste_sponsors as $s): ?>
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 10px; font-weight: bold; color: #0f172a;">
                                <?= htmlspecialchars($s['nom_prenom']) ?><br>
                                <span style="font-size: 0.75rem; color: #64748b; font-weight: normal;"><?= htmlspecialchars($s['cel'] ?? '') ?></span>
                            </td>
                            <td style="padding: 10px; max-width: 250px;">
                                <div style="font-size: 0.85rem; color: #1e293b; margin-bottom: 4px;"><?= htmlspecialchars($s['message']) ?></div>
                                <span style="font-size: 0.72rem; background: #e2e8f0; padding: 2px 6px; border-radius: 4px;">Fond : <?= $s['fond_couleur'] ?> | Texte : <?= $s['couleur_police'] ?></span>
                            </td>
                            <td style="padding: 10px; font-weight: bold; color: #16a34a;">
                                <?= number_format((float)$s['montant_paye'], 2, ',', ' ') ?> $
                            </td>
                            <td style="padding: 10px; font-size: 0.8rem; color: #475569;">
                                Du <?= date('d/m/Y', strtotime($s['date_debut'])) ?><br>au <?= date('d/m/Y', strtotime($s['date_fin'])) ?>
                            </td>
                            <td style="padding: 10px;">
                                <?php if ($s['statut'] === 'actif'): ?>
                                    <span style="background: #dcfce7; color: #166534; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold;">Actif</span>
                                <?php elseif ($s['statut'] === 'en_attente_paiement'): ?>
                                    <span style="background: #fef9c3; color: #854d0e; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold;">En attente de paiement</span>
                                <?php else: ?>
                                    <span style="background: #f1f5f9; color: #475569; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold;"><?= ucfirst($s['statut']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 10px; text-align: center;">
                               <?php if (!empty($s['token_paiement'])): 
        // Appel de la fonction intelligente ici !
        $url_client = getBaseUrl() . '/facture_pub.php?token=' . $s['token_paiement'];
    ?>
        <div style="margin-bottom: 6px;">
            <input type="text" readonly value="<?= $url_client ?>" onclick="this.select();" title="Cliquer pour copier le lien client" style="font-size: 0.72rem; padding: 4px; width: 100%; border: 1px dashed #94a3b8; border-radius: 4px; background: #f8fafc; cursor: pointer; text-align: center;">
        </div>
    <?php endif; ?>
                                <a href="?action_supprimer=<?= $s['id_bandeau'] ?>" onclick="return confirm('Voulez-vous vraiment supprimer définitivement cette publicité ?');" style="color: #dc2626; text-decoration: none; font-size: 0.8rem; font-weight: bold;">
                                    🗑️ Supprimer
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>

<!-- PETIT SCRIPT JAVASCRIPT POUR MASQUER/AFFICHER LE BLOC NOUVEAU CLIENT -->
<script>
function toggleNouveauClient(valeur) {
    const blocNouveau = document.getElementById('bloc_nouveau_client');
    if (valeur === 'nouveau') {
        blocNouveau.style.display = 'block';
    } else {
        blocNouveau.style.display = 'none';
    }
}
</script>
