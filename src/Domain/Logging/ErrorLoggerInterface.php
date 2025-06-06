<?php

namespace App\Domain\Logging;

/**
 * ErrorLoggerInterface
 *
 * Contract voor loggen in de applicatie. Hiermee kunnen we de concrete
 * ErrorHandler loskoppelen van de rest van de code en eenvoudiger testen
 * of vervangen in de toekomst.
 */
interface ErrorLoggerInterface
{
    /**
     * Log een foutmelding.
     *
     * @param string $message  Menselijke beschrijving van de fout
     * @param array<string,mixed> $context Extra contextinformatie
     * @param string $severity Handmatige severity override (default 'ERROR')
     */
    public function logError(string $message, array $context = [], string $severity = 'ERROR'): void;
/**
     * Log een waarschuwing.
     *
     * @param string $message
     * @param array<string,mixed> $context
     */
    public function logWarning(string $message, array $context = []): void;
/**
     * Log een informatief bericht.
     *
     * @param string $message
     * @param array<string,mixed> $context
     */
    public function logInfo(string $message, array $context = []): void;
/**
     * Registreer globale PHP error/exception/shutdown handlers.
     */
    public function registerGlobalHandlers(): void;
}
