<?php
require_once __DIR__ . '/../../src/utils/autoloader.php';

use References\ReferencesManager;
use Spots\SpotsManager;
use Ui\Html;
use Ui\Labels;
use Ui\View;

$error = null;
$spots = [];

// Valeurs du formulaire, réaffichées après la recherche
$selected = ['canton' => '', 'accommodation' => '', 'amenities' => [], 'price' => ''];

try {
    $spotsManager = new SpotsManager();
    $referencesManager = new ReferencesManager();

    $cantons = $referencesManager->getCantons();
    $accommodationTypes = $referencesManager->getAccommodationTypes();
    $amenities = $referencesManager->getAmenities();

    // --- Lecture et validation des filtres (GET) ---
    // Chaque valeur n'est gardée que si elle existe dans les listes de référence.
    // Une valeur inconnue ou manipulée est simplement ignorée.
    $filters = [];

    $canton = strtoupper(trim((string) ($_GET['canton'] ?? '')));
    if (isset($cantons[$canton])) {
        $filters['canton'] = $canton;
        $selected['canton'] = $canton;
    }

    $accommodation = filter_var($_GET['accommodation'] ?? null, FILTER_VALIDATE_INT);
    if ($accommodation !== false && isset($accommodationTypes[$accommodation])) {
        $filters['accommodation'] = $accommodation;
        $selected['accommodation'] = $accommodation;
    }

    $requestedAmenities = is_array($_GET['amenities'] ?? null) ? $_GET['amenities'] : [];
    foreach ($requestedAmenities as $amenityId) {
        $amenityId = filter_var($amenityId, FILTER_VALIDATE_INT);
        if ($amenityId !== false && isset($amenities[$amenityId])) {
            $selected['amenities'][] = $amenityId;
        }
    }
    if ($selected['amenities'] !== []) {
        $filters['amenities'] = array_unique($selected['amenities']);
    }

    $price = $_GET['price'] ?? '';
    if ($price === 'free' || $price === 'paid') {
        $filters['isFree'] = $price === 'free';
        $selected['price'] = $price;
    }

    $spots = $spotsManager->getPublishedSpots($filters);
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    $error = Config::isProduction() ? 'Les lieux ne peuvent pas être affichés pour le moment.' : $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">

<?php View::render('head', [
    'title' => 'Lieux',
    'description' => 'Rechercher un lieu où passer la nuit en tente, en 4x4 ou en camping-car en Suisse.',
]); ?>

<body>
    <?php View::render('header', ['current' => 'spots']); ?>

    <main class="container">
        <h1>Lieux où passer la nuit</h1>

        <?php if ($error !== null) { ?>
            <p><mark><?= Html::escape($error) ?></mark></p>
        <?php } else { ?>
            <details <?= $filters !== [] ? 'open' : '' ?>>
                <summary role="button" class="outline">Filtres<?= $filters !== [] ? ' (' . count($filters) . ' actifs)' : '' ?></summary>

                <form method="get" class="search-form" aria-label="Filtrer les lieux">
                    <div class="grid">
                        <label>
                            Type d'hébergement
                            <select name="accommodation">
                                <option value="">Tous</option>
                                <?php foreach ($accommodationTypes as $id => $code) { ?>
                                    <option value="<?= $id ?>" <?= $selected['accommodation'] === $id ? 'selected' : '' ?>>
                                        <?= Html::escape(Labels::accommodationType($code)) ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </label>

                        <label>
                            Canton
                            <select name="canton">
                                <option value="">Tous</option>
                                <?php foreach ($cantons as $code => $name) { ?>
                                    <option value="<?= Html::escape($code) ?>" <?= $selected['canton'] === $code ? 'selected' : '' ?>>
                                        <?= Html::escape($name) ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </label>

                        <label>
                            Prix
                            <select name="price">
                                <option value="">Tous</option>
                                <option value="free" <?= $selected['price'] === 'free' ? 'selected' : '' ?>>Gratuit</option>
                                <option value="paid" <?= $selected['price'] === 'paid' ? 'selected' : '' ?>>Payant</option>
                            </select>
                        </label>
                    </div>

                    <fieldset>
                        <legend>Équipements</legend>
                        <?php foreach ($amenities as $id => $code) { ?>
                            <label>
                                <input type="checkbox" name="amenities[]" value="<?= $id ?>"
                                    <?= in_array($id, $selected['amenities'], true) ? 'checked' : '' ?>>
                                <?= Html::escape(Labels::amenity($code)) ?>
                            </label>
                        <?php } ?>
                    </fieldset>

                    <div class="grid">
                        <button type="submit">Rechercher</button>
                        <a href="/spots/" role="button" class="secondary outline">Réinitialiser</a>
                    </div>
                </form>
            </details>

            <p><strong><?= count($spots) ?></strong> <?= count($spots) > 1 ? 'lieux trouvés' : 'lieu trouvé' ?></p>

            <?php if ($spots === []) { ?>
                <p>Aucun lieu ne correspond à ces critères. Essayez d'enlever un filtre.</p>
            <?php } else { ?>
                <div class="spot-grid">
                    <?php foreach ($spots as $spot) {
                        View::render('spot-card', [
                            'spot' => $spot,
                            'cantons' => $cantons,
                            'accommodationTypes' => $accommodationTypes,
                        ]);
                    } ?>
                </div>
            <?php } ?>
        <?php } ?>
    </main>

    <?php View::render('footer'); ?>
</body>

</html>
