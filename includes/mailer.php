<?php
/**
 * Centralized mailer — Brevo Transactional API primary, PHP mail() fallback.
 */

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Send an email using configured transport.
 *
 * Drivers:
 * - log: write to storage/logs/mail.log (staging / local)
 * - brevo: Brevo REST API, then PHP mail() on failure
 * - mail: PHP mail() / PHPMailer isMail() only
 * - smtp: optional legacy SMTP (not used for Brevo)
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
        $brevo = send_mail_via_brevo($valid, $subject, $htmlBody, $text);
        if ($brevo['ok']) {
            return $brevo;
        }
        error_log('[SDC] Brevo API failed, falling back to PHP mail: ' . ($brevo['error'] ?? 'unknown'));
        $fallback = send_mail_via_php($valid, $subject, $htmlBody, $text);
        if ($fallback['ok']) {
            return $fallback;
        }
        return [
            'ok' => false,
            'error' => 'Email delivery failed (Brevo and PHP mail). Check configuration and logs.',
        ];
    }

    if ($driver === 'mail') {
        return send_mail_via_php($valid, $subject, $htmlBody, $text);
    }

    if ($driver === 'smtp') {
        return send_mail_via_smtp($valid, $subject, $htmlBody, $text);
    }

    return ['ok' => false, 'error' => 'Unknown mail.driver. Use brevo, mail, smtp, or log.'];
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
    if ($apiKey === '') {
        return ['ok' => false, 'error' => 'mail.brevo_api_key is not set.'];
    }

    $to = [];
    foreach ($recipients as $email) {
        $to[] = ['email' => $email];
    }

    $payload = [
        'sender' => [
            'name' => site_mail_from_name(),
            'email' => (string) app_config('mail.from_email', 'noreply@example.com'),
        ],
        'to' => $to,
        'subject' => $subject,
        'htmlContent' => $htmlBody,
        'textContent' => $textBody,
    ];

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return ['ok' => false, 'error' => 'Failed to encode Brevo payload.'];
    }

    $url = 'https://api.brevo.com/v3/smtp/email';
    $responseBody = '';
    $httpCode = 0;

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
        ]);
        $responseBody = (string) curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);
        if ($curlErr !== '' && $httpCode === 0) {
            return ['ok' => false, 'error' => 'Brevo curl error: ' . $curlErr];
        }
    } else {
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
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $httpCode = (int) $m[1];
        }
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        return ['ok' => true, 'error' => null];
    }

    $detail = $responseBody !== '' ? $responseBody : ('HTTP ' . $httpCode);
    error_log('[SDC] Brevo API HTTP ' . $httpCode . ': ' . $detail);
    return ['ok' => false, 'error' => 'Brevo API rejected the message (HTTP ' . $httpCode . ').'];
}

/**
 * PHP mail() via PHPMailer when available, otherwise native mail().
 *
 * @param list<string> $recipients
 * @return array{ok: bool, error: ?string}
 */
function send_mail_via_php(array $recipients, string $subject, string $htmlBody, string $textBody): array
{
    $fromEmail = (string) app_config('mail.from_email', 'noreply@example.com');
    $fromName = site_mail_from_name();

    if (class_exists(PHPMailer::class)) {
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
            return ['ok' => true, 'error' => null];
        } catch (MailException | Throwable $e) {
            error_log('[SDC] PHPMailer mail() error: ' . $e->getMessage());
            // fall through to native mail()
        }
    }

    $toHeader = implode(', ', $recipients);
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . sprintf('%s <%s>', addslashes($fromName), $fromEmail),
        'Reply-To: ' . $fromEmail,
    ];
    $ok = @mail($toHeader, $encodedSubject, $htmlBody, implode("\r\n", $headers));
    if ($ok) {
        return ['ok' => true, 'error' => null];
    }
    return ['ok' => false, 'error' => 'PHP mail() failed.'];
}

/**
 * Optional legacy SMTP (not used for Brevo — prefer mail.driver=brevo).
 *
 * @param list<string> $recipients
 * @return array{ok: bool, error: ?string}
 */
function send_mail_via_smtp(array $recipients, string $subject, string $htmlBody, string $textBody): array
{
    if (!class_exists(PHPMailer::class)) {
        return [
            'ok' => false,
            'error' => 'PHPMailer is not installed. Run: composer install',
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
        error_log('[SDC] SMTP mail error: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Email delivery failed. Check mail configuration and logs.'];
    }
}
