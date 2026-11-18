<?php

namespace Reviews;

interface ReviewInterface
{
    public function getId(): ?int;
    public function getRating(): int;
    public function getComment(): string;
    public function getVisitDate(): \DateTimeImmutable;
    public function getCreatedAt(): ?\DateTimeImmutable;
    public function getUserId(): int;
    public function getSpotId(): int;
    public function getAuthorUsername(): ?string;
}
