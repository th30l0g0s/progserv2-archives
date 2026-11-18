<?php

namespace Reviews;

use Database;
use DatabaseInterface;
use PDO;

/**
 * Accès aux avis. Pour l'instant : lecture (pages publiques).
 * L'ajout, la modification et la suppression viendront avec les pages privées.
 */
class ReviewsManager implements ReviewsManagerInterface
{
    private DatabaseInterface $database;

    public function __construct(?DatabaseInterface $database = null)
    {
        $this->database = $database ?? new Database();
    }

    /** Avis d'un lieu, du plus récent au plus ancien, avec le nom de l'auteur */
    public function getReviewsBySpot(int $spotId): array
    {
        $sql = 'SELECT r.*, u.username
                FROM reviews r
                JOIN users u ON u.id = r.user_id
                WHERE r.spot_id = :spotId
                ORDER BY r.created_at DESC';

        $stmt = $this->database->getPdo()->prepare($sql);
        $stmt->bindValue(':spotId', $spotId, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(fn (array $row) => Review::fromDatabaseRow($row), $stmt->fetchAll());
    }
}
