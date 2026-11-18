<?php
/**
 * Balise <head> commune.
 * Variables : $title (titre de la page), $description (optionnelle)
 */
use Ui\Html;
?>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
    <link rel="stylesheet" href="/css/styles.css">

    <title><?= Html::escape(isset($title) ? "$title | site4night" : 'site4night') ?></title>
    <meta name="description" content="<?= Html::escape($description ?? 'site4night : trouvez où passer la nuit en tente, en 4x4 ou en camping-car en Suisse.') ?>">
</head>
