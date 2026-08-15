<?php
/**
 * Centralized mailer (PHPMailer + Brevo/SMTP via config).
 */

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Send an email using configured transport.
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

    $driver = (string) app_config('mail.driver', 'smtp');

    if ($driver === 'log') {
        $line = sprintf(
            "[%s] MAIL to=%s subject=%s\n%s\n",
            date('c'),
            implode(',', $valid),
            $subject,
            $textBody !== '' ? $textBody : strip_tags($htmlBody)
        );
        $logDir = APP_ROOT . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        @file_put_contents($logDir . '/mail.log', $line, FILE_APPEND);
        return ['ok' => true, 'error' => null];
    }

    if (!class_exists(PHPMailer::class)) {
        return [
            'ok' => false,
            'error' => 'PHPMailer is not installed. Run: composer install',
        ];
    }

    try {
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';

        if ($driver === 'smtp') {
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
        } else {
            $mail->isMail();
        }

        $mail->setFrom(
            (string) app_config('mail.from_email', 'noreply@example.com'),
            (string) app_config('mail.from_name', 'SDC')
        );

        foreach ($valid as $email) {
            $mail->addAddress($email);
        }

        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody !== '' ? $textBody : strip_tags($htmlBody);
        $mail->send();

        return ['ok' => true, 'error' => null];
    } catch (MailException | Throwable $e) {
        error_log('[SDC] mail error: ' . $e->getMessage());
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}
