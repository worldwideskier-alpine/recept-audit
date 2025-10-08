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
        $config = Config::db();
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            (int) $config['port'],
            $config['name'],
            $config['charset']
        );
        try {
            $pdo = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            log_line('db_connect_failed', ['message' => $e->getMessage()], 'error');
            throw $e;
        }
        $collation = $config['collation'] ?? 'utf8mb4_0900_ai_ci';
        try {
            $pdo->exec('SET NAMES utf8mb4 COLLATE ' . $collation);
        } catch (PDOException $e) {
            log_line('charset_fallback', ['target' => $collation, 'error' => $e->getMessage()], 'warning');
            $pdo->exec('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
        }
        self::$pdo = $pdo;
        return $pdo;
    }

    public static function transaction(callable $callback)
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function ensureIndex(string $table, string $name, string $createSql): void
    {
        $pdo = self::pdo();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?');
        $stmt->execute([$table, $name]);
        $exists = (int) $stmt->fetchColumn() > 0;
        if ($exists) {
            log_line('schema_index_exists', ['name' => $name, 'table' => $table]);
            return;
        }
        try {
            $pdo->exec($createSql);
            log_line('schema_index_created', ['name' => $name, 'table' => $table]);
        } catch (PDOException $e) {
            log_line('schema_index_failed', ['name' => $name, 'table' => $table, 'message' => $e->getMessage()], 'error');
            throw $e;
        }
    }
}
