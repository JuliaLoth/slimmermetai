<?php

namespace App\Application\Service;

use App\Infrastructure\Mail\MailerInterface;

final class EmailService
{
    public function __construct(private MailerInterface $mailer)
    {
    }

    /**
     * Algemeen mail versturen
     */
    public function send(string $to, string $subject, string $html): bool
    {
        return $this->mailer->send($to, $subject, $html);
    }

    /* ---------------- Specifieke sjablonen ---------------- */

    public function sendVerificationEmail(string $to, string $token, string $name = ''): bool
    {
        $verifyUrl = sprintf(
            '%s/verify-email?token=%s',
            getenv('FRONTEND_URL') ?: 'https://slimmermetai.com',
            $token
        );
        $html = $this->buildTemplate(
            'Bevestig je e-mailadres',
            $name,
            $verifyUrl,
            'Verifieer E-mailadres'
        );
        return $this->send($to, 'Verifieer je e-mailadres - SlimmerMetAI', $html);
    }

    public function sendWelcomeEmail(string $to, string $name = ''): bool
    {
        $dashboardUrl = (getenv('FRONTEND_URL') ?: 'https://slimmermetai.com') . '/dashboard';
        $html = $this->buildTemplate(
            'Welkom bij SlimmerMetAI!',
            $name,
            $dashboardUrl,
            'Ga naar dashboard'
        );
        return $this->send($to, 'Welkom bij SlimmerMetAI!', $html);
    }

    public function sendPasswordResetEmail(string $to, string $token, string $name = ''): bool
    {
        $resetUrl = sprintf(
            '%s/reset-password?token=%s',
            getenv('FRONTEND_URL') ?: 'https://slimmermetai.com',
            $token
        );
        $html = $this->buildTemplate(
            'Wachtwoord resetten',
            $name,
            $resetUrl,
            'Reset wachtwoord',
            'Deze link is 1 uur geldig.'
        );
        return $this->send($to, 'Wachtwoord resetten - SlimmerMetAI', $html);
    }

    /* ---------------- Test Support Methods ---------------- */

    public function validateEmailAddress(?string $email): bool
    {
        if ($email === null || $email === '') {
            return false;
        }
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function renderTemplate(string $templateName, array $vars): string
    {
        // For test purposes - basic template rendering
        $html = "<html><body>";
        $html .= "<h1>Template: {$templateName}</h1>";
        foreach ($vars as $key => $value) {
            $html .= "<p>{$key}: {$value}</p>";
        }
        $html .= "</body></html>";
        return $html;
    }

    private array $emailQueue = [];

    public function queueEmail(string $to, string $subject, string $template, array $vars = []): void
    {
        $this->emailQueue[] = [
            'to' => $to,
            'subject' => $subject,
            'template' => $template,
            'vars' => $vars
        ];
    }

    public function getQueueSize(): int
    {
        return count($this->emailQueue);
    }

    public function processEmailQueue(): int
    {
        $processed = 0;
        foreach ($this->emailQueue as $email) {
            $html = $this->renderTemplate($email['template'], $email['vars']);
            if ($this->send($email['to'], $email['subject'], $html)) {
                $processed++;
            }
        }
        $this->emailQueue = [];
        return $processed;
    }

    public function sendBulkEmails(array $recipients, string $subject, string $template): array
    {
        $results = [];
        foreach ($recipients as $recipient) {
            $html = $this->renderTemplate($template, ['name' => $recipient['name']]);
            $success = $this->send($recipient['email'], $subject, $html);
            $results[] = ['success' => $success, 'email' => $recipient['email']];
        }
        return $results;
    }

    /* ---------------- Helper ---------------- */
    private function buildTemplate(
        string $title,
        string $name,
        string $actionUrl,
        string $buttonText,
        string $note = ''
    ): string {
        $brandUrl = getenv('FRONTEND_URL') ?: 'https://slimmermetai.com';
        $year = date('Y');
        $displayName = $name ?: 'daar';
        return <<<HTML
        <div style="max-width:600px;margin:0 auto;padding:20px;font-family:Arial,sans-serif;">
            <div style="text-align:center;margin-bottom:20px;">
                <img src="$brandUrl/images/Logo.svg" alt="SlimmerMetAI Logo" style="max-width:100px;" />
            </div>
            <div style="background:#f9f9f9;border-radius:10px;padding:20px;margin-bottom:20px;">
                <h2 style="color:#333;margin-bottom:15px;">$title</h2>
                <p style="color:#666;line-height:1.5;margin-bottom:20px;">Hallo {$displayName},</p>
                <p style="color:#666;line-height:1.5;margin-bottom:20px;">
                    Klik op de onderstaande knop om verder te gaan.
                </p>
                <div style="text-align:center;margin:30px 0;">
                    <a href="$actionUrl" 
                       style="background:#5852f2;color:#fff;padding:12px 25px;text-decoration:none;
                              border-radius:5px;font-weight:bold;display:inline-block;">
                        $buttonText
                    </a>
                </div>
                <p style="color:#666;line-height:1.5;margin-bottom:20px;">
                    Als de knop niet werkt, kopieer en plak deze URL in je browser:<br/>$actionUrl
                </p>
                <p style="color:#666;line-height:1.5;margin-top:30px;">$note</p>
            </div>
            <div style="text-align:center;color:#999;font-size:12px;">
                <p>&copy; $year SlimmerMetAI. Alle rechten voorbehouden.</p>
            </div>
        </div>
        HTML;
    }
}
