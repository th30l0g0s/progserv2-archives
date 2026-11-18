<?php

namespace Ui;

/**
 * Fonctions d'aide pour produire du HTML sûr.
 */
class Html
{
    /**
     * Échappe une valeur avant de l'afficher (protection XSS).
     * À utiliser pour TOUTE donnée venant de la base ou de l'utilisateur.
     */
    public static function escape(string|int|float|null $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /** Note moyenne sous forme d'étoiles, ex. 3.7 -> "★★★★☆" (arrondi) */
    public static function stars(?float $rating): string
    {
        if ($rating === null) {
            return '';
        }

        $full = (int) round($rating);

        return str_repeat('★', $full) . str_repeat('☆', 5 - $full);
    }

    /** Date au format suisse, ex. 18.11.2026 */
    public static function date(?\DateTimeInterface $date): string
    {
        return $date?->format('d.m.Y') ?? '';
    }
}
