<?php
require_once __DIR__ . '/../src/utils/autoloader.php';

use References\ReferencesManager;
use Spots\SpotsManager;

$spots = [];
$error = null;

try {
    $spotsManager = new SpotsManager();
    $referencesManager = new ReferencesManager();

    $spots = $spotsManager->getPublishedSpots();
    $cantons = $referencesManager->getCantons();
} catch (Throwable $e) {
    // Le détail technique va dans le journal du serveur, jamais à l'écran en production
    error_log($e->getMessage());
    $error = Config::isProduction() ? 'Les lieux ne peuvent pas être affichés pour le moment.' : $e->getMessage();
}

// Libellés provisoires (seront remplacés par la gestion multilingue)
$accommodationLabels = [1 => 'Tente', 2 => '4x4', 3 => 'Camping-car'];

// Échappe une valeur avant affichage (protection XSS)
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">

    <title>Accueil | site4night</title>
</head>

<body>
    <main class="container">
        <h1>site4night</h1>
        <p>Trouvez où passer la nuit en tente, en 4x4 ou en camping-car.</p>

        <?php if ($error !== null) { ?>
            <p><mark><?= e($error) ?></mark></p>
        <?php } else { ?>
            <h2><?= count($spots) ?> lieux publiés</h2>

            <table>
                <thead>
                    <tr>
                        <th>Lieu</th>
                        <th>Canton</th>
                        <th>Altitude</th>
                        <th>Hébergement</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($spots as $spot) { ?>
                        <tr>
                            <td><?= e($spot->getName()) ?></td>
                            <td><?= e($cantons[$spot->getCantonCode()] ?? $spot->getCantonCode()) ?></td>
                            <td><?= $spot->getAltitude() !== null ? $spot->getAltitude() . ' m' : '–' ?></td>
                            <td><?= e(implode(', ', array_map(fn (int $id) => $accommodationLabels[$id] ?? '?', $spot->getAccommodationTypeIds()))) ?></td>
                            <td><?= $spot->getAverageRating() !== null ? $spot->getAverageRating() . ' / 5 (' . $spot->getReviewCount() . ')' : '–' ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } ?>

        <footer>
            <small>Projet réalisé dans le cadre du cours ProgServ2 à la <a href="https://heig-vd.ch">HEIG-VD</a>.</small>
        </footer>
    </main>
</body>

</html>
