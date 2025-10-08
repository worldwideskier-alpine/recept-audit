<?php
declare(strict_types=1);

namespace App\Support;

use PDO;
use Throwable;

final class Schema
{
    public static function ensure(): void
    {
        $pdo = self::pdo();
        $sot = self::loadSot();
        self::ensureTables($pdo, $sot['tables'] ?? []);
        self::ensureFixedRows($pdo, $sot['fixed_rows'] ?? []);
        self::ensureIndexes($pdo, $sot['indexes'] ?? []);
    }

    private static function pdo(): PDO
    {
        return DB::pdo();
    }

    /**
     * @return array<string, mixed>
     */
    private static function loadSot(): array
    {
        $path = base_path('app/SOT/schema.required.json');
        $json = file_get_contents($path);
        if ($json === false) {
            return [];
        }
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    /**
     * @param PDO $pdo
     * @param array<int, string> $tables
     */
    private static function ensureTables(PDO $pdo, array $tables): void
    {
        foreach ($tables as $table) {
            try {
                $sql = 'CREATE TABLE IF NOT EXISTS `' . $table . '` (id INT PRIMARY KEY AUTO_INCREMENT)';
                $pdo->exec($sql);
                log_line([
                    'event' => 'schema_table_created',
                    'table' => $table,
                ]);
            } catch (Throwable $e) {
                log_line([
                    'event' => 'schema_exec_failed',
                    'table' => $table,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * @param PDO $pdo
     * @param array<int, array<string, mixed>> $fixedRows
     */
    private static function ensureFixedRows(PDO $pdo, array $fixedRows): void
    {
        foreach ($fixedRows as $row) {
            $table = $row['table'] ?? null;
            $pk = $row['pk'] ?? null;
            $value = $row['value'] ?? null;
            if (!is_string($table) || !is_string($pk)) {
                continue;
            }
            try {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM `' . $table . '` WHERE `' . $pk . '` = ?');
                $stmt->execute([$value]);
                if ((int) $stmt->fetchColumn() === 0) {
                    $insert = $pdo->prepare('INSERT INTO `' . $table . '` (`' . $pk . '`) VALUES (?)');
                    $insert->execute([$value]);
                    log_line([
                        'event' => 'schema_bootstrap_ok',
                        'table' => $table,
                        'pk' => $value,
                    ]);
                }
            } catch (Throwable $e) {
                log_line([
                    'event' => 'schema_fixed_row_failed',
                    'table' => $table,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * @param PDO $pdo
     * @param array<int, array<string, mixed>> $indexes
     */
    private static function ensureIndexes(PDO $pdo, array $indexes): void
    {
        foreach ($indexes as $index) {
            $table = $index['table'] ?? null;
            $name = $index['name'] ?? null;
            $columns = $index['columns'] ?? [];
            if (!is_string($table) || !is_string($name) || !is_array($columns)) {
                continue;
            }
            try {
                $stmt = $pdo->prepare(
                    'SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?'
                );
                $stmt->execute([$table, $name]);
                if ((int) $stmt->fetchColumn() === 0) {
                    $cols = array_map(static fn (string $col): string => '`' . $col . '`', $columns);
                    $pdo->exec(sprintf(
                        'CREATE INDEX `%s` ON `%s` (%s)',
                        $name,
                        $table,
                        implode(',', $cols)
                    ));
                    log_line([
                        'event' => 'schema_index_created',
                        'table' => $table,
                        'index' => $name,
                    ]);
                } else {
                    log_line([
                        'event' => 'schema_index_exists',
                        'table' => $table,
                        'index' => $name,
                    ]);
                }
            } catch (Throwable $e) {
                log_line([
                    'event' => 'schema_index_failed',
                    'table' => $table,
                    'index' => $name,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }
}
