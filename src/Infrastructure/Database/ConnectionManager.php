<?php
namespace App\Infrastructure\Database;

use PDO;
use PDOException;
use App\Infrastructure\Config\Config;
use App\Infrastructure\Logging\ErrorHandler;

/**
 * Beheert meerdere PDO-connecties (users, sessions, loginAttempts).
 * Hiermee vervangen we de losse functies in legacy includes/backend/config/db.php.
 */
final class ConnectionManager
{
    /**
     * Cache van actieve PDO-connecties per naam.
     * @var array<string, PDO>
     */
    private static array $connections = [];

    /**
     * Haal (of maak) een PDO-connectie op basis van een key.
     * Mogelijke keys: default|users, sessions, login_attempts
     *
     * @throws PDOException bij verbindingsfouten
     */
    public static function get(string $name = 'default'): PDO
    {
        $name = strtolower($name);
        if (isset(self::$connections[$name])) {
            return self::$connections[$name];
        }

        [$host, $dbname, $user, $pass, $port] = self::resolveCredentials($name);
        $charset = 'utf8mb4';
        $dsn = "mysql:host={$host};dbname={$dbname};port={$port};charset={$charset}";

        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            self::$connections[$name] = $pdo;
            return $pdo;
        } catch (PDOException $e) {
            ErrorHandler::getInstance()->logError('Database connectie mislukt', [
                'connection' => $name,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Bepaal database-credentials op basis van de connectienaam.
     * Prioriteit: specifieke ENV-variabelen → globale → Config fallback.
     */
    private static function resolveCredentials(string $name): array
    {
        $config = Config::getInstance();
        $prefix = match ($name) {
            'sessions'        => 'SESSIONS_',
            'login_attempts', 'loginattempts' => 'LOGIN_ATTEMPTS_',
            default           => '', // default/users
        };

        $upper = static fn(string $k) => ($prefix ? $prefix : '') . 'DB_' . strtoupper($k);

        $host = getenv($upper('HOST')) ?: $config->get('db_host');
        $dbname = getenv($upper('NAME')) ?: $config->get('db_name');
        $user = getenv($upper('USER')) ?: $config->get('db_user');
        $pass = getenv($upper('PASSWORD')) ?: $config->get('db_pass');
        $port = getenv($upper('PORT')) ?: 3306;

        return [$host, $dbname, $user, $pass, $port];
    }

    /**
     * Sluit (en verwijder) een connectie uit de cache.
     */
    public static function disconnect(string $name = 'default'): void
    {
        if (isset(self::$connections[$name])) {
            self::$connections[$name] = null;
            unset(self::$connections[$name]);
        }
    }

    /**
     * Sluit álle connecties (handig bij shutdown tests).
     */
    public static function disconnectAll(): void
    {
        foreach (array_keys(self::$connections) as $k) {
            self::disconnect($k);
        }
    }
} 