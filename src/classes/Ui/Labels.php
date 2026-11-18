<?php

namespace Ui;

/**
 * Libellés affichés pour les codes stockés en base (tent, paved, water…).
 *
 * Centralisés ici pour être remplacés facilement par la gestion
 * multilingue (i18n) : les pages n'écrivent jamais ces textes en dur.
 */
class Labels
{
    const ACCOMMODATION_TYPES = [
        'tent' => 'Tente',
        '4x4' => '4x4',
        'campervan' => 'Camping-car',
    ];

    const ACCESS_TYPES = [
        'paved' => 'Route goudronnée',
        'track' => 'Piste',
        'trail' => 'Sentier',
    ];

    const AMENITIES = [
        'water' => 'Eau',
        'toilets' => 'Toilettes',
        'fire' => 'Feu autorisé',
        'electricity' => 'Électricité',
    ];

    public static function accommodationType(string $code): string
    {
        return self::ACCOMMODATION_TYPES[$code] ?? $code;
    }

    public static function accessType(string $code): string
    {
        return self::ACCESS_TYPES[$code] ?? $code;
    }

    public static function amenity(string $code): string
    {
        return self::AMENITIES[$code] ?? $code;
    }

    /**
     * Transforme une liste d'ids en libellés, à l'aide d'une table id => code
     * (fournie par ReferencesManager). Ex. [1, 3] -> ['Tente', 'Camping-car']
     */
    public static function fromIds(array $ids, array $codesById, callable $label): array
    {
        return array_map(fn (int $id) => $label($codesById[$id] ?? '?'), $ids);
    }
}
