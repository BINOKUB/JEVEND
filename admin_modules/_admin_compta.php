<?php
// =============================================================================
// MODULE : _admin_compta.php
// REVISION : 1.0 - Compilation financière et bilans des revenus Stripe
// NOM DU SCRIPT : admin_modules/_admin_compta.php
// =============================================================================
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { exit(); }

// Définition des périodes temporelles basées sur l'heure locale de Matane
$aujourdhui = date('Y-m-d');
$ce_mois = date('Y-m');
$cette_annee = date('Y');

$revenu_jour = 0.00;
$revenu_mois = 0.00;
$revenu_annee = 0.00;
$transactions = [];

try {
    // 1. CALCUL DU CHIFFRE D'AFFAIRES - AUJOURD'HUI
    $stmt_jour = $bdd->prepare("SELECT SUM(montant_paye) FROM jevend_achats_publicites WHERE DATE(date_achat) = ?");
    $stmt_jour->execute([$aujourdhui]);
    $revenu_jour = (float)($stmt_jour->fetchColumn() ?? 0.00);

    // 2. CALCUL DU CHIFFRE D'AFFAIRES - CE MOIS-CI
    $stmt_mois = $bdd->prepare("SELECT SUM(montant_paye) FROM jevend_achats_publicites WHERE DATE_FORMAT(date_achat, '%Y-%m') = ?");
    $stmt_mois->execute([$ce_mois]);
    $revenu_mois = (float)($stmt_mois->fetchColumn() ?? 0.00);

    // 3. CALCUL DU CHIFFRE D'AFFAIRES - CETTE ANNÉE
    $stmt_annee = $bdd->prepare("SELECT SUM(montant_paye) FROM jevend_achats_publicites WHERE DATE_FORMAT(date_achat, '%Y') = ?");
    $stmt_annee->execute([$cette_annee]);
    $revenu_annee = (float)($stmt_annee->fetchColumn() ?? 0.00);

    // 4. RÉCUPÉRATION DES 10 DERNIÈRES TRANSACTIONS AVEC COORDONNÉES MEMBRES
    $sql_trans = "
        SELECT a.id_achat, a.type_produit, a.montant_paye, a.duree_jours, a.date_achat, a.stripe_checkout_id, u.nom, u.courriel
        FROM jevend_achats_publicites a
        INNER JOIN jevend_utilisateurs u ON a.id_utilisateur = u.id_utilisateur
        ORDER BY a.date_achat DESC
        LIMIT 10
    ";
    $transactions = $bdd->query($sql_trans)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "<div style='color: #991b1b; background: #fef2f2; padding: 15px; border-radius: 6px;'>⚠️ Erreur SQL Comptabilité : " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

<div class="admin-bloc-vide" style="background: #ffffff; padding: 25px; border: 1px solid #e2e8f0; border-radius: 8px; text-align: left; color: inherit; box-sizing: border-box; width: 100%;">
    
    <h3 style="color: #1e3a8a; margin-top: 0; margin-bottom: 20px; font-size: 1.3rem; display: flex; align-items: center; gap: 10px;">
        <span>💰</span> Bilans Financiers & Ventes de Bannières
    </h3>

    <!-- COMPTEURS FINANCIERS TRIPLE BLOCS -->
    <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 30px; width: 100%; box-sizing: border-box;">
        
        <!-- Bloc Aujourd'hui -->
        <div style="flex: 1; min-width: 240px; background: #fafafa; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; box-sizing: border-box;">
            <div style="font-size: 0.8rem; font-weight: bold; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">📅 Aujourd'hui</div>
            <div style="font-size: 2rem; font-weight: bold; color: #0f172a; margin-top: 10px;">
                <?= number_format($revenu_jour, 2, ',', ' ') ?> $ <span style="font-size: 1rem; color: #64748b;">CAD</span>
            </div>
        </div>

        <!-- Bloc Ce Mois-ci -->
        <div style="flex: 1; min-width: 240px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 20px; box-sizing: border-box;">
            <div style="font-size: 0.8rem; font-weight: bold; color: #166534; text-transform: uppercase; letter-spacing: 0.5px;">🌙 Ce mois-ci</div>
            <div style="font-size: 2rem; font-weight: bold; color: #15803d; margin-top: 10px;">
                <?= number_format($revenu_mois, 2, ',', ' ') ?> $ <span style="font-size: 1rem; color: #166534;">CAD</span>
            </div>
        </div>

        <!-- Bloc Bilan Annuel -->
        <div style="flex: 1; min-width: 240px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 20px; box-sizing: border-box;">
            <div style="font-size: 0.8rem; font-weight: bold; color: #1e40af; text-transform: uppercase; letter-spacing: 0.5px;">🚀 Bilan annuel</div>
            <div style="font-size: 2rem; font-weight: bold; color: #1d4ed8; margin-top: 10px;">
                <?= number_format($revenu_annee, 2, ',', ' ') ?> $ <span style="font-size: 1rem; color: #1e40af;">CAD</span>
            </div>
        </div>

    </div>

    <!-- TABLEAU RESPONSIVE DES 10 DERNIÈRES TRANSACTIONS -->
    <h4 style="color: #0f172a; margin-bottom: 15px; font-size: 1.1rem; border-bottom: 2px solid #cbd5e1; padding-bottom: 8px;">📋 Journal des 10 derniers encaissements Stripe</h4>

    <style>
        .table-compta { width: 100%; border-collapse: collapse; }
        .table-compta th, .table-compta td { padding: 12px; border-bottom: 1px solid #e2e8f0; text-align: left; font-size: 0.9rem; }
        .table-compta th { background-color: #f8fafc; color: #475569; font-weight: bold; }
        
        .badge-prod { padding: 3px 6px; border-radius: 4px; font-weight: bold; font-size: 0.75rem; text-transform: uppercase; }
        .prod-reguliere { background-color: #f1f5f9; color: #334155; }
        .prod-bronze { background-color: #ffedd5; color: #c2410c; }
        .prod-premium { background-color: #e0f2fe; color: #0369a1; }
        .prod-supreme { background-color: #f3e8ff; color: #6b21a8; }

        @media (max-width: 768px) {
            .table-compta thead { display: none; }
            .table-compta tr { display: block; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; margin-bottom: 12px; }
            .table-compta td { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: none; border-top: 1px solid #f1f5f9; }
            .table-compta td:first-child { border-top: none; }
            .table-compta td::before { content: attr(data-label); font-weight: bold; color: #64748b; }
        }
    </style>

    <?php if (empty($transactions)): ?>
        <div style="text-align: center; color: #94a3b8; padding: 30px 10px; font-style: italic;">
            Aucune transaction enregistrée pour le moment.
        </div>
    <?php else: ?>
        <table class="table-compta">
            <thead>
                <tr>
                    <th>Date / Heure</th>
                    <th>Acheteur</th>
                    <th>Forfait</th>
                    <th>Durée</th>
                    <th>Réf. Stripe</th>
                    <th style="text-align: right;">Montant</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $t): ?>
                    <tr>
                        <td data-label="Date"><?= date('d-m-Y H:i', strtotime($t['date_achat'])) ?></td>
                        <td data-label="Acheteur">
                            <strong><?= htmlspecialchars($t['nom']) ?></strong><br>
                            <span style="font-size: 0.75rem; color: #64748b;"><?= htmlspecialchars($t['courriel']) ?></span>
                        </td>
                        <td data-label="Forfait">
                            <span class="badge-prod prod-<?= $t['type_produit'] ?>"><?= htmlspecialchars($t['type_produit']) ?></span>
                        </td>
                        <td data-label="Durée"><?= $t['duree_jours'] ?> jours</td>
                        <td data-label="Réf. Stripe"><code style="background: #f1f5f9; padding: 2px 5px; border-radius: 3px; font-size: 0.8rem;"><?= htmlspecialchars(substr($t['stripe_checkout_id'], 0, 18)) ?>...</code></td>
                        <td data-label="Montant" style="text-align: right; font-weight: bold; color: #15803d; font-size: 0.95rem;"><?= number_format($t['montant_paye'], 2, ',', ' ') ?> $</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</div>
