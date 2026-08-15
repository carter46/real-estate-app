<?php
/**
 * Application bootstrap.
 */

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

$config = require APP_ROOT . '/config.php';

date_default_timezone_set($config['app']['timezone'] ?? 'UTC');

if (($config['app']['env'] ?? 'production') !== 'production' && !empty($config['app']['debug'])) {
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
require_once APP_ROOT . '/includes/db.php';
require_once APP_ROOT . '/includes/visibility.php';
require_once APP_ROOT . '/includes/csrf.php';
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/mailer.php';
require_once APP_ROOT . '/includes/properties.php';
require_once APP_ROOT . '/includes/uploads.php';
require_once APP_ROOT . '/includes/inquiries.php';

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
