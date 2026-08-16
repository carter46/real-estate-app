<?php
/**
 * Application bootstrap.
 */

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

// Always capture fatals/warnings to a file (even when display_errors is off).
$sdcLogDir = APP_ROOT . '/storage/logs';
if (!is_dir($sdcLogDir)) {
    @mkdir($sdcLogDir, 0755, true);
}
ini_set('log_errors', '1');
ini_set('error_log', $sdcLogDir . '/php-error.log');
set_exception_handler(static function (Throwable $e): void {
    error_log('[SDC uncaught] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
    }
    $debug = false;
    try {
        if (function_exists('app_config')) {
            $debug = (string) app_config('app.env', '') !== 'production' && !empty(app_config('app.debug'));
        }
    } catch (Throwable $ignored) {
    }
    echo $debug
        ? ('Error: ' . $e->getMessage())
        : 'A server error occurred. Check storage/logs/php-error.log';
    exit(1);
});

$config = require APP_ROOT . '/config.php';

date_default_timezone_set($config['app']['timezone'] ?? 'UTC');

$env = (string) ($config['app']['env'] ?? 'production');
$debug = !empty($config['app']['debug']);
if ($env === 'production') {
    $debug = false;
    $config['app']['debug'] = false;
}

if ($env !== 'production' && $debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
}

$autoload = APP_ROOT . '/vendor/autoload.php';
if (is_readable($autoload)) {
    require_once $autoload;
}

require_once APP_ROOT . '/includes/helpers.php';
require_once APP_ROOT . '/includes/security_headers.php';
require_once APP_ROOT . '/includes/db.php';
require_once APP_ROOT . '/includes/settings.php';
require_once APP_ROOT . '/includes/visibility.php';
require_once APP_ROOT . '/includes/csrf.php';
require_once APP_ROOT . '/includes/rate_limit.php';
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/mailer.php';
require_once APP_ROOT . '/includes/properties.php';
require_once APP_ROOT . '/includes/taxonomy.php';
require_once APP_ROOT . '/includes/uploads.php';
require_once APP_ROOT . '/includes/inquiries.php';

security_headers_send();

// Start session early so CSRF / flash work on public and admin requests.
auth_session_start();

/**
 * @return array<string, mixed>
 */
function app_config(?string $key = null, mixed $default = null): mixed
{
    global $config;
    if ($key === null) {
        return $config;
    }

    $segments = explode('.', $key);
    $value = $config;
    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}
