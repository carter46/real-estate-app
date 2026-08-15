<?php
/**
 * Central application configuration.
 *
 * Copy config.local.php.example to config.local.php and set secrets there.
 * config.local.php is gitignored and overrides values below.
 */

declare(strict_types=1);

$config = [
    'app' => [
        'name' => 'Sunview Development and Consultancy (SDC)',
        'env' => 'local', // local | staging | production
        // Prefer false in committed defaults; enable via config.local.php when developing.
        'debug' => false,
        'url' => 'http://localhost/real-estate-app',
        'timezone' => 'America/Denver',
    ],

    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'real_estate',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],

    'mail' => [
        'driver' => 'log', // smtp | mail | log — use log locally until SMTP is configured
        'from_email' => 'noreply@example.com',
        'from_name' => 'SDC',
        'admin_notify_email' => 'admin@example.com',
        // Brevo / SMTP
        'smtp_host' => 'smtp-relay.brevo.com',
        'smtp_port' => 587,
        'smtp_encryption' => 'tls', // tls | ssl | ''
        'smtp_user' => '',
        'smtp_pass' => '',
    ],

    'security' => [
        'session_name' => 'sdc_re_session',
        'csrf_token_key' => '_csrf_token',
        'password_algo' => PASSWORD_DEFAULT,
    ],

    'uploads' => [
        'properties_dir' => __DIR__ . '/uploads/properties',
        'max_bytes' => 10 * 1024 * 1024, // 10MB images; video limits later
        'allowed_mime' => [
            'image/jpeg',
            'image/png',
            'image/webp',
        ],
        'allowed_ext' => ['jpg', 'jpeg', 'png', 'webp'],
    ],

    /**
     * Public visibility rules (status => visible on public site).
     * under_contract / sold follow design/business; default public with badge support.
     */
    'visibility' => [
        'draft' => false,
        'private' => false,
        'available' => true,
        'pending' => true,
        'under_contract' => true,
        'sold' => true,
        'archived' => false,
    ],
];

$localConfigPath = __DIR__ . '/config.local.php';
if (is_readable($localConfigPath)) {
    $local = require $localConfigPath;
    if (is_array($local)) {
        $config = array_replace_recursive($config, $local);
    }
}

// Production safety: never leave debug on when env is production.
if (($config['app']['env'] ?? '') === 'production') {
    $config['app']['debug'] = false;
}

return $config;
