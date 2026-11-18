<?php
require_once __DIR__ . '/../../src/utils/autoloader.php';

use References\ReferencesManager;
use Reviews\ReviewsManager;
use Spots\SpotsManager;
use Ui\Html;
use Ui\Labels;
use Ui\View;

$error = null;
$spot = null;

// L'identifiant doit être un entier positif, sinon : page introuvable
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

try {
    if ($id !== false) {
        $spot = (new SpotsManager())->getSpotById($id);
    }

    // Seuls les lieux publiés sont visibles publiquement.
    // Un lieu en attente ou refusé répond comme un lieu inexistant (404).
    if ($spot === null || !$spot->isPublished()) {
        http_response_code(404);
        $error = "Ce lieu n'existe pas ou n'est pas encore publié.";
        $spot = null;
    } else {
        $referencesManager = new ReferencesManager();
        $cantons = $referencesManager->getCantons();
        $accessTypes = $referencesManager->getAccessTypes();
        $accommodationTypes = $referencesManager->getAccommodationTypes();
        $amenities = $referencesManager->getAmenities();

        $reviews = (new ReviewsManager())->getReviewsBySpot($spot->getId());
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    $error = Config::isProduction() ? "Ce lieu ne peut pas être affiché pour le moment." : $e->getMessage();
    $spot = null;
}

// Lien vers OpenStreetMap, avec un marqueur sur le lieu
$mapUrl = $spot !== null
    ? sprintf('https://www.openstreetmap.org/?mlat=%1$F&mlon=%2$F#map=15/%1$F/%2$F', $spot->getLatitude(), $spot->getLongitude())
    : null;
?>
<!DOCTYPE html>
<html lang="fr">

<?php View::render('head', [
    'title' => $spot !== null ? $spot->getName() : 'Lieu introuvable',
    'description' => $spot !== null ? mb_substr($spot->getDescription(), 0, 150) : null,
]); ?>

<body>
    <?php View::render('header', ['current' => 'spots']); ?>

    <main class="container">
        <nav aria-label="breadcrumb">
            <ul>
                <li><a href="/">Accueil</a></li>
                <li><a href="/spots/">Lieux</a></li>
                <li><?= $spot !== null ? Html::escape($spot->getName()) : 'Introuvable' ?></li>
            </ul>
        </nav>

        <?php if ($spot === null) { ?>
            <h1>Lieu introuvable</h1>
            <p><?= Html::escape($error) ?></p>
            <p><a href="/spots/" role="button">Voir tous les lieux</a></p>
        <?php } else { ?>
            <hgroup>
                <h1><?= Html::escape($spot->getName()) ?></h1>
                <p>
                    <?= Html::escape($cantons[$spot->getCantonCode()] ?? $spot->getCantonCode()) ?>
                    <?php if ($spot->getAltitude() !== null) { ?> · <?= $spot->getAltitude() ?> m<?php } ?>
                    <?php if ($spot->getAverageRating() !== null) { ?>
                        · <span class="stars" aria-hidden="true"><?= Html::stars($spot->getAverageRating()) ?></span>
                        <?= $spot->getAverageRating() ?> / 5 (<?= $spot->getReviewCount() ?> avis)
                    <?php } ?>
                </p>
            </hgroup>

            <p class="legal-notice">
                <strong>Attention :</strong> site4night ne garantit pas que le séjour est autorisé.
                Vérifiez la réglementation cantonale et communale, les zones protégées et les propriétés privées avant de vous installer.
            </p>

            <div class="grid">
                <section>
                    <h2>Description</h2>
                    <!-- nl2br après l'échappement : conserve les retours à la ligne sans autoriser de HTML -->
                    <p><?= nl2br(Html::escape($spot->getDescription())) ?></p>
                </section>

                <article>
                    <h2>Informations</h2>
                    <dl>
                        <dt><strong>Hébergement</strong></dt>
                        <dd>
                            <ul class="tags">
                                <?php foreach (Labels::fromIds($spot->getAccommodationTypeIds(), $accommodationTypes, [Labels::class, 'accommodationType']) as $label) { ?>
                                    <li><?= Html::escape($label) ?></li>
                                <?php } ?>
                            </ul>
                        </dd>

                        <dt><strong>Équipements</strong></dt>
                        <dd>
                            <?php if ($spot->getAmenityIds() === []) { ?>
                                Aucun
                            <?php } else { ?>
                                <ul class="tags">
                                    <?php foreach (Labels::fromIds($spot->getAmenityIds(), $amenities, [Labels::class, 'amenity']) as $label) { ?>
                                        <li><?= Html::escape($label) ?></li>
                                    <?php } ?>
                                </ul>
                            <?php } ?>
                        </dd>

                        <dt><strong>Accès</strong></dt>
                        <dd><?= Html::escape(Labels::accessType($accessTypes[$spot->getAccessTypeId()] ?? '?')) ?></dd>

                        <dt><strong>Prix</strong></dt>
                        <dd><?= $spot->isFree() ? 'Gratuit' : 'Payant' ?></dd>

                        <dt><strong>Coordonnées GPS</strong></dt>
                        <dd>
                            <?= Html::escape(number_format($spot->getLatitude(), 5, '.', '')) ?>,
                            <?= Html::escape(number_format($spot->getLongitude(), 5, '.', '')) ?>
                            <br><a href="<?= Html::escape($mapUrl) ?>" target="_blank" rel="noopener">Voir sur la carte ↗</a>
                        </dd>
                    </dl>
                </article>
            </div>

            <section>
                <h2>Avis (<?= count($reviews) ?>)</h2>

                <?php if ($reviews === []) { ?>
                    <p>Aucun avis pour l'instant.</p>
                <?php } ?>

                <?php foreach ($reviews as $review) { ?>
                    <article>
                        <header>
                            <span class="stars" aria-label="<?= $review->getRating() ?> sur 5"><?= Html::stars($review->getRating()) ?></span>
                            <strong><?= Html::escape($review->getAuthorUsername()) ?></strong>
                            <small>· visite du <?= Html::date($review->getVisitDate()) ?></small>
                        </header>
                        <p><?= nl2br(Html::escape($review->getComment())) ?></p>
                    </article>
                <?php } ?>
            </section>
        <?php } ?>
    </main>

    <?php View::render('footer'); ?>
</body>

</html>
