<?php
declare(strict_types=1);

const BASE_DIR = __DIR__ . '/..';
const APP_DIR = BASE_DIR . '/src/App';
const STORAGE_DIR = BASE_DIR . '/storage';
const LOG_FILE = STORAGE_DIR . '/logs/app.log';

if (!is_dir(dirname(LOG_FILE))) {
    mkdir(dirname(LOG_FILE), 0775, true);
}

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $path = BASE_DIR . '/src/' . str_replace('\\', '/', $class) . '.php';
        if (is_file($path)) {
            require_once $path;
        }
    }
});

require_once APP_DIR . '/Support/Helpers.php';
require_once APP_DIR . '/Support/Config.php';
require_once APP_DIR . '/Support/Log.php';
require_once APP_DIR . '/Support/Response.php';
require_once APP_DIR . '/Support/Request.php';
require_once APP_DIR . '/Support/Router.php';
require_once APP_DIR . '/Support/DB.php';
require_once APP_DIR . '/Support/Schema.php';
require_once APP_DIR . '/Support/Auth.php';
require_once APP_DIR . '/Support/CSRF.php';
require_once APP_DIR . '/Support/HTML.php';
require_once APP_DIR . '/Controllers/EnvController.php';
require_once APP_DIR . '/Controllers/HealthController.php';
require_once APP_DIR . '/Controllers/AuthController.php';
require_once APP_DIR . '/Controllers/ProviderSetupController.php';
require_once APP_DIR . '/Controllers/ProviderDashboardController.php';
require_once APP_DIR . '/Controllers/ProviderTenantsController.php';
require_once APP_DIR . '/Controllers/ProviderRulesController.php';
require_once APP_DIR . '/Controllers/AdminDashboardController.php';
require_once APP_DIR . '/Controllers/AdminClerkController.php';
require_once APP_DIR . '/Exceptions/HttpException.php';
