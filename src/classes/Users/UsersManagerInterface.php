<?php

namespace Users;

interface UsersManagerInterface
{
    public function getUserById(int $id): ?User;
    public function getUserByEmail(string $email): ?User;
    public function getUsers(): array;
    public function addUser(User $user, string $password): int;
    public function updateUser(User $user): bool;
    public function updatePassword(int $userId, string $password): bool;
    public function verifyCredentials(string $email, string $password): ?User;
}
