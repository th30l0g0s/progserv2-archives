<?php

namespace Spots;

use Database;
use DatabaseInterface;
use InvalidArgumentException;
use PDO;

/**
 * Accès aux lieux dans la base de données.
 * Toutes les requêtes sont préparées : aucune valeur saisie n'est concaténée au SQL.
 */
class SpotsManager implements SpotsManagerInterface
{
    // Requête de base : un lieu + ses types d'hébergement, équipements et note moyenne.
    // Les sous-requêtes évitent de multiplier les lignes (une ligne = un lieu).
    private const SELECT_SPOTS = "
        SELECT s.*,
            (SELECT GROUP_CONCAT(sat.accommodation_type_id ORDER BY sat.accommodation_type_id)
               FROM spot_accommodation_types sat WHERE sat.spot_id = s.id) AS accommodation_type_ids,
            (SELECT GROUP_CONCAT(sa.amenity_id ORDER BY sa.amenity_id)
               FROM spot_amenities sa WHERE sa.spot_id = s.id) AS amenity_ids,
            (SELECT AVG(r.rating) FROM reviews r WHERE r.spot_id = s.id) AS average_rating,
            (SELECT COUNT(*) FROM reviews r WHERE r.spot_id = s.id) AS review_count
        FROM spots s
    ";

    private DatabaseInterface $database;

    public function __construct(?DatabaseInterface $database = null)
    {
        $this->database = $database ?? new Database();
    }

    /**
     * Lieux publiés, avec filtres optionnels :
     * - 'canton'        => code du canton (ex. 'VS')
     * - 'accommodation' => id du type d'hébergement
     * - 'amenities'     => tableau d'ids d'équipements (le lieu doit tous les avoir)
     * - 'isFree'        => true / false
     */
    public function getPublishedSpots(array $filters = []): array
    {
        $conditions = ['s.status = :status'];
        $params = [':status' => Spot::STATUS_PUBLISHED];

        if (!empty($filters['canton'])) {
            $conditions[] = 's.canton_code = :canton';
            $params[':canton'] = strtoupper((string) $filters['canton']);
        }

        if (!empty($filters['accommodation'])) {
            $conditions[] = 'EXISTS (SELECT 1 FROM spot_accommodation_types f
                WHERE f.spot_id = s.id AND f.accommodation_type_id = :accommodation)';
            $params[':accommodation'] = (int) $filters['accommodation'];
        }

        // Un paramètre nommé par équipement (:amenity0, :amenity1…) : jamais de valeur dans le SQL
        foreach (array_values($filters['amenities'] ?? []) as $index => $amenityId) {
            $conditions[] = "EXISTS (SELECT 1 FROM spot_amenities a$index
                WHERE a$index.spot_id = s.id AND a$index.amenity_id = :amenity$index)";
            $params[":amenity$index"] = (int) $amenityId;
        }

        if (isset($filters['isFree'])) {
            $conditions[] = 's.is_free = :isFree';
            $params[':isFree'] = $filters['isFree'] ? 1 : 0;
        }

        $sql = self::SELECT_SPOTS . ' WHERE ' . implode(' AND ', $conditions) . ' ORDER BY s.name';

        return $this->fetchSpots($sql, $params);
    }

    public function getSpotById(int $id): ?Spot
    {
        $spots = $this->fetchSpots(self::SELECT_SPOTS . ' WHERE s.id = :id', [':id' => $id]);

        return $spots[0] ?? null;
    }

    public function getSpotsByUser(int $userId): array
    {
        $sql = self::SELECT_SPOTS . ' WHERE s.user_id = :userId ORDER BY s.created_at DESC';

        return $this->fetchSpots($sql, [':userId' => $userId]);
    }

    public function getPendingSpots(): array
    {
        $sql = self::SELECT_SPOTS . ' WHERE s.status = :status ORDER BY s.created_at';

        return $this->fetchSpots($sql, [':status' => Spot::STATUS_PENDING]);
    }

    /**
     * Enregistre un nouveau lieu (statut « en attente ») et ses liens.
     * Retourne l'identifiant du lieu créé.
     */
    public function addSpot(Spot $spot): int
    {
        $pdo = $this->database->getPdo();

        // Transaction : le lieu et ses liens sont enregistrés ensemble, ou pas du tout
        $pdo->beginTransaction();

        try {
            $sql = "INSERT INTO spots
                    (name, description, latitude, longitude, altitude, is_free, status, user_id, canton_code, access_type_id)
                VALUES
                    (:name, :description, :latitude, :longitude, :altitude, :isFree, :status, :userId, :cantonCode, :accessTypeId)";

            $stmt = $pdo->prepare($sql);
            $this->bindSpotValues($stmt, $spot);
            $stmt->bindValue(':status', Spot::STATUS_PENDING);
            $stmt->bindValue(':userId', $spot->getUserId(), $spot->getUserId() === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->execute();

            $spotId = (int) $pdo->lastInsertId();
            $this->saveLinks($spotId, $spot);

            $pdo->commit();

            return $spotId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Met à jour un lieu existant et ses liens.
     * Un lieu modifié repasse « en attente » pour être revalidé par un administrateur.
     */
    public function updateSpot(Spot $spot): bool
    {
        if ($spot->getId() === null) {
            throw new InvalidArgumentException("Impossible de modifier un lieu sans identifiant.");
        }

        $pdo = $this->database->getPdo();
        $pdo->beginTransaction();

        try {
            $sql = "UPDATE spots SET
                    name = :name, description = :description, latitude = :latitude, longitude = :longitude,
                    altitude = :altitude, is_free = :isFree, canton_code = :cantonCode, access_type_id = :accessTypeId,
                    status = :status, refusal_reason = NULL
                WHERE id = :id";

            $stmt = $pdo->prepare($sql);
            $this->bindSpotValues($stmt, $spot);
            $stmt->bindValue(':status', Spot::STATUS_PENDING);
            $stmt->bindValue(':id', $spot->getId(), PDO::PARAM_INT);
            $stmt->execute();

            // Les liens sont remplacés : suppression puis réinsertion
            foreach (['spot_accommodation_types', 'spot_amenities'] as $table) {
                $delete = $pdo->prepare("DELETE FROM $table WHERE spot_id = :id");
                $delete->bindValue(':id', $spot->getId(), PDO::PARAM_INT);
                $delete->execute();
            }
            $this->saveLinks($spot->getId(), $spot);

            $pdo->commit();

            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function removeSpot(int $id): bool
    {
        // Les liens, avis et favoris sont supprimés par ON DELETE CASCADE
        $stmt = $this->database->getPdo()->prepare('DELETE FROM spots WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() === 1;
    }

    public function publishSpot(int $id): bool
    {
        return $this->changeStatus($id, Spot::STATUS_PUBLISHED, null);
    }

    public function refuseSpot(int $id, string $reason): bool
    {
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) > Spot::REFUSAL_REASON_MAX_LENGTH) {
            throw new InvalidArgumentException('Le motif de refus doit contenir entre 1 et ' . Spot::REFUSAL_REASON_MAX_LENGTH . ' caractères.');
        }

        return $this->changeStatus($id, Spot::STATUS_REFUSED, $reason);
    }

    // --- Méthodes internes ---

    private function changeStatus(int $id, string $status, ?string $reason): bool
    {
        $sql = 'UPDATE spots SET status = :status, refusal_reason = :reason WHERE id = :id';
        $stmt = $this->database->getPdo()->prepare($sql);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':reason', $reason, $reason === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() === 1;
    }

    private function fetchSpots(string $sql, array $params): array
    {
        $stmt = $this->database->getPdo()->prepare($sql);
        $stmt->execute($params);

        return array_map(fn (array $row) => Spot::fromDatabaseRow($row), $stmt->fetchAll());
    }

    // Valeurs communes à l'INSERT et à l'UPDATE
    private function bindSpotValues(\PDOStatement $stmt, Spot $spot): void
    {
        $stmt->bindValue(':name', $spot->getName());
        $stmt->bindValue(':description', $spot->getDescription());
        $stmt->bindValue(':latitude', (string) $spot->getLatitude());
        $stmt->bindValue(':longitude', (string) $spot->getLongitude());
        $stmt->bindValue(':altitude', $spot->getAltitude(), $spot->getAltitude() === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':isFree', $spot->isFree(), PDO::PARAM_BOOL);
        $stmt->bindValue(':cantonCode', $spot->getCantonCode());
        $stmt->bindValue(':accessTypeId', $spot->getAccessTypeId(), PDO::PARAM_INT);
    }

    // Enregistre les types d'hébergement et équipements d'un lieu (tables de liaison N:N)
    private function saveLinks(int $spotId, Spot $spot): void
    {
        $pdo = $this->database->getPdo();

        $stmt = $pdo->prepare('INSERT INTO spot_accommodation_types (spot_id, accommodation_type_id) VALUES (:spotId, :typeId)');
        foreach ($spot->getAccommodationTypeIds() as $typeId) {
            $stmt->execute([':spotId' => $spotId, ':typeId' => $typeId]);
        }

        $stmt = $pdo->prepare('INSERT INTO spot_amenities (spot_id, amenity_id) VALUES (:spotId, :amenityId)');
        foreach ($spot->getAmenityIds() as $amenityId) {
            $stmt->execute([':spotId' => $spotId, ':amenityId' => $amenityId]);
        }
    }
}
