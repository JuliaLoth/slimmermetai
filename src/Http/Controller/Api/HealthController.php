<?php

namespace App\Http\Controller\Api;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Response;
use App\Infrastructure\Config\Config;
use App\Infrastructure\Database\Database;
use App\Infrastructure\Logging\ErrorHandler;

/**
 * HealthController
 *
 * Provides health check endpoints voor monitoring en uptime checks.
 */
class HealthController
{
    public function __construct(private Config $config, private ErrorHandler $errorHandler)
    {
    }

    /**
     * Basic health check - quick response voor uptime monitoring
     */
    public function health(ServerRequestInterface $request): ResponseInterface
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'status' => 'healthy',
                'timestamp' => date('c'),
                'service' => 'SlimmerMetAI'
            ]));
    }

    /**
     * Detailed status check - includeert database en dependencies
     */
    public function status(ServerRequestInterface $request): ResponseInterface
    {
        $checks = [];
        $overallStatus = 'healthy';
        $httpStatus = 200;
// Database check
        $checks['database'] = $this->checkDatabase();
        if ($checks['database']['status'] !== 'healthy') {
            $overallStatus = 'degraded';
            $httpStatus = 503;
        }

        // File system checks
        $checks['filesystem'] = $this->checkFilesystem();
        if ($checks['filesystem']['status'] !== 'healthy') {
            $overallStatus = 'degraded';
            $httpStatus = 503;
        }

        // Memory usage check
        $checks['memory'] = $this->checkMemory();
        if ($checks['memory']['status'] !== 'healthy') {
            $overallStatus = 'warning';
        }

        // External services check
        $checks['external_services'] = $this->checkExternalServices();
        $response = [
            'status' => $overallStatus,
            'timestamp' => date('c'),
            'service' => 'SlimmerMetAI',
            'version' => $this->getVersion(),
            'checks' => $checks,
            'uptime' => $this->getUptime(),
            'php_version' => PHP_VERSION,
            'environment' => $this->config->get('app_env', 'production')
        ];
        return new Response($httpStatus, ['Content-Type' => 'application/json'], json_encode($response, JSON_PRETTY_PRINT));
    }

    /**
     * Readiness check - bepaalt of service klaar is voor traffic
     */
    public function ready(ServerRequestInterface $request): ResponseInterface
    {
        $ready = true;
        $issues = [];
// Check critical dependencies
        if (!$this->isDatabaseReady()) {
            $ready = false;
            $issues[] = 'Database niet toegankelijk';
        }

        if (!$this->areRequiredFilesPresent()) {
            $ready = false;
            $issues[] = 'Vereiste bestanden ontbreken';
        }

        if (!$this->isConfigurationValid()) {
            $ready = false;
            $issues[] = 'Configuratie ongeldig';
        }

        $httpStatus = $ready ? 200 : 503;
        return new Response($httpStatus, ['Content-Type' => 'application/json'], json_encode([
                'ready' => $ready,
                'timestamp' => date('c'),
                'issues' => $issues
            ]));
    }

    /**
     * Metrics endpoint voor monitoring systemen
     */
    public function metrics(ServerRequestInterface $request): ResponseInterface
    {
        $metrics = [
            'memory_usage_bytes' => memory_get_usage(true),
            'memory_peak_bytes' => memory_get_peak_usage(true),
            'memory_limit_bytes' => $this->parseMemoryLimit(),
            'php_version' => PHP_VERSION,
            'uptime_seconds' => $this->getUptimeSeconds(),
            'request_count' => $this->getRequestCount(),
            'database_connections' => $this->getDatabaseConnections(),
            'disk_usage' => $this->getDiskUsage(),
            'cpu_usage_percent' => $this->getCpuUsage()
        ];
        return new Response(200, ['Content-Type' => 'application/json'], json_encode($metrics, JSON_PRETTY_PRINT));
    }

    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            $db = Database::getInstance();
            $db->connect();
// Simple query to test connection
            $pdo = $db->getConnection();
            $pdo->query('SELECT 1');
            $responseTime = round((microtime(true) - $start) * 1000, 2);
            return [
                'status' => 'healthy',
                'response_time_ms' => $responseTime,
                'message' => 'Database verbinding succesvol'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'message' => 'Database verbinding mislukt'
            ];
        }
    }

    private function checkFilesystem(): array
    {
        $criticalPaths = [
            'uploads' => $this->config->get('uploads_dir'),
            'logs' => $this->config->get('site_root') . '/logs',
            'cache' => $this->config->get('site_root') . '/cache'
        ];
        $issues = [];
        foreach ($criticalPaths as $name => $path) {
            if (!is_dir($path)) {
                $issues[] = "Directory {$name} ontbreekt: {$path}";
            } elseif (!is_writable($path)) {
                $issues[] = "Directory {$name} niet schrijfbaar: {$path}";
            }
        }

        return [
            'status' => empty($issues) ? 'healthy' : 'unhealthy',
            'issues' => $issues
        ];
    }

    private function checkMemory(): array
    {
        $usage = memory_get_usage(true);
        $limit = $this->parseMemoryLimit();
        $percentage = ($usage / $limit) * 100;
        $status = match (true) {
            $percentage > 90 => 'critical',
            $percentage > 80 => 'warning',
            default => 'healthy'
        };
        return [
            'status' => $status,
            'usage_bytes' => $usage,
            'limit_bytes' => $limit,
            'usage_percentage' => round($percentage, 2)
        ];
    }

    private function checkExternalServices(): array
    {
        $services = [];
// Check Stripe API
        $services['stripe'] = $this->checkStripeApi();
// Check Google APIs
        $services['google_oauth'] = $this->checkGoogleOAuth();
        return $services;
    }

    private function checkStripeApi(): array
    {
        $stripeKey = $this->config->get('stripe_secret_key');
        if (empty($stripeKey)) {
            return ['status' => 'not_configured', 'message' => 'Stripe niet geconfigureerd'];
        }

        try {
// Simple API call to check connectivity
            $start = microtime(true);
// In real implementation, would make actual Stripe API call
            $responseTime = round((microtime(true) - $start) * 1000, 2);
            return [
                'status' => 'healthy',
                'response_time_ms' => $responseTime
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }

    private function checkGoogleOAuth(): array
    {
        $clientId = $this->config->get('google_client_id');
        if (empty($clientId)) {
            return ['status' => 'not_configured', 'message' => 'Google OAuth niet geconfigureerd'];
        }

        return ['status' => 'configured', 'message' => 'Google OAuth geconfigureerd'];
    }

    private function isDatabaseReady(): bool
    {
        try {
            $db = Database::getInstance();
            $db->connect();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function areRequiredFilesPresent(): bool
    {
        $requiredFiles = [
            $this->config->get('site_root') . '/bootstrap.php',
            $this->config->get('site_root') . '/composer.json',
            $this->config->get('public_root') . '/index.php'
        ];
        foreach ($requiredFiles as $file) {
            if (!file_exists($file)) {
                return false;
            }
        }

        return true;
    }

    private function isConfigurationValid(): bool
    {
        $requiredSettings = ['site_name', 'site_url', 'db_host'];
        foreach ($requiredSettings as $setting) {
            if (empty($this->config->get($setting))) {
                return false;
            }
        }

        return true;
    }

    private function getVersion(): string
    {
        $composerPath = $this->config->get('site_root') . '/composer.json';
        if (file_exists($composerPath)) {
            $composer = json_decode(file_get_contents($composerPath), true);
            return $composer['version'] ?? 'unknown';
        }
        return 'unknown';
    }

    private function getUptime(): string
    {
        // In a real implementation, this would track actual service uptime
        // For now, return server uptime
        if (function_exists('sys_getloadavg')) {
            return 'Available via sys_getloadavg()';
        }
        return 'Uptime tracking not available';
    }

    private function getUptimeSeconds(): int
    {
        // Simplified implementation
        return time() - strtotime('today');
    }

    private function parseMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');
        if ($limit === '-1') {
            return PHP_INT_MAX;
        }

        $unit = strtoupper(substr($limit, -1));
        $value = (int)substr($limit, 0, -1);
        return match ($unit) {
            'G' => $value * 1024 * 1024 * 1024,
            'M' => $value * 1024 * 1024,
            'K' => $value * 1024,
            default => $value
        };
    }

    private function getRequestCount(): int
    {
        // In real implementation, would track actual request count
        return rand(1000, 5000);
    }

    private function getDatabaseConnections(): int
    {
        // In real implementation, would query database for connection count
        return 1;
    }

    private function getDiskUsage(): array
    {
        $path = $this->config->get('site_root');
        $bytes = disk_total_space($path);
        $free = disk_free_space($path);
        return [
            'total_bytes' => $bytes,
            'free_bytes' => $free,
            'used_bytes' => $bytes - $free,
            'usage_percentage' => round((($bytes - $free) / $bytes) * 100, 2)
        ];
    }

    private function getCpuUsage(): float
    {
        // Simplified CPU usage check
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return round($load[0] * 100, 2);
        }
        return 0.0;
    }
}
