<?php

namespace App\Infrastructure\Security;

use Psr\Http\Message\ServerRequestInterface;

class CsrfProtection
{
    private static ?self $instance = null;
    private string $tokenName = 'csrf_token';
    private string $headerName = 'X-CSRF-Token';
    private string $cookieName = 'csrf_token';
    private int $tokenLifetime = 3600;
    private int $tokenLength = 32;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function generateToken(): string
    {
        $token = bin2hex(random_bytes($this->tokenLength));

        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION[$this->tokenName] = [
            'token' => $token,
            'expires' => time() + $this->tokenLifetime
        ];

        $this->setTokenCookie($token);
        return $token;
    }

    public function getToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION[$this->tokenName])) {
            $tokenData = $_SESSION[$this->tokenName];
            if (is_array($tokenData) && $tokenData['expires'] > time()) {
                return $tokenData['token'];
            }
        }

        return $this->generateToken();
    }

    public function validateToken(?string $token = null, ?ServerRequestInterface $request = null): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION[$this->tokenName])) {
            return false;
        }

        $sessionData = $_SESSION[$this->tokenName];
        if (!is_array($sessionData) || $sessionData['expires'] <= time()) {
            unset($_SESSION[$this->tokenName]);
            return false;
        }

        $sessionToken = $sessionData['token'];

        // Get token from provided parameter or request
        if ($token === null && $request !== null) {
            $token = $this->getTokenFromRequest($request);
        }

        return $token !== null && hash_equals($sessionToken, $token);
    }

    public function invalidateToken(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        unset($_SESSION[$this->tokenName]);
        if (isset($_COOKIE[$this->cookieName])) {
            setcookie($this->cookieName, '', time() - 3600, '/', '', true, true);
        }
    }

    private function setTokenCookie(string $token): void
    {
        setcookie($this->cookieName, $token, [
            'expires' => time() + $this->tokenLifetime,
            'path' => '/',
            'secure' => true,
            'httponly' => false,
            'samesite' => 'Strict'
        ]);
    }

    private function getTokenFromRequest(ServerRequestInterface $request): ?string
    {
        // Check POST data
        $parsedBody = $request->getParsedBody();
        if (is_array($parsedBody) && isset($parsedBody[$this->tokenName])) {
            return $parsedBody[$this->tokenName];
        }

        // Check headers
        if ($request->hasHeader($this->headerName)) {
            return $request->getHeaderLine($this->headerName);
        }

        // Check query parameters
        $queryParams = $request->getQueryParams();
        if (isset($queryParams[$this->tokenName])) {
            return $queryParams[$this->tokenName];
        }

        // Check cookies
        $cookies = $request->getCookieParams();
        if (isset($cookies[$this->cookieName])) {
            return $cookies[$this->cookieName];
        }

        return null;
    }

    public function verifyRequestToken(ServerRequestInterface $request, bool $exitOnFailure = false): bool
    {
        if (!$this->validateToken(null, $request)) {
            if ($exitOnFailure) {
                http_response_code(403);
                echo 'Invalid CSRF token';
                exit;
            }
            return false;
        }
        return true;
    }

    // Setters
    public function setTokenName(string $n): void
    {
        $this->tokenName = $n;
    }

    public function setHeaderName(string $n): void
    {
        $this->headerName = $n;
    }

    public function setCookieName(string $n): void
    {
        $this->cookieName = $n;
    }

    public function setTokenLifetime(int $sec): void
    {
        $this->tokenLifetime = $sec;
    }

    public function setTokenLength(int $len): void
    {
        $this->tokenLength = $len;
    }
}
