<?php
declare(strict_types=1);

namespace App\Config;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Singleton PDO Database Connection Manager
 * Designed for cPanel Shared / VPS (MySQL 8.0+ / MariaDB 10.5+)
 */
class Database
{
    private static ?PDO $instance = null;

    private function __construct()
    {
        // Private constructor to prevent direct instantiation
    }

    public static function loadEnv(?string $filePath = null): void
    {
        $path = $filePath ?: dirname(__DIR__) . '/.env';
        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (strpos($line, '=') !== false) {
                [$key, $val] = explode('=', $line, 2);
                $key = trim($key);
                $val = trim($val);
                // Strip quotes if present
                if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                    (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                    $val = substr($val, 1, -1);
                }
                if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                    putenv("{$key}={$val}");
                    $_ENV[$key] = $val;
                    $_SERVER[$key] = $val;
                }
            }
        }
    }

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            self::loadEnv();
            $driver = getenv('DB_DRIVER') ?: 'mysql';

            if ($driver === 'sqlite') {
                $sqlitePath = dirname(__DIR__) . '/database/local.sqlite';
                self::$instance = new PDO("sqlite:{$sqlitePath}", null, null, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
                require_once dirname(__DIR__) . '/database/sqlite_bootstrap.php';
                \App\Database\SqliteBootstrap::init(self::$instance);
                return self::$instance;
            }

            $host = getenv('DB_HOST') ?: '127.0.0.1';
            $port = getenv('DB_PORT') ?: '3306';
            $dbName = getenv('DB_NAME') ?: 'hipertrofi_platform';
            $username = getenv('DB_USER') ?: 'root';
            $password = getenv('DB_PASS') ?: '';
            $charset = 'utf8mb4';

            $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            try {
                self::$instance = new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                // If local development and MySQL is down, seamlessly fallback to SQLite
                if ($host === '127.0.0.1' || $host === 'localhost') {
                    $sqlitePath = dirname(__DIR__) . '/database/local.sqlite';
                    self::$instance = new PDO("sqlite:{$sqlitePath}", null, null, [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]);
                    require_once dirname(__DIR__) . '/database/sqlite_bootstrap.php';
                    \App\Database\SqliteBootstrap::init(self::$instance);
                    return self::$instance;
                }

                error_log("Database Connection Error: " . $e->getMessage());
                throw new RuntimeException("Veritabanı bağlantısı kurulamadı. Lütfen sistem yöneticisiyle iletişime geçin.");
            }
        }

        return self::$instance;
    }

    /**
     * Helper for test environments or reconnection
     */
    public static function setConnection(?PDO $pdo): void
    {
        self::$instance = $pdo;
    }
}
