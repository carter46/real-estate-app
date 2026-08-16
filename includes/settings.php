<?php
/**
 * Site settings — single source of truth for branding & public contact.
 * Write path: Admin → Settings only.
 */

declare(strict_types=1);

function setting_get(string $key, ?string $default = null): ?string
{
    try {
        $stmt = db()->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val === false || $val === null ? $default : (string) $val;
    } catch (Throwable $e) {
        return $default;
    }
}

function setting_set(string $key, ?string $value): void
{
    $stmt = db()->prepare(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP'
    );
    $stmt->execute([$key, $value]);
}

function site_name(): string
{
    $name = trim((string) (setting_get('site_name') ?? ''));
    if ($name !== '') {
        return $name;
    }
    return (string) app_config('app.name', 'SDC');
}

/**
 * Compact label for tight chrome (header brand).
 * Long names like "Sunview Development and Consultancy" → "Sunview".
 */
function site_name_short(int $maxLen = 18): string
{
    $name = site_name();
    $len = function_exists('mb_strlen') ? mb_strlen($name) : strlen($name);
    if ($len <= $maxLen) {
        return $name;
    }

    if (preg_match('/^[^\s]+/u', $name, $m)) {
        $first = (string) $m[0];
        $flen = function_exists('mb_strlen') ? mb_strlen($first) : strlen($first);
        if ($flen <= $maxLen) {
            return $first;
        }
        $cut = function_exists('mb_substr')
            ? mb_substr($first, 0, max(1, $maxLen - 1))
            : substr($first, 0, max(1, $maxLen - 1));
        return rtrim((string) $cut) . '…';
    }

    $cut = function_exists('mb_substr')
        ? mb_substr($name, 0, max(1, $maxLen - 1))
        : substr($name, 0, max(1, $maxLen - 1));
    return rtrim((string) $cut) . '…';
}

function site_phone(): string
{
    $phone = trim((string) (setting_get('site_phone') ?? ''));
    return $phone !== '' ? $phone : '800.555.0123';
}

function site_email(): string
{
    $email = trim((string) (setting_get('site_email') ?? ''));
    return $email !== '' ? $email : 'info@example.com';
}

function site_mail_from_name(): string
{
    $from = trim((string) (setting_get('mail_from_name') ?? ''));
    if ($from !== '') {
        return $from;
    }
    return site_name();
}

function site_logo_path(): string
{
    return trim((string) (setting_get('site_logo_path') ?? ''));
}

/** True when an admin-uploaded logo is set (not the bundled placeholder). */
function site_has_logo(): bool
{
    return site_logo_path() !== '';
}

function site_logo_url(): string
{
    $path = site_logo_path();
    if ($path === '') {
        $path = 'assets/img/logo-sdc.svg';
    }
    return media_url($path);
}

function site_favicon_path(): ?string
{
    $path = trim((string) (setting_get('site_favicon_path') ?? ''));
    return $path !== '' ? $path : null;
}

function site_favicon_url(): string
{
    $path = site_favicon_path();
    return $path !== null ? media_url($path) : '';
}
