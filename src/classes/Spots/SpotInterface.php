<?php

namespace Spots;

interface SpotInterface
{
    public function getId(): ?int;
    public function getName(): string;
    public function getDescription(): string;
    public function getLatitude(): float;
    public function getLongitude(): float;
    public function getAltitude(): ?int;
    public function isFree(): bool;
    public function getStatus(): string;
    public function getRefusalReason(): ?string;
    public function getCreatedAt(): ?\DateTimeImmutable;
    public function getUserId(): ?int;
    public function getCantonCode(): string;
    public function getAccessTypeId(): int;
    public function getAccommodationTypeIds(): array;
    public function getAmenityIds(): array;
    public function getAverageRating(): ?float;
    public function getReviewCount(): int;
}
