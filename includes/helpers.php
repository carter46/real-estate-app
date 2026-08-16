<?php
/**
 * Shared helper functions.
 */

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-') ?: 'item';
}

function base_url(string $path = ''): string
{
    $base = rtrim((string) app_config('app.url', ''), '/');
    $path = ltrim($path, '/');
    return $path === '' ? $base : $base . '/' . $path;
}

/**
 * Redirect only to same-app relative paths (or absolute URLs under app.url).
 * Rejects open redirects via external hosts.
 */
function redirect(string $path): never
{
    $target = redirect_safe_url($path);
    header('Location: ' . $target);
    exit;
}

function redirect_safe_url(string $path): string
{
    $path = trim($path);
    $appBase = rtrim((string) app_config('app.url', ''), '/');

    if ($path === '') {
        return $appBase !== '' ? $appBase . '/' : '/';
    }

    // Protocol-relative or absolute external URLs are rejected unless under app.url.
    if (preg_match('#^(https?:)?//#i', $path) === 1) {
        if (preg_match('#^https?://#i', $path) !== 1) {
            return $appBase !== '' ? $appBase . '/' : '/';
        }
        if ($appBase !== '' && str_starts_with(strtolower($path), strtolower($appBase))) {
            return $path;
        }
        return $appBase !== '' ? $appBase . '/' : '/';
    }

    // Relative path only
    if (str_contains($path, "\n") || str_contains($path, "\r") || str_starts_with($path, '//')) {
        return $appBase !== '' ? $appBase . '/' : '/';
    }

    return base_url(ltrim($path, '/'));
}

function flash_set(string $key, string $message): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }
    $_SESSION['_flash'][$key] = $message;
}

function flash_get(string $key, bool $clear = true): ?string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return null;
    }
    $message = $_SESSION['_flash'][$key] ?? null;
    if ($clear && isset($_SESSION['_flash'][$key])) {
        unset($_SESSION['_flash'][$key]);
    }
    return is_string($message) ? $message : null;
}

function format_price(?float $price, bool $onRequest = false, string $currency = 'USD'): string
{
    if ($onRequest || $price === null) {
        return 'Price Upon Request';
    }

    $symbol = $currency === 'USD' ? '$' : $currency . ' ';
    return $symbol . number_format($price, 0, '.', ',');
}

/**
 * Resolve a media path for <img src>.
 * Absolute http(s) only; protocol-relative // rejected. Relative paths use base_url().
 */
function media_url(?string $path): string
{
    $path = trim((string) $path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^//#', $path) === 1) {
        return '';
    }
    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }
    // Block path traversal in relative media
    if (str_contains($path, '..')) {
        return '';
    }
    return base_url(ltrim($path, '/'));
}

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function is_post(): bool
{
    return request_method() === 'POST';
}

/**
 * Append a line to storage/logs/app.log (and PHP error_log).
 */
function app_log(string $channel, string $message): void
{
    $line = sprintf('[%s] [%s] %s', date('c'), $channel, $message);
    error_log('[SDC] ' . $channel . ': ' . $message);
    $logDir = APP_ROOT . '/storage/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    @file_put_contents($logDir . '/app.log', $line . "\n", FILE_APPEND | LOCK_EX);
}
