<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

interface MailerInterface
{
    /**
     * Stuur een mail.
     *
     * @param string $to Het e-mailadres van de ontvanger
     * @param string $subject Het onderwerp van de e-mail
     * @param string $html De HTML-inhoud van de e-mail
     * @param string|null $from Het afzenderadres (optioneel)
     * @return bool True als de e-mail succesvol is verstuurd, false anders
     */
    public function send(string $to, string $subject, string $html, ?string $from = null): bool;
}
