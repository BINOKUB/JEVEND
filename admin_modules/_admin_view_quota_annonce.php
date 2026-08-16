<?php
// =============================================================================
// NOM DU SCRIPT : _admin_view_quota_annonce.php include dans _admin_rpm.php
// DESCRIPTION  : Affichage de l'historique des quotas d'annonces (30 derniers jours) dans l'onglet RPM
// =============================================================================

$quotas_jours = [];
try {
    // Récupération des 30 derniers jours triés du plus récent au plus ancien
    $stmt_q = $bdd->query("
        SELECT date_jour, nombre_annonces 
        FROM jevend_quota_annonces 
        ORDER BY date_jour DESC 
        LIMIT 30
    ");
    $quotas_jours = $stmt_q->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $quotas_jours = [];
}
?>

<div style="background: #ffffff; border-radius: 8px; padding: 20px; border: 1px solid #e2e8f0; margin-top: 25px;">
    <h3 style="margin-top: 0; color: #1e3a8a; font-size: 1.2rem; display: flex; align-items: center; gap: 8px;">
        📈 Suivi Quotidien des Annonces (30 derniers jours)
    </h3>
    <p style="color: #475569; font-size: 0.85rem; margin-bottom: 15px;">
        Historique du volume d'annonces créées par jour sur la plateforme.
    </p>

    <?php if (!empty($quotas_jours)): ?>
        <div style="max-height: 220px; overflow-y: auto; border: 1px solid #cbd5e1; border-radius: 6px;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; text-align: left;">
                <thead>
                    <tr style="background-color: #f1f5f9; color: #334155; position: sticky; top: 0; z-index: 1;">
                        <th style="padding: 10px 15px; border-bottom: 1px solid #cbd5e1;">Date</th>
                        <th style="padding: 10px 15px; border-bottom: 1px solid #cbd5e1; text-align: right;">Annonces créées</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($quotas_jours as $q): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 8px 15px; color: #0f172a; font-weight: 500;">
                                <?= htmlspecialchars($q['date_jour']) ?>
                                <?php if ($q['date_jour'] === date('Y-m-d')): ?>
                                    <span style="background-color: #dbeafe; color: #1d4ed8; font-size: 0.7rem; padding: 2px 6px; border-radius: 4px; margin-left: 6px; font-weight: bold;">Aujourd'hui</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 8px 15px; text-align: right; color: #2563eb; font-weight: bold;">
                                <?= (int)$q['nombre_annonces'] ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="background: #f8fafc; border: 1px solid #cbd5e1; padding: 15px; border-radius: 6px; text-align: center; color: #64748b; font-size: 0.85rem;">
            Aucune donnée de quota enregistrée pour le moment.
        </div>
    <?php endif; ?>
</div>
