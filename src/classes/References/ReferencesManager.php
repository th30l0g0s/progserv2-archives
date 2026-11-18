<?php

namespace References;

use Database;
use DatabaseInterface;

/**
 * Données de référence (listes fixes) : cantons, types d'accès,
 * types d'hébergement et équipements.
 * Servent à remplir les listes des formulaires et à afficher les fiches.
 */
class ReferencesManager
{
    private DatabaseInterface $database;

    public function __construct(?DatabaseInterface $database = null)
    {
        $this->database = $database ?? new Database();
    }

    /** @return array<string, string> code => nom, ex. ['VS' => 'Valais'] */
    public function getCantons(): array
    {
        return $this->fetchPairs('SELECT code, name FROM cantons ORDER BY name');
    }

    /** @return array<int, string> id => code, ex. [1 => 'paved'] */
    public function getAccessTypes(): array
    {
        return $this->fetchPairs('SELECT id, code FROM access_types ORDER BY id');
    }

    /** @return array<int, string> id => code, ex. [1 => 'tent'] */
    public function getAccommodationTypes(): array
    {
        return $this->fetchPairs('SELECT id, code FROM accommodation_types ORDER BY id');
    }

    /** @return array<int, string> id => code, ex. [1 => 'water'] */
    public function getAmenities(): array
    {
        return $this->fetchPairs('SELECT id, code FROM amenities ORDER BY id');
    }

    // Requêtes sans paramètre : aucune saisie utilisateur, query() suffit
    private function fetchPairs(string $sql): array
    {
        return $this->database->getPdo()->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);
    }
}
