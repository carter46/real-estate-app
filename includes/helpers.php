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

function redirect(string $path): never
{
    if (!preg_match('#^https?://#i', $path)) {
        $path = base_url($path);
    }
    header('Location: ' . $path);
    exit;
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
 * Absolute http(s) URLs pass through; relative paths are prefixed with base_url().
 */
function media_url(?string $path): string
{
    $path = trim((string) $path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^(https?:)?//#i', $path) === 1) {
        return $path;
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
