<?php
declare(strict_types=1);

namespace ReceptAudit\Support;

use PDO;
use PDOException;

final class DB
{
    private static ?PDO $pdo = null;

    /**
     * @throws PDOException
     */
    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = (string) Config::get('db_host', 'localhost');
        $name = (string) Config::get('db_name', '');
        $user = (string) Config::get('db_user', '');
        $pass = (string) Config::get('db_pass', '');
        $port = (int) Config::get('db_port', 3306);

        $dsn = sprintf('mysql:host=%s;dbname=%s;port=%d;charset=utf8mb4', $host, $name, $port);

        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            Log::event('db_connect_failed', ['message' => $e->getMessage()], 'error');
            throw $e;
        }

        self::configureCollation($pdo);
        self::$pdo = $pdo;

        return self::$pdo;
    }

    private static function configureCollation(PDO $pdo): void
    {
        try {
            $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_0900_ai_ci");
        } catch (PDOException $e) {
            Log::event('charset_fallback', ['message' => $e->getMessage()], 'warning');
            $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        }
    }
}
