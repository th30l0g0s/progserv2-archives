<?php

namespace Spots;

interface SpotsManagerInterface
{
    public function getPublishedSpots(array $filters = []): array;
    public function getSpotById(int $id): ?Spot;
    public function getSpotsByUser(int $userId): array;
    public function getPendingSpots(): array;
    public function addSpot(Spot $spot): int;
    public function updateSpot(Spot $spot): bool;
    public function removeSpot(int $id): bool;
    public function publishSpot(int $id): bool;
    public function refuseSpot(int $id, string $reason): bool;
}
