<?php
/**
 * Baseline security headers for public + admin responses.
 */

declare(strict_types=1);

function security_headers_send(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    // CSP deferred while Tailwind CDN + Google Fonts are external (see DEPLOY.md).
}
