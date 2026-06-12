<?php

declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailerException;
use Psr\Log\LoggerInterface;

/**
 * MailService
 *
 * Thin wrapper around PHPMailer that resolves templates, applies the global
 * "from" address, and dispatches email messages. Logging is performed for
 * every send attempt regardless of success.
 */
final class MailService
{
    public function __construct(
        private readonly array $mailConfig,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Send an email using a named template.
     *
     * @param  string               $to        Recipient email address.
     * @param  string               $template  Template key (matches config/mail.php templates map).
     * @param  array<string, mixed> $data      Variables passed to the template view.
     * @param  string|null          $subject   Overrides the template's default subject when provided.
     * @param  string[]             $cc        Optional CC addresses.
     * @param  string[]             $bcc       Optional BCC addresses.
     *
     * @throws \RuntimeException  On mailer initialisation or send failure.
     */
    public function send(
        string $to,
        string $template,
        array $data = [],
        ?string $subject = null,
        array $cc = [],
        array $bcc = [],
    ): void {
        $templatePath = $this->resolveTemplatePath($template);
        [$defaultSubject, $htmlBody, $textBody] = $this->renderTemplate($templatePath, $data);

        $mail = $this->buildMailer();

        try {
            $mail->addAddress($to);

            foreach ($cc as $ccAddr) {
                $mail->addCC($ccAddr);
            }

            foreach ($bcc as $bccAddr) {
                $mail->addBCC($bccAddr);
            }

            $mail->Subject = $subject ?? $defaultSubject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = $textBody;

            $mail->send();

            $this->logger->info('Mail sent.', ['to' => $to, 'template' => $template]);
        } catch (MailerException $e) {
            $this->logger->error('Mail send failed.', [
                'to'       => $to,
                'template' => $template,
                'error'    => $e->getMessage(),
            ]);
            throw new \RuntimeException('Could not send email: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Send a raw email without a template.
     *
     * @param  string  $to
     * @param  string  $subject
     * @param  string  $htmlBody
     * @param  string  $textBody
     */
    public function sendRaw(
        string $to,
        string $subject,
        string $htmlBody,
        string $textBody = '',
    ): void {
        $mail = $this->buildMailer();

        try {
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = $textBody !== '' ? $textBody : strip_tags($htmlBody);

            $mail->send();

            $this->logger->info('Raw mail sent.', ['to' => $to, 'subject' => $subject]);
        } catch (MailerException $e) {
            $this->logger->error('Raw mail send failed.', [
                'to'      => $to,
                'subject' => $subject,
                'error'   => $e->getMessage(),
            ]);
            throw new \RuntimeException('Could not send email: ' . $e->getMessage(), 0, $e);
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Construct and configure a PHPMailer instance from config.
     */
    private function buildMailer(): PHPMailer
    {
        $cfg = $this->mailConfig;
        $smtp = $cfg['mailers']['smtp'] ?? [];

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->SMTPDebug  = SMTP::DEBUG_OFF;
        $mail->Host       = $smtp['host'] ?? 'localhost';
        $mail->Port       = (int) ($smtp['port'] ?? 25);
        $mail->SMTPSecure = $smtp['encryption'] ?? '';
        $mail->CharSet    = PHPMailer::CHARSET_UTF8;
        $mail->isHTML(true);

        if (!empty($smtp['username'])) {
            $mail->SMTPAuth = true;
            $mail->Username = $smtp['username'];
            $mail->Password = $smtp['password'] ?? '';
        }

        $from = $cfg['from'] ?? [];
        $mail->setFrom(
            $from['address'] ?? 'noreply@bizcore.local',
            $from['name'] ?? 'BizCore ERP',
        );

        if (!empty($cfg['reply_to']['address'])) {
            $mail->addReplyTo(
                $cfg['reply_to']['address'],
                $cfg['reply_to']['name'] ?? '',
            );
        }

        return $mail;
    }

    /**
     * Resolve the absolute filesystem path to a template file.
     */
    private function resolveTemplatePath(string $template): string
    {
        $templatesMap = $this->mailConfig['templates'] ?? [];

        // Allow callers to pass either a key ('password_reset') or direct slug.
        $slug = $templatesMap[$template] ?? $template;

        $basePath = $this->mailConfig['templates_path']
            ?? dirname(__DIR__, 2) . '/resources/views/emails';

        $path = rtrim($basePath, '/') . '/' . $slug . '.php';

        if (!file_exists($path)) {
            throw new \RuntimeException("Mail template not found: {$path}");
        }

        return $path;
    }

    /**
     * Render a PHP template file and return [subject, htmlBody, textBody].
     *
     * The template file must define $subject, $html, and optionally $text.
     *
     * @param  array<string, mixed> $data
     * @return array{0: string, 1: string, 2: string}
     */
    private function renderTemplate(string $templatePath, array $data): array
    {
        // Extract variables into the template scope.
        extract($data, EXTR_SKIP);

        // Initialise defaults so templates can safely assume they exist.
        $subject = '';
        $html    = '';
        $text    = '';

        ob_start();
        require $templatePath;
        ob_end_clean();

        return [$subject, $html, $text];
    }
}
