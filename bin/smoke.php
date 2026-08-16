<?php
/**
 * CLI smoke checks for Phase 7 (no browser required).
 *
 * Usage: php bin/smoke.php
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$failures = 0;

function smoke_assert(bool $ok, string $label): void
{
    global $failures;
    echo ($ok ? '[pass] ' : '[fail] ') . $label . PHP_EOL;
    if (!$ok) {
        $failures++;
    }
}

smoke_assert(version_compare(PHP_VERSION, '8.1.0', '>='), 'PHP >= 8.1');
smoke_assert(function_exists('app_config'), 'app_config loaded');
smoke_assert(slugify('Hello World!') === 'hello-world', 'slugify');
smoke_assert(media_url('//evil.example/x') === '', 'media_url rejects protocol-relative');
smoke_assert(media_url('https://cdn.example/a.jpg') === 'https://cdn.example/a.jpg', 'media_url allows https');
smoke_assert(!str_contains(redirect_safe_url('https://evil.example/phish'), 'evil.example'), 'redirect blocks external host');
smoke_assert(public_property_statuses() !== [], 'public statuses configured');

try {
    db()->query('SELECT 1');
    smoke_assert(true, 'database ping');
} catch (Throwable $e) {
    smoke_assert(false, 'database ping: ' . $e->getMessage());
}

$token = csrf_token();
smoke_assert($token !== '' && strlen($token) === 64, 'csrf token generated');

$uploads = (string) app_config('uploads.properties_dir', dirname(__DIR__) . '/uploads/properties');
smoke_assert(is_dir($uploads) || @mkdir($uploads, 0755, true), 'uploads dir');

$validated = inquiry_validate([
    'first_name' => 'Test',
    'last_name' => 'User',
    'email' => 'not-an-email',
    'message' => 'Hi',
], 'contact');
smoke_assert($validated['ok'] === false, 'inquiry_validate rejects bad email');

$prop = property_validate_input([
    'title' => 'Smoke Estate',
    'status' => 'draft',
    'price' => '1000000',
    'address_line' => '1 Test Rd',
    'city' => 'Aspen',
]);
smoke_assert($prop['ok'] === true, 'property_validate_input draft ok');

$archived = property_validate_input([
    'title' => 'Archived Attempt',
    'status' => 'archived',
    'address_line' => '2 Test Rd',
    'city' => 'Aspen',
]);
smoke_assert($archived['ok'] === false, 'cannot archive via form status alone');

echo PHP_EOL . ($failures === 0 ? "Smoke OK\n" : "Smoke FAILED ($failures)\n");
exit($failures === 0 ? 0 : 1);
