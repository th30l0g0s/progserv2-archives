<?php

namespace Users;

use InvalidArgumentException;

/**
 * Un compte utilisateur.
 *
 * Le mot de passe n'est jamais stocké dans cet objet : seul UsersManager
 * manipule le hash (password_hash / password_verify).
 */
class User implements UserInterface
{
    const ROLE_USER = 'user';
    const ROLE_ADMIN = 'admin';
    const ROLES = [self::ROLE_USER, self::ROLE_ADMIN];

    const LANGUAGES = ['fr', 'en'];

    const USERNAME_PATTERN = '/^[a-zA-Z0-9_]{3,50}$/';
    const PASSWORD_MIN_LENGTH = 8;

    private ?int $id = null;
    private string $username;
    private string $email;
    private string $role = self::ROLE_USER;
    private string $language = 'fr';
    private bool $isActive = true;
    private bool $emailConfirmed = false;
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct(string $username, string $email, string $language = 'fr')
    {
        $this->setUsername($username);
        $this->setEmail($email);
        $this->setLanguage($language);
    }

    public static function fromDatabaseRow(array $row): self
    {
        $user = new self($row['username'], $row['email'], $row['language']);

        $user->id = (int) $row['id'];
        $user->role = $row['role'];
        $user->isActive = (bool) $row['is_active'];
        $user->emailConfirmed = (bool) $row['email_confirmed'];
        $user->createdAt = new \DateTimeImmutable($row['created_at']);

        return $user;
    }

    /**
     * Vérifie la robustesse d'un mot de passe en clair (avant hachage).
     * Statique car le mot de passe n'est pas une propriété de l'objet.
     */
    public static function validatePassword(string $password): void
    {
        if (mb_strlen($password) < self::PASSWORD_MIN_LENGTH) {
            throw new InvalidArgumentException('Le mot de passe doit contenir au moins ' . self::PASSWORD_MIN_LENGTH . ' caractères.');
        }
    }

    // --- Getters ---

    public function getId(): ?int { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function getEmail(): string { return $this->email; }
    public function getRole(): string { return $this->role; }
    public function getLanguage(): string { return $this->language; }
    public function isActive(): bool { return $this->isActive; }
    public function isEmailConfirmed(): bool { return $this->emailConfirmed; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    // --- Setters avec validation ---

    public function setUsername(string $username): void
    {
        $username = trim($username);
        if (!preg_match(self::USERNAME_PATTERN, $username)) {
            throw new InvalidArgumentException("Le nom d'utilisateur doit contenir 3 à 50 caractères (lettres, chiffres, _).");
        }
        $this->username = $username;
    }

    public function setEmail(string $email): void
    {
        $email = trim($email);
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException("L'adresse e-mail n'est pas valide.");
        }
        $this->email = mb_strtolower($email);
    }

    public function setLanguage(string $language): void
    {
        if (!in_array($language, self::LANGUAGES, true)) {
            throw new InvalidArgumentException('Langue non prise en charge.');
        }
        $this->language = $language;
    }

    public function setRole(string $role): void
    {
        if (!in_array($role, self::ROLES, true)) {
            throw new InvalidArgumentException('Rôle invalide.');
        }
        $this->role = $role;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }
}
