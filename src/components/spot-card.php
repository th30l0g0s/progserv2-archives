<?php
/**
 * Carte résumant un lieu (liste, accueil).
 * Variables : $spot (Spots\Spot), $cantons (code => nom), $accommodationTypes (id => code)
 */
use Ui\Html;
use Ui\Labels;

$accommodations = Labels::fromIds($spot->getAccommodationTypeIds(), $accommodationTypes, [Labels::class, 'accommodationType']);
?>
<article class="spot-card">
    <header>
        <h3><a href="/spots/view.php?id=<?= $spot->getId() ?>"><?= Html::escape($spot->getName()) ?></a></h3>
        <small>
            <?= Html::escape($cantons[$spot->getCantonCode()] ?? $spot->getCantonCode()) ?>
            <?php if ($spot->getAltitude() !== null) { ?> · <?= $spot->getAltitude() ?> m<?php } ?>
            · <?= $spot->isFree() ? 'Gratuit' : 'Payant' ?>
        </small>
    </header>

    <ul class="tags">
        <?php foreach ($accommodations as $label) { ?>
            <li><?= Html::escape($label) ?></li>
        <?php } ?>
    </ul>

    <footer>
        <?php if ($spot->getAverageRating() !== null) { ?>
            <span class="stars" aria-hidden="true"><?= Html::stars($spot->getAverageRating()) ?></span>
            <?= $spot->getAverageRating() ?> / 5 (<?= $spot->getReviewCount() ?> avis)
        <?php } else { ?>
            <small>Pas encore d'avis</small>
        <?php } ?>
    </footer>
</article>
