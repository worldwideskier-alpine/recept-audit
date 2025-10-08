<?php
declare(strict_types=1);

namespace App\Support;

use Throwable;

final class HealthCheck
{
    /**
     * @return array<string, mixed>
     */
    public static function run(): array
    {
        $result = [
            'ok' => false,
            'kind' => 'health',
            'db_ok' => false,
            'initialized' => false,
        ];

        try {
            Schema::ensure();
            $result['db_ok'] = true;
            $result['initialized'] = true;
            $result['ok'] = true;
            log_line([
                'event' => 'health_min_boot_pass',
            ]);
        } catch (Throwable $e) {
            log_line([
                'event' => 'health_min_boot_fail',
                'message' => $e->getMessage(),
            ]);
            $result['error'] = 'schema_boot_failed';
        }

        return $result;
    }
}
