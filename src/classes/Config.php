<?php

/**
 * Configuration centrale de l'application.
 *
 * Ordre de lecture de chaque valeur :
 * 1. variable d'environnement (fournie par docker-compose.yml en local) ;
 * 2. fichier config.local.php à la racine du projet (production Infomaniak,
 *    hors de public/ et jamais versionné) ;
 * 3. valeur par défaut.
 */
class Config
{
    const LOCAL_CONFIGURATION_FILE = __DIR__ . '/../../config.local.php';

    // Valeurs lues dans config.local.php, chargées une seule fois
    private static ?array $fileValues = null;

    public static function get(string $key, ?string $default = null): ?string
    {
        $envValue = getenv($key);
        if ($envValue !== false && $envValue !== '') {
            return $envValue;
        }

        $fileValues = self::loadFile();
        if (isset($fileValues[$key]) && $fileValues[$key] !== '') {
            return (string) $fileValues[$key];
        }

        return $default;
    }

    public static function isProduction(): bool
    {
        return self::get('APP_ENV', 'dev') === 'prod';
    }

    private static function loadFile(): array
    {
        if (self::$fileValues === null) {
            self::$fileValues = is_file(self::LOCAL_CONFIGURATION_FILE)
                ? require self::LOCAL_CONFIGURATION_FILE
                : [];
        }

        return self::$fileValues;
    }
}
