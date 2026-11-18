<?php

/**
 * Connexion PDO à la base MariaDB/MySQL.
 *
 * La connexion est ouverte une seule fois par requête HTTP et partagée par
 * tous les managers (propriété statique), même si plusieurs objets Database
 * sont créés.
 */
class Database implements DatabaseInterface
{
    private static ?PDO $pdo = null;

    public function getPdo(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = self::connect();
        }

        return self::$pdo;
    }

    private static function connect(): PDO
    {
        $host = Config::get('DB_HOST');
        $port = Config::get('DB_PORT', '3306');
        $name = Config::get('DB_NAME');
        $user = Config::get('DB_USER');
        $password = Config::get('DB_PASSWORD');

        if ($host === null || $name === null || $user === null) {
            throw new RuntimeException('Configuration de la base de données incomplète (DB_HOST, DB_NAME, DB_USER).');
        }

        // Documentation : https://www.php.net/manual/fr/ref.pdo-mysql.connection.php
        $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";

        return new PDO($dsn, $user, $password, [
            // Lève une exception en cas d'erreur SQL
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            // Résultats sous forme de tableaux associatifs
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Vraies requêtes préparées côté serveur (protection contre l'injection SQL)
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
