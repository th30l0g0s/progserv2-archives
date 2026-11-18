<?php

namespace Reviews;

use InvalidArgumentException;

/**
 * Avis d'un utilisateur sur un lieu (un seul avis par utilisateur et par lieu).
 */
class Review implements ReviewInterface
{
    const RATING_MIN = 1;
    const RATING_MAX = 5;
    const COMMENT_MIN_LENGTH = 10;
    const COMMENT_MAX_LENGTH = 2000;

    private ?int $id = null;
    private int $rating;
    private string $comment;
    private \DateTimeImmutable $visitDate;
    private ?\DateTimeImmutable $createdAt = null;
    private int $userId;
    private int $spotId;

    // Nom de l'auteur, lu avec une jointure sur users (affichage uniquement)
    private ?string $authorUsername = null;

    public function __construct(int $rating, string $comment, \DateTimeImmutable $visitDate, int $userId, int $spotId)
    {
        $this->setRating($rating);
        $this->setComment($comment);
        $this->setVisitDate($visitDate);
        $this->userId = $userId;
        $this->spotId = $spotId;
    }

    public static function fromDatabaseRow(array $row): self
    {
        $review = new self(
            (int) $row['rating'],
            $row['comment'],
            new \DateTimeImmutable($row['visit_date']),
            (int) $row['user_id'],
            (int) $row['spot_id'],
        );

        $review->id = (int) $row['id'];
        $review->createdAt = new \DateTimeImmutable($row['created_at']);
        $review->authorUsername = $row['username'] ?? null;

        return $review;
    }

    // --- Getters ---

    public function getId(): ?int { return $this->id; }
    public function getRating(): int { return $this->rating; }
    public function getComment(): string { return $this->comment; }
    public function getVisitDate(): \DateTimeImmutable { return $this->visitDate; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getUserId(): int { return $this->userId; }
    public function getSpotId(): int { return $this->spotId; }
    public function getAuthorUsername(): ?string { return $this->authorUsername; }

    // --- Setters avec validation ---

    public function setRating(int $rating): void
    {
        if ($rating < self::RATING_MIN || $rating > self::RATING_MAX) {
            throw new InvalidArgumentException('La note doit être comprise entre ' . self::RATING_MIN . ' et ' . self::RATING_MAX . '.');
        }
        $this->rating = $rating;
    }

    public function setComment(string $comment): void
    {
        $comment = trim($comment);
        $length = mb_strlen($comment);
        if ($length < self::COMMENT_MIN_LENGTH || $length > self::COMMENT_MAX_LENGTH) {
            throw new InvalidArgumentException('Le commentaire doit contenir entre ' . self::COMMENT_MIN_LENGTH . ' et ' . self::COMMENT_MAX_LENGTH . ' caractères.');
        }
        $this->comment = $comment;
    }

    public function setVisitDate(\DateTimeImmutable $visitDate): void
    {
        if ($visitDate > new \DateTimeImmutable('today')) {
            throw new InvalidArgumentException('La date de visite ne peut pas être dans le futur.');
        }
        $this->visitDate = $visitDate;
    }
}
