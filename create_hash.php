<?php
// Modifie le mot de passe ici
$mot_de_passe_clair = "MonMotDePasseSecurit123";

// Génération du hachage compatible avec password_verify()
$hash_genere = password_hash($mot_de_passe_clair, PASSWORD_DEFAULT);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Générateur de Hash</title>
</head>
<body style="font-family: sans-serif; padding: 40px;">
    <h2>Générateur de hash pour Jevend</h2>
    <p><strong>Mot de passe en clair :</strong> <?php echo htmlspecialchars($mot_de_passe_clair); ?></p>
    <p><strong>Hash à copier dans SQL :</strong></p>
    <textarea rows="3" cols="80" readonly style="padding: 10px; font-family: monospace;"><?php echo $hash_genere; ?></textarea>
</body>
</html>
