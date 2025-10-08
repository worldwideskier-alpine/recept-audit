<?php
declare(strict_types=1);

namespace App\Support;

use PDO;
use PDOException;
use Throwable;

final class Schema
{
    public static function ensure(): array
    {
        $pdo = DB::pdo();
        $results = [
            'statements' => [],
            'indexes' => [],
            'fixed_rows' => [],
        ];
        $sqlFile = Paths::basePath('schema.sql');
        if (is_file($sqlFile)) {
            $sql = file_get_contents($sqlFile) ?: '';
            $statements = self::splitStatements($sql);
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if ($statement === '') {
                    continue;
                }
                try {
                    $pdo->exec($statement);
                    log_line('schema_table_created', ['statement' => $statement]);
                    $results['statements'][] = ['statement' => $statement, 'status' => 'ok'];
                } catch (PDOException $e) {
                    log_line('schema_exec_failed', ['statement' => $statement, 'error' => $e->getMessage()], 'error');
                    $results['statements'][] = ['statement' => $statement, 'status' => 'failed', 'error' => $e->getMessage()];
                }
            }
        }

        $indexSql = 'CREATE INDEX ix_sc_status_requested ON sync_commands (status, requested_at, id)';
        try {
            DB::ensureIndex('sync_commands', 'ix_sc_status_requested', $indexSql);
            $results['indexes'][] = ['name' => 'ix_sc_status_requested', 'status' => 'ok'];
        } catch (Throwable $e) {
            $results['indexes'][] = ['name' => 'ix_sc_status_requested', 'status' => 'failed', 'error' => $e->getMessage()];
        }

        $results['fixed_rows'][] = self::ensureFixedRow('rules_sync_state', 1, ['last_sha256' => null]);
        $results['fixed_rows'][] = self::ensureFixedRow('sync_runner_state', 1, ['backoff_seconds' => 0]);

        return $results;
    }

    private static function ensureFixedRow(string $table, int $id, array $defaults): array
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare(sprintf('SELECT COUNT(*) FROM %s WHERE id = ?', $table));
        $stmt->execute([$id]);
        $exists = (int) $stmt->fetchColumn() > 0;
        if ($exists) {
            log_line('schema_fixed_row_exists', ['table' => $table, 'id' => $id]);
            return ['table' => $table, 'id' => $id, 'status' => 'exists'];
        }
        $columns = array_merge(['id' => $id], $defaults);
        $colSql = implode(', ', array_keys($columns));
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $stmt = $pdo->prepare(sprintf('INSERT INTO %s (%s) VALUES (%s)', $table, $colSql, $placeholders));
        try {
            $stmt->execute(array_values($columns));
            log_line('schema_bootstrap_ok', ['table' => $table, 'id' => $id]);
            return ['table' => $table, 'id' => $id, 'status' => 'inserted'];
        } catch (PDOException $e) {
            log_line('schema_bootstrap_failed', ['table' => $table, 'id' => $id, 'error' => $e->getMessage()], 'error');
            return ['table' => $table, 'id' => $id, 'status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    /**
     * @return list<string>
     */
    private static function splitStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $inString = false;
        $stringChar = '';
        $length = strlen($sql);
        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            if ($inString) {
                if ($char === $stringChar) {
                    $inString = false;
                } elseif ($char === '\\') {
                    $buffer .= $char;
                    if ($i + 1 < $length) {
                        $buffer .= $sql[++$i];
                        continue;
                    }
                }
                $buffer .= $char;
                continue;
            }
            if ($char === '\'' || $char === '"') {
                $inString = true;
                $stringChar = $char;
                $buffer .= $char;
                continue;
            }
            if ($char === ';') {
                $statements[] = $buffer;
                $buffer = '';
                continue;
            }
            $buffer .= $char;
        }
        if (trim($buffer) !== '') {
            $statements[] = $buffer;
        }
        return $statements;
    }
}
