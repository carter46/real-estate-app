<?php
/**
 * CSRF protection helpers (used by forms in later phases).
 */

declare(strict_types=1);

function csrf_token_key(): string
{
    return (string) app_config('security.csrf_token_key', '_csrf_token');
}

function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return '';
    }

    $key = csrf_token_key();
    if (empty($_SESSION[$key]) || !is_string($_SESSION[$key])) {
        $_SESSION[$key] = bin2hex(random_bytes(32));
    }

    return $_SESSION[$key];
}

function csrf_field(): string
{
    $name = e(csrf_token_key());
    $token = e(csrf_token());
    return '<input type="hidden" name="' . $name . '" value="' . $token . '">';
}

function csrf_verify(?string $token = null): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return false;
    }

    $key = csrf_token_key();
    $sessionToken = $_SESSION[$key] ?? '';
    $provided = $token ?? ($_POST[$key] ?? '');

    if (!is_string($sessionToken) || !is_string($provided) || $sessionToken === '' || $provided === '') {
        return false;
    }

    return hash_equals($sessionToken, $provided);
}

/** Rotate CSRF token after privileged auth events (login / setup). */
function csrf_rotate(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }
    $_SESSION[csrf_token_key()] = bin2hex(random_bytes(32));
}
