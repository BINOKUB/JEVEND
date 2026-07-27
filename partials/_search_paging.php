<?php
// =============================================================================
// NOM DU SCRIPT : partials/_search_paging.php
// REVISION : 1.0 - Module de pagination dynamique style Google
// =============================================================================
?>
<?php if ($total_pages > 1): ?>
    <div class="pagination-conteneur">
        
        <!-- Logo JEVEND étiré selon la page -->
        <div class="pagination-logo">
            <span class="pagination-lettre-j">j</span>
            <span class="pagination-lettre-v">v</span>
            <?php 
            // Génère autant de "e" que de pages affichées (maximum 10 pour le design)
            $nb_e = min($total_pages, 10);
            for ($i = 1; $i <= $nb_e; $i++) {
                $couleur_e = ($i % 2 == 0) ? 'pagination-lettre-e2' : 'pagination-lettre-e';
                echo "<span class='$couleur_e'>e</span>";
            }
            ?>
            <span class="pagination-lettre-n">n</span>
            <span class="pagination-lettre-d">d</span>
        </div>

        <div class="pagination-liens">
            <!-- Lien Précédent -->
            <?php if ($page_actuelle > 1): ?>
                <a href="search.php?page=<?= ($page_actuelle - 1) . $url_params ?>" class="btn-prec-suiv">Précédent</a>
            <?php endif; ?>

            <!-- Numéros de pages (Affichage fenêtré de 10 pages max) -->
            <?php 
            $debut_fenetre = max(1, $page_actuelle - 4);
            $fin_fenetre = min($total_pages, $page_actuelle + 5);
            
            for ($i = $debut_fenetre; $i <= $fin_fenetre; $i++): 
                if ($i == $page_actuelle): ?>
                    <span class="page-num-active"><?= $i ?></span>
                <?php else: ?>
                    <a href="search.php?page=<?= $i . $url_params ?>"><?= $i ?></a>
                <?php endif; 
            endfor; 
            ?>

            <!-- Lien Suivant -->
            <?php if ($page_actuelle < $total_pages): ?>
                <a href="search.php?page=<?= ($page_actuelle + 1) . $url_params ?>" class="btn-prec-suiv">Suivant</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
