<?php
declare(strict_types=1);

namespace App\Support;

use PDO;
use Throwable;

final class Schema
{
    public static function ensure(): array
    {
        $results = [
            'tables' => [],
            'indexes' => [],
            'fixed_rows' => [],
        ];

        $pdo = DB::pdo();
        $tables = self::tableDefinitions();
        foreach ($tables as $name => $sql) {
            try {
                $pdo->exec($sql);
                log_line('schema_table_created', ['table' => $name]);
                $results['tables'][$name] = 'created_or_exists';
            } catch (Throwable $throwable) {
                log_exception($throwable, 'schema_table_failed');
                $results['tables'][$name] = 'failed';
            }
        }

        // Ensure foreign keys or column adjustments if needed.
        self::ensureUserTenantForeignKey($pdo, $results);

        $indexes = self::indexDefinitions();
        foreach ($indexes as $index) {
            $table = $index['table'];
            $name = $index['name'];
            try {
                if (self::indexExists($pdo, $table, $name)) {
                    log_line('schema_index_exists', ['table' => $table, 'index' => $name]);
                    $results['indexes'][$name] = 'exists';
                    continue;
                }
                $pdo->exec($index['sql']);
                log_line('schema_index_created', ['table' => $table, 'index' => $name]);
                $results['indexes'][$name] = 'created';
            } catch (Throwable $throwable) {
                log_exception($throwable, 'schema_index_failed');
                $results['indexes'][$name] = 'failed';
            }
        }

        $fixedRows = [
            ['table' => 'rules_sync_state', 'pk' => 'id', 'value' => 1, 'payload' => ['last_sha256' => null]],
            ['table' => 'sync_runner_state', 'pk' => 'id', 'value' => 1, 'payload' => ['backoff_seconds' => 0]],
        ];

        foreach ($fixedRows as $row) {
            try {
                $pk = $row['pk'];
                $table = $row['table'];
                $value = $row['value'];
                $payload = $row['payload'];
                $exists = DB::select("SELECT COUNT(*) as count FROM {$table} WHERE {$pk} = :value", ['value' => $value]);
                if (($exists[0]['count'] ?? 0) === 0) {
                    $columns = array_merge([$pk => $value], $payload);
                    $fields = implode(', ', array_keys($columns));
                    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
                    DB::execute(
                        "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})",
                        array_values($columns)
                    );
                    log_line('fixed_row_upsert_ok', ['table' => $table, 'pk' => $value]);
                    $results['fixed_rows'][$table] = 'created';
                } else {
                    log_line('fixed_row_exists', ['table' => $table, 'pk' => $value]);
                    $results['fixed_rows'][$table] = 'exists';
                }
            } catch (Throwable $throwable) {
                log_exception($throwable, 'fixed_row_upsert_failed');
                $results['fixed_rows'][$row['table']] = 'failed';
            }
        }

        return $results;
    }

    private static function ensureUserTenantForeignKey(PDO $pdo, array &$results): void
    {
        try {
            $foreignKeyExists = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'tenant_id' AND REFERENCED_TABLE_NAME = 'tenants'");
            if (empty($foreignKeyExists)) {
                $pdo->exec('ALTER TABLE users ADD CONSTRAINT fk_users_tenant_id FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL');
                log_line('schema_foreign_key_created', ['table' => 'users', 'constraint' => 'fk_users_tenant_id']);
            }
            $results['foreign_keys']['fk_users_tenant_id'] = 'ok';
        } catch (Throwable $throwable) {
            log_exception($throwable, 'schema_foreign_key_failed');
            $results['foreign_keys']['fk_users_tenant_id'] = 'failed';
        }
    }

    private static function indexExists(PDO $pdo, string $table, string $index): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = :table AND index_name = :index');
        $stmt->execute(['table' => $table, 'index' => $index]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * @return array<string, string>
     */
    private static function tableDefinitions(): array
    {
        $collation = Config::get('db.collation', 'utf8mb4_0900_ai_ci');
        $charset = Config::get('db.charset', 'utf8mb4');
        $engine = 'ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ' COLLATE=' . $collation;

        return [
            'tenants' => <<<SQL
                CREATE TABLE IF NOT EXISTS tenants (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                ) {$engine}
            SQL,
            'users' => <<<SQL
                CREATE TABLE IF NOT EXISTS users (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    tenant_id INT UNSIGNED NULL,
                    email VARCHAR(255) NOT NULL,
                    password_hash VARCHAR(255) NOT NULL,
                    role VARCHAR(32) NOT NULL,
                    force_reset TINYINT(1) NOT NULL DEFAULT 0,
                    password_reset_token VARCHAR(255) NULL,
                    password_reset_expires DATETIME NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_users_email (email)
                ) {$engine}
            SQL,
            'departments' => <<<SQL
                CREATE TABLE IF NOT EXISTS departments (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    code VARCHAR(32) NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    UNIQUE KEY uq_departments_code (code)
                ) {$engine}
            SQL,
            'provider_rules' => <<<SQL
                CREATE TABLE IF NOT EXISTS provider_rules (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    rule_condition TEXT NOT NULL,
                    rule_action TEXT NOT NULL,
                    version VARCHAR(64) NOT NULL,
                    source_date DATE NOT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_provider_rules_title (title)
                ) {$engine}
            SQL,
            'patients' => <<<SQL
                CREATE TABLE IF NOT EXISTS patients (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    tenant_id INT UNSIGNED NULL,
                    external_id VARCHAR(64) NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                ) {$engine}
            SQL,
            'claims' => <<<SQL
                CREATE TABLE IF NOT EXISTS claims (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    tenant_id INT UNSIGNED NULL,
                    patient_id INT UNSIGNED NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                ) {$engine}
            SQL,
            'claim_items' => <<<SQL
                CREATE TABLE IF NOT EXISTS claim_items (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    claim_id INT UNSIGNED NOT NULL,
                    code VARCHAR(64) NULL,
                    quantity INT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                ) {$engine}
            SQL,
            'audit_rules' => <<<SQL
                CREATE TABLE IF NOT EXISTS audit_rules (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    keyname VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_audit_rules_keyname (keyname)
                ) {$engine}
            SQL,
            'tenant_rule_overrides' => <<<SQL
                CREATE TABLE IF NOT EXISTS tenant_rule_overrides (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    tenant_id INT UNSIGNED NOT NULL,
                    rule_id INT UNSIGNED NOT NULL,
                    enabled TINYINT(1) NOT NULL DEFAULT 1
                ) {$engine}
            SQL,
            'sync_commands' => <<<SQL
                CREATE TABLE IF NOT EXISTS sync_commands (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    status VARCHAR(64) NOT NULL,
                    requested_at DATETIME NOT NULL,
                    payload JSON NULL
                ) {$engine}
            SQL,
            'rules_sync_state' => <<<SQL
                CREATE TABLE IF NOT EXISTS rules_sync_state (
                    id INT UNSIGNED PRIMARY KEY,
                    last_sha256 CHAR(64) NULL,
                    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) {$engine}
            SQL,
            'sync_runner_state' => <<<SQL
                CREATE TABLE IF NOT EXISTS sync_runner_state (
                    id INT UNSIGNED PRIMARY KEY,
                    backoff_seconds INT NOT NULL DEFAULT 0,
                    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) {$engine}
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
                ) {$engine}
            SQL,
            'job_runs' => <<<SQL
                CREATE TABLE IF NOT EXISTS job_runs (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    status VARCHAR(64) NOT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                ) {$engine}
            SQL,
        ];
    }

    /**
     * @return array<int, array{table:string,name:string,sql:string}>
     */
    private static function indexDefinitions(): array
    {
        return [
            [
                'table' => 'sync_commands',
                'name' => 'ix_sc_status_requested',
                'sql' => 'CREATE INDEX ix_sc_status_requested ON sync_commands (status, requested_at, id)',
            ],
        ];
    }
}
