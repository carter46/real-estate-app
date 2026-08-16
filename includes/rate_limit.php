<?php
/**
 * Simple file-backed rate limiting (no Redis required).
 */

declare(strict_types=1);

function rate_limit_client_key(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    return substr(hash('sha256', $ip), 0, 32);
}

/**
 * @return array{allowed: bool, remaining: int, retry_after: int}
 */
function rate_limit_hit(string $bucket, int $maxAttempts, int $windowSeconds): array
{
    $dir = APP_ROOT . '/storage/rate_limits';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $file = $dir . '/' . preg_replace('/[^a-z0-9_\-]/i', '_', $bucket) . '_' . rate_limit_client_key() . '.json';
    $now = time();
    $data = ['start' => $now, 'count' => 0];

    if (is_readable($file)) {
        $raw = file_get_contents($file);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        if (is_array($decoded) && isset($decoded['start'], $decoded['count'])) {
            $data = [
                'start' => (int) $decoded['start'],
                'count' => (int) $decoded['count'],
            ];
        }
    }

    if (($now - $data['start']) >= $windowSeconds) {
        $data = ['start' => $now, 'count' => 0];
    }

    if ($data['count'] >= $maxAttempts) {
        $retry = max(1, $windowSeconds - ($now - $data['start']));
        return ['allowed' => false, 'remaining' => 0, 'retry_after' => $retry];
    }

    $data['count']++;
    @file_put_contents($file, json_encode($data), LOCK_EX);

    return [
        'allowed' => true,
        'remaining' => max(0, $maxAttempts - $data['count']),
        'retry_after' => 0,
    ];
}

function rate_limit_clear(string $bucket): void
{
    $file = APP_ROOT . '/storage/rate_limits/'
        . preg_replace('/[^a-z0-9_\-]/i', '_', $bucket) . '_' . rate_limit_client_key() . '.json';
    if (is_file($file)) {
        @unlink($file);
    }
}

/**
 * Honeypot: return true if the request looks like a bot filled the hidden field.
 */
function honeypot_tripped(?string $fieldName = 'website'): bool
{
    $name = $fieldName ?? 'website';
    $value = trim((string) ($_POST[$name] ?? ''));
    return $value !== '';
}

function honeypot_field(string $name = 'website'): string
{
    return '<div class="hp-field" aria-hidden="true" style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden;">'
        . '<label for="' . e($name) . '">Website</label>'
        . '<input type="text" id="' . e($name) . '" name="' . e($name) . '" value="" tabindex="-1" autocomplete="off">'
        . '</div>';
}
