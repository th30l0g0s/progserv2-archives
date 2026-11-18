<?php

namespace Spots;

use InvalidArgumentException;

/**
 * Un lieu où passer la nuit.
 *
 * Les setters valident chaque valeur et lèvent une InvalidArgumentException
 * si elle est incorrecte : un objet Spot est donc toujours dans un état valide
 * avant d'être enregistré (validation côté serveur).
 */
class Spot implements SpotInterface
{
    const STATUS_PENDING = 'pending';
    const STATUS_PUBLISHED = 'published';
    const STATUS_REFUSED = 'refused';
    const STATUSES = [self::STATUS_PENDING, self::STATUS_PUBLISHED, self::STATUS_REFUSED];

    const NAME_MAX_LENGTH = 100;
    const DESCRIPTION_MIN_LENGTH = 10;
    const REFUSAL_REASON_MAX_LENGTH = 500;
    const ALTITUDE_MAX = 4634; // Pointe Dufour, point culminant de la Suisse

    private ?int $id = null;
    private string $name;
    private string $description;
    private float $latitude;
    private float $longitude;
    private ?int $altitude = null;
    private bool $isFree = true;
    private string $status = self::STATUS_PENDING;
    private ?string $refusalReason = null;
    private ?\DateTimeImmutable $createdAt = null;
    private ?int $userId = null;
    private string $cantonCode;
    private int $accessTypeId;
    private array $accommodationTypeIds = [];
    private array $amenityIds = [];

    // Calculés à partir des avis, en lecture seule
    private ?float $averageRating = null;
    private int $reviewCount = 0;

    public function __construct(
        string $name,
        string $description,
        float $latitude,
        float $longitude,
        string $cantonCode,
        int $accessTypeId,
        array $accommodationTypeIds,
    ) {
        $this->setName($name);
        $this->setDescription($description);
        $this->setCoordinates($latitude, $longitude);
        $this->setCantonCode($cantonCode);
        $this->setAccessTypeId($accessTypeId);
        $this->setAccommodationTypeIds($accommodationTypeIds);
    }

    /**
     * Crée un Spot à partir d'une ligne de la base de données.
     * Les valeurs proviennent de la base : elles sont déjà valides.
     */
    public static function fromDatabaseRow(array $row): self
    {
        $spot = new self(
            $row['name'],
            $row['description'],
            (float) $row['latitude'],
            (float) $row['longitude'],
            $row['canton_code'],
            (int) $row['access_type_id'],
            self::splitIds($row['accommodation_type_ids'] ?? ''),
        );

        $spot->id = (int) $row['id'];
        $spot->altitude = $row['altitude'] !== null ? (int) $row['altitude'] : null;
        $spot->isFree = (bool) $row['is_free'];
        $spot->status = $row['status'];
        $spot->refusalReason = $row['refusal_reason'];
        $spot->createdAt = new \DateTimeImmutable($row['created_at']);
        $spot->userId = $row['user_id'] !== null ? (int) $row['user_id'] : null;
        $spot->amenityIds = self::splitIds($row['amenity_ids'] ?? '');
        $spot->averageRating = isset($row['average_rating']) ? round((float) $row['average_rating'], 1) : null;
        $spot->reviewCount = (int) ($row['review_count'] ?? 0);

        return $spot;
    }

    // Transforme "1,3" (résultat de GROUP_CONCAT) en [1, 3]
    private static function splitIds(?string $ids): array
    {
        if ($ids === null || $ids === '') {
            return [];
        }

        return array_map('intval', explode(',', $ids));
    }

    // --- Getters ---

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getDescription(): string { return $this->description; }
    public function getLatitude(): float { return $this->latitude; }
    public function getLongitude(): float { return $this->longitude; }
    public function getAltitude(): ?int { return $this->altitude; }
    public function isFree(): bool { return $this->isFree; }
    public function getStatus(): string { return $this->status; }
    public function getRefusalReason(): ?string { return $this->refusalReason; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getUserId(): ?int { return $this->userId; }
    public function getCantonCode(): string { return $this->cantonCode; }
    public function getAccessTypeId(): int { return $this->accessTypeId; }
    public function getAccommodationTypeIds(): array { return $this->accommodationTypeIds; }
    public function getAmenityIds(): array { return $this->amenityIds; }
    public function getAverageRating(): ?float { return $this->averageRating; }
    public function getReviewCount(): int { return $this->reviewCount; }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    // --- Setters avec validation ---

    public function setName(string $name): void
    {
        $name = trim($name);
        if ($name === '' || mb_strlen($name) > self::NAME_MAX_LENGTH) {
            throw new InvalidArgumentException('Le nom doit contenir entre 1 et ' . self::NAME_MAX_LENGTH . ' caractères.');
        }
        $this->name = $name;
    }

    public function setDescription(string $description): void
    {
        $description = trim($description);
        if (mb_strlen($description) < self::DESCRIPTION_MIN_LENGTH) {
            throw new InvalidArgumentException('La description doit contenir au moins ' . self::DESCRIPTION_MIN_LENGTH . ' caractères.');
        }
        $this->description = $description;
    }

    public function setCoordinates(float $latitude, float $longitude): void
    {
        if ($latitude < -90 || $latitude > 90) {
            throw new InvalidArgumentException('La latitude doit être comprise entre -90 et 90.');
        }
        if ($longitude < -180 || $longitude > 180) {
            throw new InvalidArgumentException('La longitude doit être comprise entre -180 et 180.');
        }
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    public function setAltitude(?int $altitude): void
    {
        if ($altitude !== null && ($altitude < 0 || $altitude > self::ALTITUDE_MAX)) {
            throw new InvalidArgumentException("L'altitude doit être comprise entre 0 et " . self::ALTITUDE_MAX . ' m.');
        }
        $this->altitude = $altitude;
    }

    public function setIsFree(bool $isFree): void
    {
        $this->isFree = $isFree;
    }

    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }

    public function setCantonCode(string $cantonCode): void
    {
        $cantonCode = strtoupper(trim($cantonCode));
        if (!preg_match('/^[A-Z]{2}$/', $cantonCode)) {
            throw new InvalidArgumentException('Le code du canton doit contenir 2 lettres.');
        }
        $this->cantonCode = $cantonCode;
    }

    public function setAccessTypeId(int $accessTypeId): void
    {
        if ($accessTypeId <= 0) {
            throw new InvalidArgumentException("Le type d'accès est invalide.");
        }
        $this->accessTypeId = $accessTypeId;
    }

    public function setAccommodationTypeIds(array $ids): void
    {
        $ids = self::cleanIds($ids);
        if ($ids === []) {
            throw new InvalidArgumentException("Au moins un type d'hébergement doit être choisi.");
        }
        $this->accommodationTypeIds = $ids;
    }

    public function setAmenityIds(array $ids): void
    {
        $this->amenityIds = self::cleanIds($ids);
    }

    // Garde uniquement des entiers positifs, sans doublon
    private static function cleanIds(array $ids): array
    {
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, fn (int $id) => $id > 0);

        return array_values(array_unique($ids));
    }
}
