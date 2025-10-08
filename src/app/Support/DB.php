<?php
declare(strict_types=1);

namespace App\Support;

use PDO;
use PDOException;

final class DB
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $config = Config::getInstance();
        $host = (string) $config->get('db.host');
        $port = (int) $config->get('db.port', 3306);
        $name = (string) $config->get('db.name');
        $user = (string) $config->get('db.user');
        $pass = (string) $config->get('db.pass');
        $charset = (string) $config->get('db.charset', 'utf8mb4');

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $name, $charset);

        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            self::$pdo = $pdo;
        } catch (PDOException $e) {
            log_line([
                'event' => 'db_connect_failed',
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }

        return self::$pdo;
    }
}
