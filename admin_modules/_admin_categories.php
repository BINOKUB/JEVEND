<?php
// =============================================================================
// MODULE : _admin_categories.php
// REVISION : 1.1 - Correctif de syntaxe sur la boucle foreach des parents
// NOM DU SCRIPT : admin_modules/_admin_categories.php
// =============================================================================
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { exit(); }

$msg_succes = "";
$msg_erreur = "";

// 1. TRAITEMENT DE L'AJOUT D'UNE CATÉGORIE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_ajouter_cat'])) {
    $nom_fr = trim($_POST['nom_fr'] ?? '');
    $nom_en = trim($_POST['nom_en'] ?? '');
    $parent_id = $_POST['parent_id'] === 'NULL' ? null : (int)$_POST['parent_id'];

    if (empty($nom_fr) || empty($nom_en)) {
        $msg_erreur = "Veuillez remplir les noms en français et en anglais.";
    } else {
        try {
            $stmt = $bdd->prepare("INSERT INTO jevend_categories (parent_id, nom_fr, nom_en) VALUES (?, ?, ?)");
            $stmt->execute([$parent_id, $nom_fr, $nom_en]);
            $msg_succes = "La catégorie a été ajoutée avec succès !";
        } catch (PDOException $e) {
            $msg_erreur = "Erreur lors de l'ajout : " . htmlspecialchars($e->getMessage());
        }
    }
}

// 2. TRAITEMENT DE LA SUPPRESSION D'ANOMALIE / CATÉGORIE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_supprimer_cat'])) {
    $id_cat = (int)($_POST['id_categorie'] ?? 0);

    if ($id_cat > 0) {
        try {
            // Sécurité : On bascule à NULL les sous-catégories enfants avant de supprimer le parent
            $update_enfants = $bdd->prepare("UPDATE jevend_categories SET parent_id = NULL WHERE parent_id = ?");
            $update_enfants->execute([$id_cat]);

            $delete = $bdd->prepare("DELETE FROM jevend_categories WHERE id_categorie = ?");
            $delete->execute([$id_cat]);
            $msg_succes = "La catégorie a été supprimée.";
        } catch (PDOException $e) {
            $msg_erreur = "Erreur de suppression : " . htmlspecialchars($e->getMessage());
        }
    }
}

// 3. EXTRACTION COMPTABLE ET TRI DE TOUTES LES CATÉGORIES
$categories_parentes = [];
$sous_categories = [];

try {
    $query = $bdd->query("SELECT * FROM jevend_categories ORDER BY parent_id ASC, id_categorie ASC");
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        if ($row['parent_id'] === null) {
            $categories_parentes[$row['id_categorie']] = $row;
            $categories_parentes[$row['id_categorie']]['enfants'] = [];
        } else {
            $sous_categories[] = $row;
        }
    }

    // Associer les enfants à leurs parents respectifs
    foreach ($sous_categories as $sous) {
        if (isset($categories_parentes[$sous['parent_id']])) {
            $categories_parentes[$sous['parent_id']]['enfants'][] = $sous;
        }
    }
} catch (PDOException $e) {
    echo "<div style='color: #991b1b; background: #fef2f2; padding: 15px; border-radius: 6px;'>⚠️ Erreur SQL Catégories : " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

<div class="admin-bloc-vide" style="background: #ffffff; padding: 25px; border: 1px solid #e2e8f0; border-radius: 8px; text-align: left; color: inherit; box-sizing: border-box; width: 100%;">
    
    <h3 style="color: #1e3a8a; margin-top: 0; margin-bottom: 20px; font-size: 1.3rem; display: flex; align-items: center; gap: 10px;">
        <span>📁</span> Structure et Organisation des Catégories
    </h3>

    <?php if (!empty($msg_erreur)): ?>
        <div style="background-color: #fef2f2; color: #991b1b; padding: 12px; border-radius: 6px; font-size: 0.85rem; margin-bottom: 20px; border: 1px solid #fecaca; font-weight: bold;"><?= $msg_erreur ?></div>
    <?php endif; ?>
    <?php if (!empty($msg_succes)): ?>
        <div style="background-color: #f0fdf4; color: #166534; padding: 12px; border-radius: 6px; font-size: 0.85rem; margin-bottom: 20px; border: 1px solid #bbf7d0; font-weight: bold;"><?= $msg_succes ?></div>
    <?php endif; ?>

    <div style="display: flex; gap: 30px; flex-wrap: wrap; width: 100%; box-sizing: border-box;">
        
        <!-- FORMULAIRE D'AJOUT (BLOC GAUCHE) -->
        <div style="flex: 1; min-width: 280px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 20px; border-radius: 6px; box-sizing: border-box; height: fit-content;">
            <h4 style="margin-top: 0; color: #0f172a; margin-bottom: 15px; font-size: 1rem; border-bottom: 2px solid #cbd5e1; padding-bottom: 8px;">➕ Ajouter une catégorie</h4>
            
            <form action="panneau.php" method="POST" style="display: flex; flex-direction: column; gap: 12px;">
                <input type="hidden" name="action_ajouter_cat" value="1">
                
                <div>
                    <label style="font-size: 0.8rem; font-weight: bold; color: #475569; display: block; margin-bottom: 4px;">Type / Parent :</label>
                    <select name="parent_id" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.9rem; background: white;">
                        <option value="NULL">[ Catégorie Principale ]</option>
                        <?php foreach ($categories_parentes as $p): ?>
                            <option value="<?= $p['id_categorie'] ?>">Sous-catégorie de : <?= htmlspecialchars($p['nom_fr']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="font-size: 0.8rem; font-weight: bold; color: #475569; display: block; margin-bottom: 4px;">Nom (Français) :</label>
                    <input type="text" name="nom_fr" placeholder="Ex: Instruments de Musique" required style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box;">
                </div>

                <div>
                    <label style="font-size: 0.8rem; font-weight: bold; color: #475569; display: block; margin-bottom: 4px;">Nom (Anglais) :</label>
                    <input type="text" name="nom_en" placeholder="Ex: Musical Instruments" required style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box;">
                </div>

                <button type="submit" style="background: #2563eb; color: white; border: none; padding: 10px; font-weight: bold; border-radius: 4px; cursor: pointer; margin-top: 5px; font-size: 0.9rem;">
                    🚀 Enregistrer la catégorie
                </button>
            </form>
        </div>

        <!-- AFFICHAGE DE L'ARBORESCENCE (BLOC DROITE) -->
        <div style="flex: 2; min-width: 320px; box-sizing: border-box;">
            <h4 style="margin-top: 0; color: #0f172a; margin-bottom: 15px; font-size: 1rem; border-bottom: 2px solid #cbd5e1; padding-bottom: 8px;">📋 Liste des sections configurées</h4>
            
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <?php foreach ($categories_parentes as $p): ?>
                    
                    <!-- Ligne Catégorie Principale -->
                    <div style="display: flex; justify-content: space-between; align-items: center; background: #eff6ff; border: 1px solid #bfdbfe; padding: 10px 15px; border-radius: 6px; box-sizing: border-box;">
                        <div>
                            <span style="font-weight: bold; color: #1e40af; font-size: 0.95rem;">📂 <?= htmlspecialchars($p['nom_fr']) ?></span>
                            <span style="font-size: 0.8rem; color: #64748b; font-style: italic; margin-left: 8px;">(<?= htmlspecialchars($p['nom_en']) ?>)</span>
                        </div>
                        <form action="panneau.php" method="POST" style="margin: 0;" onsubmit="return confirm('Voulez-vous vraiment supprimer cette catégorie ?');">
                            <input type="hidden" name="action_supprimer_cat" value="1">
                            <input type="hidden" name="id_categorie" value="<?= $p['id_categorie'] ?>">
                            <button type="submit" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 0.9rem;" title="Supprimer">🗑️</button>
                        </form>
                    </div>

                    <!-- Lignes Sous-Catégories Enfants -->
                    <?php foreach ($p['enfants'] as $e): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; background: #ffffff; border: 1px solid #e2e8f0; padding: 8px 15px; margin-left: 30px; border-radius: 4px; box-sizing: border-box; border-left: 4px solid #cbd5e1;">
                            <div>
                                <span style="color: #334155; font-size: 0.9rem;">↳ 📄 <?= htmlspecialchars($e['nom_fr']) ?></span>
                                <span style="font-size: 0.75rem; color: #94a3b8; font-style: italic; margin-left: 6px;">(<?= htmlspecialchars($e['nom_en']) ?>)</span>
                            </div>
                            <form action="panneau.php" method="POST" style="margin: 0;" onsubmit="return confirm('Voulez-vous vraiment supprimer cette sous-catégorie ?');">
                                <input type="hidden" name="action_supprimer_cat" value="1">
                                <input type="hidden" name="id_categorie" value="<?= $e['id_categorie'] ?>">
                                <button type="submit" style="background: none; border: none; color: #f87171; cursor: pointer; font-size: 0.85rem;" title="Supprimer">🗑️</button>
                            </form>
                        </div>
                    <?php endforeach; ?>

                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>
