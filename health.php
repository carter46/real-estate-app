<?php
/**
 * Health / readiness check — no secrets in output.
 *
 * Access:
 * - CLI: php health.php
 * - Web: authenticated admin, OR app.env=local with debug, OR ?token= matching security.health_token
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$isCli = PHP_SAPI === 'cli';
$allowed = $isCli;
$token = (string) app_config('security.health_token', '');
if (!$allowed && $token !== '' && hash_equals($token, (string) ($_GET['token'] ?? ''))) {
    $allowed = true;
}
if (!$allowed && (string) app_config('app.env') === 'local' && !empty(app_config('app.debug'))) {
    $allowed = true;
}
if (!$allowed && auth_check()) {
    $allowed = true;
}

if (!$allowed) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}

$checks = [];

$checks[] = [
    'name' => 'php_version',
    'ok' => version_compare(PHP_VERSION, '8.1.0', '>='),
    'detail' => PHP_VERSION,
];

$checks[] = [
    'name' => 'config_loaded',
    'ok' => is_string(app_config('app.name')) && app_config('app.name') !== '',
    'detail' => (string) app_config('app.env', 'unknown'),
];

$checks[] = [
    'name' => 'debug_safe',
    'ok' => (string) app_config('app.env') !== 'production' || empty(app_config('app.debug')),
    'detail' => !empty(app_config('app.debug')) ? 'debug_on' : 'debug_off',
];

$dbOk = false;
$dbDetail = 'unavailable';
try {
    db()->query('SELECT 1');
    $dbOk = true;
    $dbDetail = 'connected';
} catch (Throwable $e) {
    $dbDetail = 'error';
    app_log('health', 'DB ping failed: ' . $e->getMessage());
}
$checks[] = ['name' => 'database', 'ok' => $dbOk, 'detail' => $dbDetail];

$uploadsDir = (string) app_config('uploads.properties_dir', APP_ROOT . '/uploads/properties');
$checks[] = [
    'name' => 'uploads_writable',
    'ok' => is_dir($uploadsDir) ? is_writable($uploadsDir) : (@mkdir($uploadsDir, 0755, true) && is_writable($uploadsDir)),
    'detail' => 'uploads/properties',
];

$logDir = APP_ROOT . '/storage/logs';
$checks[] = [
    'name' => 'logs_writable',
    'ok' => is_dir($logDir) ? is_writable($logDir) : (@mkdir($logDir, 0755, true) && is_writable($logDir)),
    'detail' => 'storage/logs',
];

$mailDriver = strtolower((string) app_config('mail.driver', 'log'));
$brevoKey = trim((string) app_config('mail.brevo_api_key', ''));
$mailOk = match ($mailDriver) {
    'log' => true,
    'brevo' => $brevoKey !== '' && (function_exists('curl_init') || ini_get('allow_url_fopen')),
    'mail' => class_exists(\PHPMailer\PHPMailer\PHPMailer::class)
        || is_readable(APP_ROOT . '/PHPMailer/PHPMailer.php'),
    'smtp' => class_exists(\PHPMailer\PHPMailer\PHPMailer::class)
        || is_readable(APP_ROOT . '/PHPMailer/PHPMailer.php'),
    default => false,
};
$checks[] = [
    'name' => 'mail',
    'ok' => $mailOk,
    'detail' => $mailDriver . ($mailDriver === 'brevo' ? ($brevoKey !== '' ? '+api_key' : '+missing_api_key') : ''),
];

$checks[] = [
    'name' => 'public_statuses',
    'ok' => public_property_statuses() !== [],
    'detail' => implode(',', public_property_statuses()),
];

$allOk = true;
foreach ($checks as $c) {
    if (empty($c['ok'])) {
        $allOk = false;
        break;
    }
}

$payload = [
    'ok' => $allOk,
    'app' => (string) app_config('app.name'),
    'env' => (string) app_config('app.env'),
    'checks' => $checks,
];

if ($isCli) {
    echo ($allOk ? "OK\n" : "FAIL\n");
    foreach ($checks as $c) {
        echo sprintf("  [%s] %s — %s\n", !empty($c['ok']) ? 'pass' : 'fail', $c['name'], $c['detail']);
    }
    exit($allOk ? 0 : 1);
}

http_response_code($allOk ? 200 : 503);
header('Content-Type: application/json; charset=utf-8');
echo json_encode($payload, JSON_PRETTY_PRINT);
