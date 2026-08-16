<?php
/**
 * Centralized mailer — Brevo API primary when configured; SMTP / PHP mail fallbacks.
 */

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * True when a usable Brevo API key is present.
 */
function mail_brevo_configured(): bool
{
    $key = trim((string) app_config('mail.brevo_api_key', ''));
    return $key !== '' && $key !== 'YOUR_BREVO_API_KEY';
}

/**
 * True when SMTP host is set (enough to attempt SMTP delivery).
 */
function mail_smtp_configured(): bool
{
    return trim((string) app_config('mail.smtp_host', '')) !== '';
}

/**
 * Send an email using configured transport.
 *
 * Drivers:
 * - log: write to storage/logs/mail.log (staging / local)
 * - brevo: Brevo REST API when key is set; otherwise SMTP (if configured) or PHPMailer —
 *          never waits on Brevo when the API key is missing
 * - mail: PHPMailer using the PHP mail() transport (isMail)
 * - smtp: PHPMailer SMTP
 *
 * @param list<string>|string $to
 * @return array{ok: bool, error: ?string}
 */
function send_mail(string|array $to, string $subject, string $htmlBody, string $textBody = ''): array
{
    $recipients = is_array($to) ? $to : [$to];
    $valid = [];
    foreach ($recipients as $email) {
        if (is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $valid[] = $email;
        }
    }
    if ($valid === []) {
        return ['ok' => false, 'error' => 'No valid recipient address.'];
    }

    $driver = strtolower((string) app_config('mail.driver', 'brevo'));
    $text = $textBody !== '' ? $textBody : strip_tags($htmlBody);

    if ($driver === 'log') {
        $line = sprintf(
            "[%s] MAIL from=%s <%s> to=%s subject=%s\n%s\n",
            date('c'),
            site_mail_from_name(),
            (string) app_config('mail.from_email', 'noreply@example.com'),
            implode(',', $valid),
            $subject,
            $text
        );
        $logDir = APP_ROOT . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        @file_put_contents($logDir . '/mail.log', $line, FILE_APPEND);
        return ['ok' => true, 'error' => null];
    }

    if ($driver === 'brevo') {
        if (mail_brevo_configured()) {
            $brevo = send_mail_via_brevo($valid, $subject, $htmlBody, $text);
            if ($brevo['ok']) {
                return $brevo;
            }
            mail_log('BREVO FAIL then fallback reason=' . ($brevo['error'] ?? 'unknown') . ' to=' . implode(',', $valid) . ' subject=' . $subject);
        } else {
            mail_log('BREVO SKIP (api key not configured) to=' . implode(',', $valid) . ' subject=' . $subject);
        }

        if (mail_smtp_configured()) {
            mail_log('FALLBACK_TO_SMTP to=' . implode(',', $valid) . ' subject=' . $subject);
            $smtp = send_mail_via_smtp($valid, $subject, $htmlBody, $text);
            if ($smtp['ok']) {
                mail_log('SMTP OK to=' . implode(',', $valid) . ' subject=' . $subject);
                return $smtp;
            }
            mail_log('SMTP FAIL to=' . implode(',', $valid) . ' subject=' . $subject . ' error=' . ($smtp['error'] ?? 'unknown'));
        }

        mail_log('FALLBACK_TO_PHPMAILER_MAIL to=' . implode(',', $valid) . ' subject=' . $subject);
        $fallback = send_mail_via_php($valid, $subject, $htmlBody, $text);
        if ($fallback['ok']) {
            mail_log('PHPMailer isMail OK to=' . implode(',', $valid) . ' subject=' . $subject);
            return $fallback;
        }
        mail_log('PHPMailer isMail FAIL to=' . implode(',', $valid) . ' subject=' . $subject . ' error=' . ($fallback['error'] ?? 'unknown'));
        return [
            'ok' => false,
            'error' => 'Email delivery failed. Check storage/logs/mail.log (Brevo / SMTP / PHPMailer).',
        ];
    }

    if ($driver === 'mail') {
        return send_mail_via_php($valid, $subject, $htmlBody, $text);
    }

    if ($driver === 'smtp') {
        if (!mail_smtp_configured()) {
            return ['ok' => false, 'error' => 'mail.smtp_host is not set.'];
        }
        return send_mail_via_smtp($valid, $subject, $htmlBody, $text);
    }

    return ['ok' => false, 'error' => 'Unknown mail.driver. Use brevo, mail, smtp, or log.'];
}

/**
 * Append a line to storage/logs/mail.log (and PHP error_log).
 */
function mail_log(string $message): void
{
    $line = '[' . date('c') . '] ' . $message;
    error_log('[SDC mail] ' . $message);
    $logDir = APP_ROOT . '/storage/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    @file_put_contents($logDir . '/mail.log', $line . "\n", FILE_APPEND | LOCK_EX);
}

/**
 * Best-effort public / server IP for Brevo IP-limit diagnostics.
 */
function mail_server_ip_hint(): string
{
    $hints = [];
    foreach (['SERVER_ADDR', 'LOCAL_ADDR'] as $key) {
        $v = trim((string) ($_SERVER[$key] ?? ''));
        if ($v !== '') {
            $hints[] = $key . '=' . $v;
        }
    }
    return $hints !== [] ? implode(' ', $hints) : 'server_ip=unknown';
}

/**
 * Parse Brevo JSON error body into a readable reason (IP limits, auth, sender, etc.).
 *
 * @return array{code:?string, message:?string, summary:string}
 */
function mail_brevo_parse_error(string $responseBody, int $httpCode): array
{
    $code = null;
    $message = null;
    $decoded = json_decode($responseBody, true);
    if (is_array($decoded)) {
        $code = isset($decoded['code']) ? (string) $decoded['code'] : null;
        $message = isset($decoded['message']) ? (string) $decoded['message'] : null;
        if ($message === null && isset($decoded['error'])) {
            $message = is_string($decoded['error']) ? $decoded['error'] : json_encode($decoded['error']);
        }
    }

    $blob = strtolower($responseBody . ' ' . (string) $message . ' ' . (string) $code);
    $hints = [];
    if ($httpCode === 401 || str_contains($blob, 'unauthorized') || str_contains($blob, 'api-key') || str_contains($blob, 'apikey')) {
        $hints[] = 'auth/api-key issue';
    }
    if ($httpCode === 402 || str_contains($blob, 'credit') || str_contains($blob, 'quota') || str_contains($blob, 'plan')) {
        $hints[] = 'account credits/plan limit';
    }
    if ($httpCode === 403 || str_contains($blob, 'permission') || str_contains($blob, 'forbidden')) {
        $hints[] = 'forbidden / insufficient permissions (or IP not allowed)';
    }
    if (
        $httpCode === 429
        || str_contains($blob, 'rate')
        || str_contains($blob, 'too many')
        || str_contains($blob, 'ip address')
        || str_contains($blob, 'ip_address')
        || str_contains($blob, 'blocked')
        || str_contains($blob, 'whitelist')
        || str_contains($blob, 'unauthorised ip')
        || str_contains($blob, 'unauthorized ip')
        || str_contains($blob, 'not allowed from this ip')
    ) {
        $hints[] = 'rate limit (HTTP 429) or IP restriction';
    }
    if (str_contains($blob, 'sender') || str_contains($blob, 'from') || str_contains($blob, 'not verified')) {
        $hints[] = 'sender/from address not verified';
    }
    if ($httpCode === 400 && $hints === []) {
        $hints[] = 'bad request / invalid payload';
    }

    $parts = [];
    if ($code !== null && $code !== '') {
        $parts[] = 'code=' . $code;
    }
    if ($message !== null && $message !== '') {
        $parts[] = $message;
    } elseif ($responseBody !== '') {
        $parts[] = substr($responseBody, 0, 800);
    } else {
        $parts[] = 'HTTP ' . $httpCode;
    }
    if ($hints !== []) {
        $parts[] = 'hint=' . implode('; ', $hints);
    }

    return [
        'code' => $code,
        'message' => $message,
        'summary' => implode(' | ', $parts),
    ];
}

/**
 * Brevo Transactional Email API (v3).
 *
 * @param list<string> $recipients
 * @return array{ok: bool, error: ?string}
 */
function send_mail_via_brevo(array $recipients, string $subject, string $htmlBody, string $textBody): array
{
    $apiKey = trim((string) app_config('mail.brevo_api_key', ''));
    $fromEmail = (string) app_config('mail.from_email', 'noreply@example.com');
    $ipHint = mail_server_ip_hint();

    if ($apiKey === '' || $apiKey === 'YOUR_BREVO_API_KEY') {
        $err = 'mail.brevo_api_key is not set.';
        mail_log('BREVO FAIL missing_api_key to=' . implode(',', $recipients) . ' subject=' . $subject . ' ' . $ipHint);
        return ['ok' => false, 'error' => $err];
    }

    $to = [];
    foreach ($recipients as $email) {
        $to[] = ['email' => $email];
    }

    $payload = [
        'sender' => [
            'name' => site_mail_from_name(),
            'email' => $fromEmail,
        ],
        'to' => $to,
        'subject' => $subject,
        'htmlContent' => $htmlBody,
        'textContent' => $textBody,
    ];

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        mail_log('BREVO FAIL encode_payload subject=' . $subject . ' ' . $ipHint);
        return ['ok' => false, 'error' => 'Failed to encode Brevo payload.'];
    }

    $url = 'https://api.brevo.com/v3/smtp/email';
    $responseBody = '';
    $httpCode = 0;
    $transport = 'curl';
    $rateHeaders = [];

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'accept: application/json',
                'content-type: application/json',
                'api-key: ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HEADERFUNCTION => static function ($curl, string $headerLine) use (&$rateHeaders): int {
                if (preg_match('/^(x-sib-ratelimit-(?:limit|remaining|reset))\s*:\s*(.+)$/i', $headerLine, $m)) {
                    $rateHeaders[strtolower($m[1])] = trim($m[2]);
                }
                return strlen($headerLine);
            },
        ]);
        $responseBody = (string) curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);
        if ($curlErr !== '' && $httpCode === 0) {
            mail_log(
                'BREVO FAIL curl_error=' . $curlErr
                . ' to=' . implode(',', $recipients)
                . ' from=' . $fromEmail
                . ' subject=' . $subject
                . ' ' . $ipHint
            );
            return ['ok' => false, 'error' => 'Brevo curl error: ' . $curlErr];
        }
    } else {
        $transport = 'fopen';
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", [
                    'accept: application/json',
                    'content-type: application/json',
                    'api-key: ' . $apiKey,
                ]),
                'content' => $json,
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);
        $responseBody = (string) @file_get_contents($url, false, $ctx);
        if (isset($http_response_header) && is_array($http_response_header)) {
            if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
                $httpCode = (int) $m[1];
            }
            foreach ($http_response_header as $hdr) {
                if (preg_match('/^(x-sib-ratelimit-(?:limit|remaining|reset))\s*:\s*(.+)$/i', $hdr, $m)) {
                    $rateHeaders[strtolower($m[1])] = trim($m[2]);
                }
            }
        }
    }

    $rateLog = '';
    if ($rateHeaders !== []) {
        $rateLog = ' ratelimit_limit=' . ($rateHeaders['x-sib-ratelimit-limit'] ?? 'n/a')
            . ' ratelimit_remaining=' . ($rateHeaders['x-sib-ratelimit-remaining'] ?? 'n/a')
            . ' ratelimit_reset=' . ($rateHeaders['x-sib-ratelimit-reset'] ?? 'n/a');
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        $messageId = '';
        $decoded = json_decode($responseBody, true);
        if (is_array($decoded) && !empty($decoded['messageId'])) {
            $messageId = (string) $decoded['messageId'];
        }
        mail_log(
            'BREVO OK http=' . $httpCode
            . ' transport=' . $transport
            . ' messageId=' . ($messageId !== '' ? $messageId : 'n/a')
            . ' to=' . implode(',', $recipients)
            . ' from=' . $fromEmail
            . ' subject=' . $subject
            . ' ' . $ipHint
            . $rateLog
        );
        return ['ok' => true, 'error' => null];
    }

    $parsed = mail_brevo_parse_error($responseBody, $httpCode);
    mail_log(
        'BREVO REJECT http=' . $httpCode
        . ' transport=' . $transport
        . ' to=' . implode(',', $recipients)
        . ' from=' . $fromEmail
        . ' subject=' . $subject
        . ' ' . $ipHint
        . $rateLog
        . ' detail=' . $parsed['summary']
        . ' raw=' . substr($responseBody, 0, 1200)
    );

    $userError = 'Brevo API rejected the message (HTTP ' . $httpCode . ')';
    if ($parsed['message'] !== null && $parsed['message'] !== '') {
        $userError .= ': ' . $parsed['message'];
    }
    return ['ok' => false, 'error' => $userError];
}

/**
 * Ensure PHPMailer classes are loaded (Composer vendor or bundled PHPMailer/).
 */
function mail_ensure_phpmailer(): bool
{
    if (class_exists(PHPMailer::class)) {
        return true;
    }
    $pm = APP_ROOT . '/PHPMailer';
    foreach (['Exception.php', 'SMTP.php', 'PHPMailer.php'] as $file) {
        $path = $pm . '/' . $file;
        if (is_readable($path)) {
            require_once $path;
        }
    }
    return class_exists(PHPMailer::class);
}

/**
 * Send via PHPMailer using the PHP mail() transport (isMail).
 * Requires the bundled PHPMailer library — does not use raw mail() alone.
 *
 * @param list<string> $recipients
 * @return array{ok: bool, error: ?string}
 */
function send_mail_via_php(array $recipients, string $subject, string $htmlBody, string $textBody): array
{
    if (!mail_ensure_phpmailer()) {
        mail_log('PHPMailer missing — place files in PHPMailer/ or run composer install');
        return ['ok' => false, 'error' => 'PHPMailer is not available. Deploy the PHPMailer/ folder or run composer install.'];
    }

    $fromEmail = (string) app_config('mail.from_email', 'noreply@example.com');
    $fromName = site_mail_from_name();

    try {
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->isMail();
        $mail->setFrom($fromEmail, $fromName);
        foreach ($recipients as $email) {
            $mail->addAddress($email);
        }
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody;
        $mail->send();
        mail_log('PHPMailer isMail OK to=' . implode(',', $recipients) . ' subject=' . $subject);
        return ['ok' => true, 'error' => null];
    } catch (MailException | Throwable $e) {
        mail_log('PHPMailer isMail FAIL to=' . implode(',', $recipients) . ' subject=' . $subject . ' error=' . $e->getMessage());
        return ['ok' => false, 'error' => 'PHPMailer mail transport failed: ' . $e->getMessage()];
    }
}

/**
 * Send via PHPMailer SMTP (mail.smtp_* settings).
 *
 * @param list<string> $recipients
 * @return array{ok: bool, error: ?string}
 */
function send_mail_via_smtp(array $recipients, string $subject, string $htmlBody, string $textBody): array
{
    if (!mail_ensure_phpmailer()) {
        return [
            'ok' => false,
            'error' => 'PHPMailer is not available. Deploy the PHPMailer/ folder or run composer install.',
        ];
    }

    try {
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host = (string) app_config('mail.smtp_host', '');
        $mail->Port = (int) app_config('mail.smtp_port', 587);
        $mail->SMTPAuth = true;
        $mail->Username = (string) app_config('mail.smtp_user', '');
        $mail->Password = (string) app_config('mail.smtp_pass', '');

        $encryption = (string) app_config('mail.smtp_encryption', 'tls');
        if ($encryption === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = false;
            $mail->SMTPAutoTLS = false;
        }

        $mail->setFrom(
            (string) app_config('mail.from_email', 'noreply@example.com'),
            site_mail_from_name()
        );
        foreach ($recipients as $email) {
            $mail->addAddress($email);
        }
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody;
        $mail->send();

        return ['ok' => true, 'error' => null];
    } catch (MailException | Throwable $e) {
        mail_log('PHPMailer SMTP FAIL to=' . implode(',', $recipients) . ' subject=' . $subject . ' error=' . $e->getMessage());
        return ['ok' => false, 'error' => 'PHPMailer SMTP failed: ' . $e->getMessage()];
    }
}
