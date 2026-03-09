<?php

namespace Laragent\Tools;

use Illuminate\Support\Facades\Mail;

class MailerTool extends BaseTool
{
    public function name(): string
    {
        return 'send_email';
    }

    public function description(): string
    {
        return 'Send an email via the application\'s configured mail system.';
    }

    public function parameters(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'to'        => ['type' => 'string', 'description' => 'Recipient email address'],
                'subject'   => ['type' => 'string', 'description' => 'Email subject'],
                'body'      => ['type' => 'string', 'description' => 'Email body (plain text or basic HTML)'],
                'from_name' => ['type' => 'string', 'description' => 'Sender name (defaults to app name)'],
            ],
            'required'   => ['to', 'subject', 'body'],
        ];
    }

    public function execute(array $params): string
    {
        $to = $params['to'] ?? '';
        $subject = $params['subject'] ?? '';
        $body = $params['body'] ?? '';
        $fromName = $params['from_name'] ?? config('app.name', 'Laragent');

        // Validate email
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return $this->error("Invalid email address: {$to}");
        }

        if (empty($subject) || empty($body)) {
            return $this->error('Subject and body are required');
        }

        try {
            Mail::raw($body, function ($message) use ($to, $subject, $fromName) {
                $message->to($to)->subject($subject)->from(
                    config('mail.from.address', 'noreply@example.com'),
                    $fromName
                );
            });

            return "Email sent successfully to {$to}";
        } catch (\Exception $e) {
            return $this->error('Failed to send email: ' . $e->getMessage());
        }
    }
}
