<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MailService
{
    public function send(string $to, string $subject, string $body, array $data = []): void
    {
        try {
            Mail::html($body, function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });
            Log::info('Mail sent.', ['to' => $to, 'subject' => $subject]);
        } catch (\Throwable $e) {
            Log::error('Mail send failed.', ['to' => $to, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Could not send email: ' . $e->getMessage(), 0, $e);
        }
    }

    public function sendView(string $to, string $subject, string $view, array $data = []): void
    {
        try {
            Mail::send($view, $data, function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });
            Log::info('Mail sent.', ['to' => $to, 'view' => $view]);
        } catch (\Throwable $e) {
            Log::error('Mail send failed.', ['to' => $to, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Could not send email: ' . $e->getMessage(), 0, $e);
        }
    }
}
