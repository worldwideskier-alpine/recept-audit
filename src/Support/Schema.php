<?php
declare(strict_types=1);

namespace ReceptAudit\Support;

use PDO;
use PDOException;
use Throwable;

final class Schema
{
    /**
     * @return array<string, mixed>
     */
    public static function ensure(): array
    {
        $result = [
            'ok' => true,
            'errors' => [],
            'tables' => [],
            'indexes' => [],
            'fixed_rows' => [],
        ];

        try {
            $pdo = DB::pdo();
        } catch (PDOException $e) {
            $result['ok'] = false;
            $result['errors'][] = $e->getMessage();
            return $result;
        }

        foreach (self::tableStatements() as $table => $sql) {
            $success = self::withGuard(
                static fn () => $pdo->exec($sql),
                'schema_table_created',
                ['table' => $table],
                $result
            );
            $result['tables'][] = ['table' => $table, 'status' => $success ? 'ok' : 'failed'];
        }

        self::ensureUsersForeignKey($pdo, $result);
        self::ensureSyncCommandsIndex($pdo, $result);
        self::ensureFixedRows($pdo, $result);

        return $result;
    }

    /**
     * @return array<string, string>
     */
    private static function tableStatements(): array
    {
        return [
            'tenants' => <<<SQL
CREATE TABLE IF NOT EXISTS tenants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            'users' => <<<SQL
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL,
    force_reset TINYINT(1) NOT NULL DEFAULT 0,
    password_reset_token VARCHAR(255) DEFAULT NULL,
    password_reset_expires DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            'departments' => <<<SQL
CREATE TABLE IF NOT EXISTS departments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_departments_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            'provider_rules' => <<<SQL
CREATE TABLE IF NOT EXISTS provider_rules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    rule_condition TEXT NOT NULL,
    rule_action TEXT NOT NULL,
    version VARCHAR(50) NOT NULL,
    source_date DATE DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_provider_rules_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            'patients' => <<<SQL
CREATE TABLE IF NOT EXISTS patients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            'claims' => <<<SQL
CREATE TABLE IF NOT EXISTS claims (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_claims_patient_id (patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            'claim_items' => <<<SQL
CREATE TABLE IF NOT EXISTS claim_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    claim_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_claim_items_claim_id (claim_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            'audit_rules' => <<<SQL
CREATE TABLE IF NOT EXISTS audit_rules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    keyname VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            'tenant_rule_overrides' => <<<SQL
CREATE TABLE IF NOT EXISTS tenant_rule_overrides (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    rule_id INT UNSIGNED NOT NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            'sync_commands' => <<<SQL
CREATE TABLE IF NOT EXISTS sync_commands (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    status VARCHAR(50) NOT NULL,
    requested_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            'rules_sync_state' => <<<SQL
CREATE TABLE IF NOT EXISTS rules_sync_state (
    id INT UNSIGNED PRIMARY KEY,
    last_sha256 CHAR(64) DEFAULT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            'sync_runner_state' => <<<SQL
CREATE TABLE IF NOT EXISTS sync_runner_state (
    id INT UNSIGNED PRIMARY KEY,
    backoff_seconds INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            'import_runs' => <<<SQL
CREATE TABLE IF NOT EXISTS import_runs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pack_name VARCHAR(255) NOT NULL,
    pack_sha256 CHAR(64) NOT NULL,
    inserted INT NOT NULL,
    updated INT NOT NULL,
    failed INT NOT NULL,
    lines_read INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            'job_runs' => <<<SQL
CREATE TABLE IF NOT EXISTS job_runs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    status VARCHAR(50) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        ];
    }

    /**
     * @param array<string, mixed> $result
     */
    private static function ensureUsersForeignKey(PDO $pdo, array &$result): void
    {
        $sql = <<<SQL
SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = DATABASE()
  AND TABLE_NAME = 'users'
  AND CONSTRAINT_NAME = 'fk_users_tenant_id'
SQL;

        $shouldCreate = (int) $pdo->query($sql)->fetchColumn() === 0;

        if (!$shouldCreate) {
            return;
        }

        $created = self::withGuard(
            static function () use ($pdo): void {
                $pdo->exec('ALTER TABLE users ADD CONSTRAINT fk_users_tenant_id FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL');
            },
            'schema_fk_created',
            ['table' => 'users', 'constraint' => 'fk_users_tenant_id'],
            $result
        );
        $result['tables'][] = ['table' => 'users', 'constraint' => 'fk_users_tenant_id', 'status' => $created ? 'ok' : 'failed'];
    }

    /**
     * @param array<string, mixed> $result
     */
    private static function ensureSyncCommandsIndex(PDO $pdo, array &$result): void
    {
        $stmt = $pdo->prepare(<<<SQL
SELECT COUNT(*) FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'sync_commands'
  AND INDEX_NAME = 'ix_sc_status_requested'
SQL);
        $stmt->execute();
        $exists = (int) $stmt->fetchColumn() > 0;

        if ($exists) {
            Log::event('schema_index_exists', ['index' => 'ix_sc_status_requested']);
            $result['indexes'][] = ['index' => 'ix_sc_status_requested', 'status' => 'exists'];
            return;
        }

        $created = self::withGuard(
            static function () use ($pdo): void {
                $pdo->exec('CREATE INDEX ix_sc_status_requested ON sync_commands (status, requested_at, id)');
            },
            'schema_index_created',
            ['index' => 'ix_sc_status_requested'],
            $result
        );
        $result['indexes'][] = ['index' => 'ix_sc_status_requested', 'status' => $created ? 'ok' : 'failed'];
    }

    /**
     * @param array<string, mixed> $result
     */
    private static function ensureFixedRows(PDO $pdo, array &$result): void
    {
        $fixed = [
            ['table' => 'rules_sync_state', 'id' => 1],
            ['table' => 'sync_runner_state', 'id' => 1],
        ];

        foreach ($fixed as $row) {
            $ensured = self::withGuard(
                static function () use ($pdo, $row): void {
                    $table = $row['table'];
                    $id = $row['id'];
                    $sql = sprintf(
                        "INSERT INTO %s (id) VALUES (:id) AS new ON DUPLICATE KEY UPDATE id = new.id",
                        $table
                    );
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['id' => $id]);
                },
                'schema_fixed_row_ensured',
                ['table' => $row['table'], 'id' => $row['id']],
                $result
            );
            $result['fixed_rows'][] = ['table' => $row['table'], 'id' => $row['id'], 'status' => $ensured ? 'ok' : 'failed'];
        }
    }

    /**
     * @param callable():void $operation
     * @param array<string, mixed> $result
     */
    private static function withGuard(callable $operation, string $event, array $context, array &$result): bool
    {
        try {
            $operation();
            Log::event($event, $context);
            return true;
        } catch (Throwable $e) {
            $result['ok'] = false;
            $result['errors'][] = $e->getMessage();
            Log::event($event . '_failed', $context + ['message' => $e->getMessage()], 'error');
            return false;
        }
    }
}
