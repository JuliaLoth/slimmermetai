<?php

namespace App\Infrastructure\Mail;

use App\Infrastructure\Config\Config;
use App\Domain\Logging\ErrorLoggerInterface;

/**
 * Mailer – eenvoudige SMTP mailer op basis van PHPMailer.
 * Voor productie kun je PHPMailer via Composer toevoegen:
 *   composer require phpmailer/phpmailer
 * Om vendor-afhankelijkheid te vermijden is hier een lichte wrapper; als PHPMailer
 * aanwezig is wordt die gebruikt, anders fallback naar PHP mail().
 */
final class Mailer
{
    private string $host;
    private int $port;
    private bool $secure;
    private string $user;
    private string $pass;
    private string $from;
/**
     * Stuur een mail.
     * @param string       $to
     * @param string       $subject
     * @param string       $html
     * @param string|null  $from
     * @return bool
     */
    public function send(string $to, string $subject, string $html, ?string $from = null): bool
    {
        $from = $from ?? $this->from;
// Gebruik PHPMailer indien beschikbaar
        if (class_exists('\PHPMailer\\PHPMailer\\PHPMailer')) {
            return $this->sendViaPhpMailer($to, $subject, $html, $from);
        }
        // Fallback – eenvoudige headers
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: ' . $from,
        ];
        $ok = mail($to, $subject, $html, implode("\r\n", $headers));
        if (!$ok) {
            $this->logger->logError('Mail verzenden mislukt', compact('to', 'subject'));
        }
        return $ok;
    }

    private function sendViaPhpMailer(string $to, string $subject, string $html, string $from): bool
    {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
        //Server settings
            $mail->isSMTP();
            $mail->Host       = $this->host;
            $mail->Port       = $this->port;
            $mail->SMTPAuth   = !empty($this->user);
            if ($mail->SMTPAuth) {
                $mail->Username = $this->user;
                $mail->Password = $this->pass;
            }
            $mail->SMTPSecure = $this->secure ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        //Recipients
            $mail->setFrom($from, 'SlimmerMetAI');
            $mail->addAddress($to);
        //Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $html;
            $mail->AltBody = strip_tags($html);
            $mail->send();
            return true;
        } catch (\Throwable $e) {
            $this->logger->logError('PHPMailer fout', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Wordt automatisch geautowired via DI-container.
     */
    public function __construct(Config $cfg, private ErrorLoggerInterface $logger)
    {
        $this->host   = $cfg->get('smtp_host', 'localhost');
        $this->port   = (int)$cfg->get('smtp_port', 25);
        $this->secure = $cfg->getTyped('smtp_secure', 'bool', false);
        $this->user   = $cfg->get('smtp_user', '');
        $this->pass   = $cfg->get('smtp_pass', '');
        $this->from   = $cfg->get('mail_from', 'noreply@example.com');
    }
}
