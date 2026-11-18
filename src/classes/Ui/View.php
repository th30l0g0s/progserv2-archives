<?php

namespace Ui;

/**
 * Affiche les parties d'interface réutilisables (src/components/).
 *
 * Exemple : View::render('header');
 *           View::render('spot-card', ['spot' => $spot, 'cantons' => $cantons]);
 *
 * Les clés du tableau deviennent des variables dans le composant.
 */
class View
{
    const COMPONENTS_DIRECTORY = __DIR__ . '/../../components/';

    public static function render(string $component, array $data = []): void
    {
        // Seuls les noms simples sont acceptés (pas de ../), le nom ne vient jamais de l'utilisateur
        if (!preg_match('/^[a-z0-9-]+$/', $component)) {
            throw new \InvalidArgumentException("Nom de composant invalide : $component");
        }

        $file = self::COMPONENTS_DIRECTORY . $component . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("Composant introuvable : $component");
        }

        // Rend les données disponibles comme variables locales du composant
        extract($data, EXTR_SKIP);

        require $file;
    }
}
