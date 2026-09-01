<?php
// =============================================================================
// MODULE : _admin_traffic.php
// REVISION : 1.0 - Extraction et affichage comptable du trafic (Jour/Mois/Année)
// NOM DU SCRIPT : admin_modules/_admin_traffic.php
// =============================================================================
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { exit(); }

// Initialisation des variables de statistiques
$stats_jour = ['total' => 0, 'ordinateur' => 0, 'cellulaire' => 0];
$stats_mois = ['total' => 0, 'ordinateur' => 0, 'cellulaire' => 0];
$stats_annee = ['total' => 0, 'ordinateur' => 0, 'cellulaire' => 0];

try {
    // 1. STATISTIQUES DU JOUR (Aujourd'hui)
    $query_jour = $bdd->query("
        SELECT type_appareil, COUNT(*) as quantite 
        FROM jevend_stats_connect 
        WHERE DATE(date_connexion) = CURRENT_DATE() 
        GROUP BY type_appareil
    ");
    while ($row = $query_jour->fetch(PDO::FETCH_ASSOC)) {
        $stats_jour[$row['type_appareil']] = (int)$row['quantite'];
        $stats_jour['total'] += (int)$row['quantite'];
    }

    // 2. STATISTIQUES DU MOIS EN COURS
    $query_mois = $bdd->query("
        SELECT type_appareil, COUNT(*) as quantite 
        FROM jevend_stats_connect 
        WHERE MONTH(date_connexion) = MONTH(CURRENT_DATE()) AND YEAR(date_connexion) = YEAR(CURRENT_DATE())
        GROUP BY type_appareil
    ");
    while ($row = $query_mois->fetch(PDO::FETCH_ASSOC)) {
        $stats_mois[$row['type_appareil']] = (int)$row['quantite'];
        $stats_mois['total'] += (int)$row['quantite'];
    }

    // 3. STATISTIQUES DE L'ANNÉE EN COURS
    $query_annee = $bdd->query("
        SELECT type_appareil, COUNT(*) as quantite 
        FROM jevend_stats_connect 
        WHERE YEAR(date_connexion) = YEAR(CURRENT_DATE())
        GROUP BY type_appareil
    ");
    while ($row = $query_annee->fetch(PDO::FETCH_ASSOC)) {
        $stats_annee[$row['type_appareil']] = (int)$row['quantite'];
        $stats_annee['total'] += (int)$row['quantite'];
    }

} catch (PDOException $e) {
    echo "<div style='color: #991b1b; background: #fef2f2; padding: 15px; border-radius: 6px;'>⚠️ Erreur SQL Traffic : " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Calcul des pourcentages globaux annuels pour le visuel
$pct_ordi = $stats_annee['total'] > 0 ? round(($stats_annee['ordinateur'] / $stats_annee['total']) * 100) : 0;
$pct_cell = $stats_annee['total'] > 0 ? round(($stats_annee['cellulaire'] / $stats_annee['total']) * 100) : 0;
?>

<div class="admin-bloc-vide" style="background: #ffffff; padding: 25px; border: 1px solid #e2e8f0; border-radius: 8px; text-align: left; color: inherit; box-sizing: border-box; width: 100%;">
    <h3 style="color: #0f172a; margin-top: 0; margin-bottom: 20px; font-size: 1.3rem; display: flex; align-items: center; gap: 10px;">
        <span>📊</span> Analyse en Temps Réel du Trafic Réseau
    </h3>

    <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 30px; width: 100%; box-sizing: border-box;">
        
        <div style="flex: 1; min-width: 250px; background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 20px; border-radius: 6px; box-sizing: border-box;">
            <span style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: bold;">📅 Aujourd'hui</span>
            <div style="font-size: 2.2rem; font-weight: 800; color: #0f172a; margin: 10px 0;"><?= $stats_jour['total'] ?> <span style="font-size: 1rem; font-weight: 400; color: #64748b;">connexions</span></div>
            <div style="font-size: 0.9rem; color: #334155; display: flex; justify-content: space-between; border-top: 1px solid #e2e8f0; padding-top: 8px;">
                <span>💻 Ordi : <strong><?= $stats_jour['ordinateur'] ?></strong></span>
                <span>📱 Cell : <strong><?= $stats_jour['cellulaire'] ?></strong></span>
            </div>
        </div>

        <div style="flex: 1; min-width: 250px; background-color: #f0fdf4; border: 1px solid #bbf7d0; padding: 20px; border-radius: 6px; box-sizing: border-box;">
            <span style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; color: #166534; font-weight: bold;">🌙 Ce mois-ci</span>
            <div style="font-size: 2.2rem; font-weight: 800; color: #166534; margin: 10px 0;"><?= $stats_mois['total'] ?> <span style="font-size: 1rem; font-weight: 400; color: #166534;">connexions</span></div>
            <div style="font-size: 0.9rem; color: #14532d; display: flex; justify-content: space-between; border-top: 1px solid #bbf7d0; padding-top: 8px;">
                <span>💻 Ordi : <strong><?= $stats_mois['ordinateur'] ?></strong></span>
                <span>📱 Cell : <strong><?= $stats_mois['cellulaire'] ?></strong></span>
            </div>
        </div>

        <div style="flex: 1; min-width: 250px; background-color: #eff6ff; border: 1px solid #bfdbfe; padding: 20px; border-radius: 6px; box-sizing: border-box;">
            <span style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; color: #1e40af; font-weight: bold;">🚀 Bilan Annuel</span>
            <div style="font-size: 2.2rem; font-weight: 800; color: #1e40af; margin: 10px 0;"><?= $stats_annee['total'] ?> <span style="font-size: 1rem; font-weight: 400; color: #1e40af;">totale</span></div>
            <div style="font-size: 0.9rem; color: #1e3a8a; display: flex; justify-content: space-between; border-top: 1px solid #bfdbfe; padding-top: 8px;">
                <span>💻 Ordi : <strong><?= $stats_annee['ordinateur'] ?></strong></span>
                <span>📱 Cell : <strong><?= $stats_annee['cellulaire'] ?></strong></span>
            </div>
        </div>

    </div>

    <h4 style="color: #334155; margin-bottom: 10px; font-size: 1rem;">📊 Proportion globale des appareils (Cette année)</h4>
    <div style="width: 100%; background-color: #e2e8f0; height: 24px; border-radius: 12px; display: flex; overflow: hidden; font-weight: bold; font-size: 0.85rem; color: #ffffff; line-height: 24px; text-align: center; box-sizing: border-box; margin-bottom: 10px;">
        <?php if ($stats_annee['total'] > 0): ?>
            <div style="width: <?= $pct_ordi ?>%; background-color: #2563eb; transition: width 0.5s ease;"><?= $pct_ordi ?>% 💻</div>
            <div style="width: <?= $pct_cell ?>%; background-color: #ec4899; transition: width 0.5s ease;"><?= $pct_cell ?>% 📱</div>
        <?php else: ?>
            <div style="width: 100%; color: #64748b; font-style: italic; font-weight: normal;">Aucune donnée accumulée pour le moment</div>
        <?php endif; ?>
    </div>
    
    <div style="display: flex; gap: 15px; font-size: 0.8rem; color: #64748b; margin-top: 5px;">
        <span style="display: flex; align-items: center; gap: 5px;"><span style="display: inline-block; width: 12px; height: 12px; background: #2563eb; border-radius: 3px;"></span> Ordinateur / PC</span>
        <span style="display: flex; align-items: center; gap: 5px;"><span style="display: inline-block; width: 12px; height: 12px; background: #ec4899; border-radius: 3px;"></span> Cellulaire / Mobile</span>
    </div>

<div></div>
<div class="admin-bloc-vide">

<!-- BLOC CONTRÔLE MAINTENANCE -->
<div style="background-color: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 20px; margin-top: 20px; color: #f8fafc;">
    <h3 style="margin-bottom: 15px; font-size: 1.1rem; color: #38bdf8;">🚧 Devanture & Mode Maintenance</h3>
    
    <form id="form-maintenance-admin" style="display: flex; flex-direction: column; gap: 15px;">
        <div style="display: flex; align-items: center; justify-content: space-between; background: #1e293b; padding: 12px 15px; border-radius: 6px;">
            <label for="maintenance_actif" style="font-weight: bold; cursor: pointer;">État de la devanture :</label>
            <select name="maintenance_actif" id="maintenance_actif" style="background: #0f172a; color: #fff; border: 1px solid #475569; padding: 8px 12px; border-radius: 4px; font-weight: bold;">
                <option value="0" <?= ($params_maint['maintenance_actif'] ?? '0') === '0' ? 'selected' : '' ?>>🟢 SITE OUVERT (Accès public)</option>
                <option value="1" <?= ($params_maint['maintenance_actif'] ?? '0') === '1' ? 'selected' : '' ?>>🔴 FERMÉ (Devanture active)</option>
            </select>
        </div>

        <div style="display: flex; gap: 15px;">
            <div style="flex: 1;">
                <label style="display: block; font-size: 0.85rem; color: #94a3b8; margin-bottom: 5px;">Heure / Date de réouverture affichée :</label>
                <input type="text" name="maintenance_heure_ouverture" value="<?= htmlspecialchars($params_maint['maintenance_heure_ouverture'] ?? '14:00') ?>" placeholder="ex: 14:00 ou Demain 8h" style="width: 100%; background: #1e293b; color: #fff; border: 1px solid #475569; padding: 10px; border-radius: 4px;">
            </div>
            
            <div style="flex: 2;">
                <label style="display: block; font-size: 0.85rem; color: #94a3b8; margin-bottom: 5px;">Message aux visiteurs :</label>
                <input type="text" name="maintenance_message" value="<?= htmlspecialchars($params_maint['maintenance_message'] ?? '') ?>" placeholder="ex: Travaux techniques en cours..." style="width: 100%; background: #1e293b; color: #fff; border: 1px solid #475569; padding: 10px; border-radius: 4px;">
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center;">
            <span id="maint-status-msg" style="font-weight: bold; font-size: 0.9rem;"></span>
            <button type="submit" style="background: #2563eb; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; font-weight: bold; cursor: pointer;">
                Enregistrer les modifications
            </button>
        </div>
    </form>
</div>


</div> </div>

<script>
document.getElementById('form-maintenance-admin').addEventListener('submit', function(e) {
    e.preventDefault();
    const statusMsg = document.getElementById('maint-status-msg');
    const formData = new FormData(this);
    formData.append('action', 'update_maintenance');

    statusMsg.style.color = '#38bdf8';
    statusMsg.textContent = 'Mise à jour en cours...';

    fetch('panneau_maintenance_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            statusMsg.style.color = '#4ade80';
            statusMsg.textContent = ' Configuration enregistrée avec succès !';
        } else {
            statusMsg.style.color = '#f87171';
            statusMsg.textContent = ' Erreur : ' + (data.error || 'Impossible de sauvegarder.');
        }
    })
    .catch(() => {
        statusMsg.style.color = '#f87171';
        statusMsg.textContent = ' Erreur de connexion avec le serveur.';
    });
});
</script>
