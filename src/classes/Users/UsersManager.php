<?php

namespace Users;

use Database;
use DatabaseInterface;
use InvalidArgumentException;
use PDO;
use PDOException;

class UsersManager implements UsersManagerInterface
{
    // Code d'erreur MySQL/MariaDB pour une contrainte UNIQUE violée
    private const DUPLICATE_ENTRY = 1062;

    private DatabaseInterface $database;

    public function __construct(?DatabaseInterface $database = null)
    {
        $this->database = $database ?? new Database();
    }

    public function getUserById(int $id): ?User
    {
        return $this->fetchOne('SELECT * FROM users WHERE id = :value', $id);
    }

    public function getUserByEmail(string $email): ?User
    {
        return $this->fetchOne('SELECT * FROM users WHERE email = :value', mb_strtolower(trim($email)));
    }

    public function getUsers(): array
    {
        $stmt = $this->database->getPdo()->query('SELECT * FROM users ORDER BY username');

        return array_map(fn (array $row) => User::fromDatabaseRow($row), $stmt->fetchAll());
    }

    /**
     * Crée un compte. Le mot de passe est haché avec password_hash()
     * (algorithme par défaut de PHP, avec sel aléatoire intégré).
     * Retourne l'identifiant du compte créé.
     */
    public function addUser(User $user, string $password): int
    {
        User::validatePassword($password);

        $sql = 'INSERT INTO users (username, email, password_hash, role, language)
                VALUES (:username, :email, :passwordHash, :role, :language)';

        $stmt = $this->database->getPdo()->prepare($sql);
        $stmt->bindValue(':username', $user->getUsername());
        $stmt->bindValue(':email', $user->getEmail());
        $stmt->bindValue(':passwordHash', password_hash($password, PASSWORD_DEFAULT));
        $stmt->bindValue(':role', $user->getRole());
        $stmt->bindValue(':language', $user->getLanguage());

        $this->executeCheckingDuplicates($stmt);

        return (int) $this->database->getPdo()->lastInsertId();
    }

    public function updateUser(User $user): bool
    {
        if ($user->getId() === null) {
            throw new InvalidArgumentException("Impossible de modifier un compte sans identifiant.");
        }

        $sql = 'UPDATE users SET username = :username, email = :email, role = :role,
                    language = :language, is_active = :isActive
                WHERE id = :id';

        $stmt = $this->database->getPdo()->prepare($sql);
        $stmt->bindValue(':username', $user->getUsername());
        $stmt->bindValue(':email', $user->getEmail());
        $stmt->bindValue(':role', $user->getRole());
        $stmt->bindValue(':language', $user->getLanguage());
        $stmt->bindValue(':isActive', $user->isActive(), PDO::PARAM_BOOL);
        $stmt->bindValue(':id', $user->getId(), PDO::PARAM_INT);

        $this->executeCheckingDuplicates($stmt);

        return true;
    }

    public function updatePassword(int $userId, string $password): bool
    {
        User::validatePassword($password);

        $stmt = $this->database->getPdo()->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $stmt->bindValue(':hash', password_hash($password, PASSWORD_DEFAULT));
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() === 1;
    }

    /**
     * Retourne l'utilisateur si l'e-mail et le mot de passe sont corrects
     * et que le compte est actif, sinon null.
     * Le message d'erreur affiché ne doit pas dire lequel des deux est faux.
     */
    public function verifyCredentials(string $email, string $password): ?User
    {
        $stmt = $this->database->getPdo()->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->bindValue(':email', mb_strtolower(trim($email)));
        $stmt->execute();
        $row = $stmt->fetch();

        if ($row === false || !password_verify($password, $row['password_hash']) || !$row['is_active']) {
            return null;
        }

        // Si PHP recommande un algorithme plus récent, le hash est mis à jour
        if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
            $this->updatePassword((int) $row['id'], $password);
        }

        return User::fromDatabaseRow($row);
    }

    // --- Méthodes internes ---

    private function fetchOne(string $sql, int|string $value): ?User
    {
        $stmt = $this->database->getPdo()->prepare($sql);
        $stmt->bindValue(':value', $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();

        return $row === false ? null : User::fromDatabaseRow($row);
    }

    // Traduit l'erreur SQL « doublon » en message compréhensible
    private function executeCheckingDuplicates(\PDOStatement $stmt): void
    {
        try {
            $stmt->execute();
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) === self::DUPLICATE_ENTRY) {
                throw new InvalidArgumentException("Ce nom d'utilisateur ou cette adresse e-mail est déjà utilisé.");
            }
            throw $e;
        }
    }
}
