<?php
// Charge automatiquement les classes de src/classes/.
// Convention : un namespace = un dossier, une classe = un fichier du même nom.
// Exemple : Spots\SpotsManager -> src/classes/Spots/SpotsManager.php
spl_autoload_register(function (string $class): void {
    // Convertit les séparateurs de namespace en séparateurs de répertoires
    $relativePath = str_replace('\\', '/', $class);

    // Construit le chemin complet du fichier
    $file = __DIR__ . '/../classes/' . $relativePath . '.php';

    // Vérifie si le fichier existe avant de l'inclure
    if (file_exists($file)) {
        require_once $file;
    }
});
