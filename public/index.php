<?php
require_once __DIR__ . '/../src/utils/autoloader.php';

use References\ReferencesManager;
use Spots\SpotsManager;
use Ui\Html;
use Ui\Labels;
use Ui\View;

$error = null;

try {
    $spotsManager = new SpotsManager();
    $referencesManager = new ReferencesManager();

    $cantons = $referencesManager->getCantons();
    $accommodationTypes = $referencesManager->getAccommodationTypes();

    // Les 3 lieux les mieux notés (les lieux sans avis passent après)
    $spots = $spotsManager->getPublishedSpots();
    usort($spots, fn ($a, $b) => ($b->getAverageRating() ?? 0) <=> ($a->getAverageRating() ?? 0));
    $bestSpots = array_slice($spots, 0, 3);
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    $error = Config::isProduction() ? 'Les lieux ne peuvent pas être affichés pour le moment.' : $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">

<?php View::render('head', ['title' => 'Accueil']); ?>

<body>
    <?php View::render('header', ['current' => 'home']); ?>

    <main class="container">
        <section class="hero">
            <h1>Trouvez où passer la nuit, en pleine nature.</h1>
            <p>
                site4night réunit une communauté de voyageurs qui référencent, recherchent
                et évaluent des lieux où dormir en tente, en 4x4 aménagé ou en camping-car, partout en Suisse.
            </p>

            <?php if ($error === null) { ?>
                <!-- Accès rapide à la recherche : envoie les filtres à la page des lieux -->
                <form action="/spots/" method="get" role="search">
                    <select name="accommodation" aria-label="Type d'hébergement">
                        <option value="">Tous les hébergements</option>
                        <?php foreach ($accommodationTypes as $id => $code) { ?>
                            <option value="<?= $id ?>"><?= Html::escape(Labels::accommodationType($code)) ?></option>
                        <?php } ?>
                    </select>
                    <select name="canton" aria-label="Canton">
                        <option value="">Tous les cantons</option>
                        <?php foreach ($cantons as $code => $name) { ?>
                            <option value="<?= Html::escape($code) ?>"><?= Html::escape($name) ?></option>
                        <?php } ?>
                    </select>
                    <button type="submit">Rechercher</button>
                </form>
            <?php } ?>
        </section>

        <?php if ($error !== null) { ?>
            <p><mark><?= Html::escape($error) ?></mark></p>
        <?php } else { ?>
            <h2>Les lieux les mieux notés</h2>

            <div class="spot-grid">
                <?php foreach ($bestSpots as $spot) {
                    View::render('spot-card', [
                        'spot' => $spot,
                        'cantons' => $cantons,
                        'accommodationTypes' => $accommodationTypes,
                    ]);
                } ?>
            </div>

            <p><a href="/spots/" role="button" class="outline">Voir les <?= count($spots) ?> lieux</a></p>
        <?php } ?>

        <h2>Comment ça marche ?</h2>
        <div class="grid">
            <article>
                <h3>Recherchez</h3>
                <p>Filtrez par type d'hébergement, canton et équipements : eau, toilettes, feu, électricité.</p>
            </article>
            <article>
                <h3>Proposez</h3>
                <p>Partagez vos lieux. Un administrateur les vérifie avant publication.</p>
            </article>
            <article>
                <h3>Évaluez</h3>
                <p>Laissez un avis après votre nuit pour aider les prochains voyageurs.</p>
            </article>
        </div>
    </main>

    <?php View::render('footer'); ?>
</body>

</html>
