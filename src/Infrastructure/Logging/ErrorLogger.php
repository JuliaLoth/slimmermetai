<?php

namespace App\Infrastructure\Logging;

use App\Domain\Logging\ErrorLoggerInterface;

/**
 * ErrorLogger - Alias voor ErrorHandler voor test compatibility
 */
class ErrorLogger implements ErrorLoggerInterface
{
    private ErrorHandler $handler;

    public function __construct()
    {
        $this->handler = new ErrorHandler();
    }

    public function logError(string $message, array $context = [], string $severity = 'ERROR'): void
    {
        $this->handler->logError($message, $context, $severity);
    }

    public function logWarning(string $message, array $context = []): void
    {
        $this->handler->logWarning($message, $context);
    }

    public function logInfo(string $message, array $context = []): void
    {
        $this->handler->logInfo($message, $context);
    }

    public function registerGlobalHandlers(): void
    {
        $this->handler->registerGlobalHandlers();
    }
}
