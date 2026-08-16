<?php
/**
 * One-time staging diagnostic. Delete this file after the site works.
 * Open: https://realestate.chain-m33.online/check.php
 */
declare(strict_types=1);

header('Content-Type: text/plain; charset=UTF-8');

$root = __DIR__;
echo "PHP: " . PHP_VERSION . "\n";
echo "Root: " . $root . "\n";
echo "config.php: " . (is_readable($root . '/config.php') ? 'yes' : 'NO') . "\n";
echo "config.local.php: " . (is_readable($root . '/config.local.php') ? 'yes' : 'MISSING — upload it') . "\n";
echo "vendor/autoload.php: " . (is_readable($root . '/vendor/autoload.php') ? 'yes' : 'MISSING — redeploy so Composer runs') . "\n";
echo "pdo_mysql: " . (extension_loaded('pdo_mysql') ? 'yes' : 'NO') . "\n";
echo "curl: " . (function_exists('curl_init') ? 'yes' : 'no') . "\n";

try {
    $config = require $root . '/config.php';
    echo "config load: ok\n";
    echo "app.url: " . ($config['app']['url'] ?? '') . "\n";
    echo "app.env: " . ($config['app']['env'] ?? '') . "\n";
    echo "db.name: " . ($config['db']['name'] ?? '') . "\n";
    echo "db.user: " . ($config['db']['user'] ?? '') . "\n";
    echo "mail.driver: " . ($config['mail']['driver'] ?? '') . "\n";
    echo "brevo key set: " . (!empty($config['mail']['brevo_api_key']) && $config['mail']['brevo_api_key'] !== 'YOUR_BREVO_API_KEY' ? 'yes' : 'no/placeholder') . "\n";
} catch (Throwable $e) {
    echo "config load FAILED: " . $e->getMessage() . "\n";
    exit;
}

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['db']['host'],
        (int) $config['db']['port'],
        $config['db']['name'],
        $config['db']['charset'] ?? 'utf8mb4'
    );
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $n = (int) $pdo->query('SELECT COUNT(*) FROM properties')->fetchColumn();
    echo "db connect: ok (properties={$n})\n";
} catch (Throwable $e) {
    echo "db connect FAILED: " . $e->getMessage() . "\n";
}

echo "\nDone. Delete check.php when finished.\n";
