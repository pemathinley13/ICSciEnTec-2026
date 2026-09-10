<?php

namespace App\Services;

use App\Db\Database;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Thin notification layer. Ships with driver = 'log' by default (writes to
 * email_log, does not actually send) since real SMTP credentials for the
 * college mail server aren't confirmed yet — see docs/deployment.md. Once
 * config.php sets mail.driver = 'smtp' and fills in mail.smtp_* /
 * mail.from_email, this same call site starts actually sending via the
 * vendored PHPMailer (no Composer needed — see vendor/phpmailer/) without any
 * page needing to change.
 *
 * Every attempt — sent or not — is written to email_log so nothing is lost
 * silently; that table is also part of the spec's audit/record-keeping
 * requirement (step 14).
 */
final class EmailService
{
    /**
     * @param array<string,mixed> $vars variables available to the template as $vars
     */
    public static function send(string $templateKey, string $toEmail, array $vars = [], ?int $submissionId = null): bool
    {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $mailConfig = $config['mail'];

        $templatePath = dirname(__DIR__, 2) . "/templates/emails/{$templateKey}.php";
        if (!is_file($templatePath)) {
            self::logAttempt($toEmail, $templateKey, $submissionId, 'failed', "Missing template: {$templateKey}");
            return false;
        }

        [$subject, $body] = self::renderTemplate($templatePath, $vars);

        if ($mailConfig['driver'] === 'smtp') {
            return self::sendViaSmtp($mailConfig, $toEmail, $subject, $body, $templateKey, $submissionId);
        }

        // driver === 'log': record what WOULD have been sent, for local testing.
        self::logAttempt($toEmail, $templateKey, $submissionId, 'sent', "[log driver] Subject: {$subject}");
        return true;
    }

    private static function sendViaSmtp(array $mailConfig, string $toEmail, string $subject, string $body, string $templateKey, ?int $submissionId): bool
    {
        require_once dirname(__DIR__, 2) . '/vendor/phpmailer/Exception.php';
        require_once dirname(__DIR__, 2) . '/vendor/phpmailer/PHPMailer.php';
        require_once dirname(__DIR__, 2) . '/vendor/phpmailer/SMTP.php';

        $mailer = new PHPMailer(true);
        try {
            $mailer->isSMTP();
            $mailer->Host       = $mailConfig['smtp_host'];
            $mailer->Port       = $mailConfig['smtp_port'];
            $mailer->SMTPAuth   = true;
            $mailer->Username   = $mailConfig['smtp_user'];
            $mailer->Password   = $mailConfig['smtp_pass'];
            $mailer->SMTPSecure = $mailConfig['smtp_secure'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mailer->CharSet    = 'UTF-8';

            $mailer->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
            $mailer->addAddress($toEmail);
            $mailer->isHTML(true);
            $mailer->Subject = $subject;
            $mailer->Body    = $body;
            $mailer->AltBody = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $body)));

            $mailer->send();
            self::logAttempt($toEmail, $templateKey, $submissionId, 'sent', null);
            return true;
        } catch (PHPMailerException | \Throwable $e) {
            error_log("Email send failed ({$templateKey} to {$toEmail}): " . $e->getMessage());
            self::logAttempt($toEmail, $templateKey, $submissionId, 'failed', $e->getMessage());
            return false;
        }
    }

    /** @return array{0:string,1:string} [$subject, $htmlBody] */
    private static function renderTemplate(string $path, array $vars): array
    {
        extract($vars, EXTR_SKIP);
        ob_start();
        $subject = require $path; // template file returns its subject string
        $body = ob_get_clean();
        return [is_string($subject) ? $subject : '(no subject)', $body];
    }

    private static function logAttempt(string $toEmail, string $templateKey, ?int $submissionId, string $status, ?string $error): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO email_log (to_email, template_key, submission_id, status, error_message) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$toEmail, $templateKey, $submissionId, $status, $error]);
    }
}
