<?php

declare(strict_types=1);

namespace App\Mail;

use Psr\Log\LoggerInterface;

class MailManager
{
    public function __construct(
        private readonly array           $config,
        private readonly LoggerInterface $logger,
    ) {}

    public function send(string $to, string $subject, string $body, array $options = []): bool
    {
        try {
            $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);

            $mailer->isSMTP();
            $mailer->Host       = $this->config['host']       ?? 'localhost';
            $mailer->Port       = (int) ($this->config['port'] ?? 587);
            $mailer->SMTPSecure = $this->config['encryption']  ?? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;

            if (!empty($this->config['username'])) {
                $mailer->SMTPAuth = true;
                $mailer->Username = $this->config['username'];
                $mailer->Password = $this->config['password'] ?? '';
            }

            $fromAddress = $this->config['from']['address'] ?? 'noreply@bizcore.local';
            $fromName    = $this->config['from']['name']    ?? 'BizCore ERP';

            $mailer->setFrom($fromAddress, $fromName);
            $mailer->addAddress($to);
            $mailer->isHTML(true);
            $mailer->Subject = $subject;
            $mailer->Body    = $body;

            $mailer->send();
            $this->logger->info("Mail sent to {$to}: {$subject}");
            return true;
        } catch (\Throwable $e) {
            $this->logger->error("Mail failed to {$to}: " . $e->getMessage());
            return false;
        }
    }

    public function queue(string $to, string $subject, string $body): void
    {
        $this->logger->info("Mail queued to {$to}: {$subject}");
    }
}
