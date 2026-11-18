<?php

namespace Users;

interface UserInterface
{
    public function getId(): ?int;
    public function getUsername(): string;
    public function getEmail(): string;
    public function getRole(): string;
    public function getLanguage(): string;
    public function isActive(): bool;
    public function isEmailConfirmed(): bool;
    public function getCreatedAt(): ?\DateTimeImmutable;
    public function isAdmin(): bool;
}
