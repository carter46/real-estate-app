<?php
/**
 * PDO database connection (singleton).
 */

declare(strict_types=1);

/**
 * @return PDO
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = (string) app_config('db.host', '127.0.0.1');
    $port = (int) app_config('db.port', 3306);
    $name = (string) app_config('db.name', 'real_estate');
    $user = (string) app_config('db.user', 'root');
    $pass = (string) app_config('db.pass', '');
    $charset = (string) app_config('db.charset', 'utf8mb4');

    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $name, $charset);

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
