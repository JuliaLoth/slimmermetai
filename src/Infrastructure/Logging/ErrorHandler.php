<?php

namespace App\Infrastructure\Logging;

use App\Domain\Logging\ErrorLoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SlackWebhookHandler;
use Monolog\Formatter\LineFormatter;

use function container;

class ErrorHandler implements ErrorLoggerInterface
{
    private string $logPath;
    private ?Logger $logger = null;
    private string $requestId;

    /**
     * Legacy helper die een instantie uit de container haalt.
     */
    public static function getInstance(): self
    {
        return container()->get(self::class);
    }

    public function __construct()
    {
        $this->logPath = defined('SITE_ROOT') ? SITE_ROOT . '/logs/' : dirname(dirname(dirname(__DIR__))) . '/logs/';
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
        $this->requestId = bin2hex(random_bytes(8));
        if (!headers_sent()) {
            header('X-Request-Id: ' . $this->requestId);
        }
        $this->initLogger();
    }

    public function logError(string $m, array $c = [], string $s = 'ERROR'): void
    {
        $this->log($s, $m, $c);
    }

    public function logWarning(string $m, array $c = []): void
    {
        $this->log('WARNING', $m, $c);
    }

    public function logInfo(string $m, array $c = []): void
    {
        $this->log('INFO', $m, $c);
    }

    private function log(string $sev, string $m, array $ctx = []): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $url = $_SERVER['REQUEST_URI'] ?? 'N/A';
        $ctx = array_merge(['ip' => $ip,'url' => $url,'request_id' => $this->requestId], $ctx);
        if ($this->logger) {
            $level = $this->mapSeverityToLevel($sev);
            $this->logger->log($level, $m, $ctx);
        } else {
            $logFile = $this->logPath . strtolower($sev) . '.log';
            $ts = date('Y-m-d H:i:s');
            file_put_contents($logFile, "[$ts][$sev][$ip][$url] $m " . json_encode($ctx) . PHP_EOL, FILE_APPEND);
        }
    }

    private function initLogger(): void
    {
        $this->logger = new Logger('app');
        $file = $this->logPath . 'app-' . date('Y-m-d') . '.log';
        $fh = new StreamHandler($file, Logger::DEBUG);
        $fh->setFormatter(new LineFormatter(null, null, true, true));
        $this->logger->pushHandler($fh);

        $wh = getenv('SLACK_WEBHOOK_URL');
        if ($wh) {
            // Simplified SlackWebhookHandler to avoid parameter type issues
            try {
                $sh = new SlackWebhookHandler($wh);
                $this->logger->pushHandler($sh);
            } catch (\Throwable $e) {
                // Fallback: continue without Slack logging if there are issues
                $this->logError('Failed to initialize Slack webhook handler: ' . $e->getMessage());
            }
        }
    }

    private function mapSeverityToLevel(string $s): mixed
    {
        return match (strtoupper($s)) {
            'DEBUG' => Logger::DEBUG,
            'INFO' => Logger::INFO,
            'WARNING' => Logger::WARNING,
            'ERROR' => Logger::ERROR,
            'CRITICAL','FATAL' => Logger::CRITICAL,
            default => Logger::ERROR
        };
    }

    public function registerGlobalHandlers(): void
    {
        set_error_handler([$this,'handleError']);
        set_exception_handler([$this,'handleException']);
        register_shutdown_function([$this,'handleShutdown']);
    }

    public function handleError(int $no, string $str, string $file, int $line): bool
    {
        $sev = $this->getErrorSeverity($no);
        $this->logError($str, ['file' => $file,'line' => $line,'type' => $sev]);
        if (in_array($no, [E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR,E_USER_ERROR])) {
            $this->showErrorPage();
        }
        return true;
    }

    public function handleException(\Throwable $ex): void
    {
        $this->logError($ex->getMessage(), ['file' => $ex->getFile(),'line' => $ex->getLine(),'trace' => $ex->getTraceAsString()]);
        $this->respondError($ex->getMessage());
    }

    public function handleShutdown(): void
    {
        $err = error_get_last();
        if ($err && in_array($err['type'], [E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR])) {
            $this->logError($err['message'], ['file' => $err['file'],'line' => $err['line'],'type' => $this->getErrorSeverity($err['type'])]);
            $this->respondError($err['message']);
        }
    }

    private function getErrorSeverity(int $e): string
    {
        return match ($e) {
            E_ERROR,E_CORE_ERROR,E_COMPILE_ERROR,E_USER_ERROR=>'FATAL',
            E_WARNING,E_CORE_WARNING,E_COMPILE_WARNING,E_USER_WARNING=>'WARNING',
            E_NOTICE,E_USER_NOTICE=>'NOTICE',
            E_STRICT=>'STRICT',
            E_DEPRECATED,E_USER_DEPRECATED=>'DEPRECATED',
            default=>'UNKNOWN'
        };
    }

    private function showErrorPage(): void
    {
        $this->respondError();
    }

    private function respondError(string $msg = 'Interne serverfout'): void
    {
        if ($this->isApiRequest()) {
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            echo json_encode(['error' => true,'message' => $msg,'request_id' => $this->requestId]);
            exit;
        }
        if (!headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                echo '<h1>Er is een fout opgetreden</h1><p>De applicatie heeft een onverwachte fout ondervonden. Deze fout is gelogd.</p>';
            } else {
                header('Location: ' . (defined('SITE_URL') ? SITE_URL : '') . '/500.php');
                exit;
            }
        }
    }

    private function isApiRequest(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($uri, '/api/') === 0) {
            return true;
        }
        $acc = $_SERVER['HTTP_ACCEPT'] ?? '';
        return strpos($acc, 'application/json') !== false;
    }
}
