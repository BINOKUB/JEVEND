<?php
/*
====================================================
NOM DU SCRIPT : faq.php
REVISION      : v1.5
Description   : Page publique F.A.Q. avec rendu HTML propre (WYSIWYG compatible)
Nouveautés    : 
  - Affichage direct du HTML brut stocké par l'éditeur visuel de l'admin
  - Ajout de règles CSS de nettoyage pour harmoniser le texte enrichi (listes, paragraphes, styles)
  - Intégration de la session et des barres de navigation et flottante
====================================================
*/

// --- 1. DÉMARRAGE DE SESSION & DÉBOGAGE ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- 2. INCLUSION DU FICHIER DE CONFIGURATION ---
require_once 'config.php';

// Vérification de la variable de connexion $bdd
$db_instance = null;
if (isset($bdd) && $bdd instanceof PDO) {
    $db_instance = $bdd;
} elseif (isset($pdo) && $pdo instanceof PDO) {
    $db_instance = $pdo;
} elseif (isset($conn) && $conn instanceof PDO) {
    $db_instance = $conn;
}

$faqs = [];

// --- 3. RÉCUPÉRATION DES QUESTIONS DEPUIS LA BDD ---
if ($db_instance) {
    try {
        $stmt = $db_instance->prepare("SELECT * FROM jevend_faq WHERE actif = 1 ORDER BY ordre ASC, id ASC");
        $stmt->execute();
        $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $error_message = "Erreur SQL : " . $e->getMessage();
    }
} else {
    $error_message = "Erreur : La variable \$bdd n'a pas été trouvée dans config.php.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>F.A.Q. - JeVend.com</title>
    <style>
        body {
            background-color: #ffffff;
            color: #1e293b;
            font-family: system-ui, -apple-system, sans-serif;
            margin: 0;
            padding: 0;
        }

        .faq-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .faq-title {
            text-align: center;
            font-size: 2rem;
            margin-bottom: 30px;
            color: #0f172a;
        }

        .faq-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 15px;
            overflow: hidden;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .faq-item:hover {
            border-color: #2563eb;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.08);
        }

        .faq-question {
            width: 100%;
            padding: 18px 20px;
            background: none;
            border: none;
            outline: none;
            color: #0f172a;
            font-size: 1.1rem;
            font-weight: 600;
            text-align: left;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .faq-toggle {
            font-size: 1.4rem;
            font-weight: bold;
            color: #2563eb;
            min-width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(37, 99, 235, 0.1);
            border-radius: 50%;
            transition: transform 0.3s ease, background 0.3s ease, color 0.3s ease;
        }

        .faq-item.active .faq-toggle {
            transform: rotate(45deg);
            color: #dc2626;
            background: rgba(220, 38, 38, 0.1);
        }

        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s cubic-bezier(0, 1, 0, 1), padding 0.3s ease;
            background: #ffffff;
            color: #334155;
            line-height: 1.6;
            padding: 0 20px;
        }

        .faq-item.active .faq-answer {
            max-height: 1500px;
            padding: 18px 20px;
            border-top: 1px solid #e2e8f0;
        }

        /* Nettoyage et harmonisation du HTML enrichi provenant de l'éditeur */
        .faq-answer p {
            margin: 0 0 10px 0;
        }
        .faq-answer ul, .faq-answer ol {
            margin: 10px 0;
            padding-left: 25px;
        }
        .faq-answer li {
            margin-bottom: 5px;
        }

        .faq-alert {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>

    <!-- INCLUSION DE LA BARRE DE NAVIGATION -->
    <?php 
        if (file_exists('partials/_nav_publique.php')) {
            include_once 'partials/_nav_publique.php';
        }
    ?>

    <main class="faq-container">
        <h1 class="faq-title">Foire Aux Questions (F.A.Q.)</h1>

        <?php if (isset($error_message)): ?>
            <div class="faq-alert">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($faqs)): ?>
            <?php foreach ($faqs as $faq): ?>
                <div class="faq-item">
                    <button class="faq-question">
                        <span><?= htmlspecialchars($faq['question']) ?></span>
                        <span class="faq-toggle">+</span>
                    </button>
                    <!-- Affichage du HTML brut de l'éditeur WYSIWYG -->
                    <div class="faq-answer">
                        <?= $faq['reponse'] ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <?php if (!isset($error_message)): ?>
                <p style="text-align: center; color: #64748b;">Aucune question disponible pour le moment.</p>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const faqQuestions = document.querySelectorAll('.faq-question');

            faqQuestions.forEach(button => {
                button.addEventListener('click', () => {
                    const currentItem = button.parentElement;

                    document.querySelectorAll('.faq-item').forEach(item => {
                        if (item !== currentItem) {
                            item.classList.remove('active');
                        }
                    });

                    currentItem.classList.toggle('active');
                });
            });
        });
    </script>
<?php 
if (file_exists('partials/_barre_flottante.php')) {
    include 'partials/_barre_flottante.php';
}
?>
</body>
</html>
