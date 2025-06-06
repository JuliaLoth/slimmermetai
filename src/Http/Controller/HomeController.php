<?php

namespace App\Http\Controller;

use App\Infrastructure\View\View;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Response;

final class HomeController
{
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        // Controleer of dit een OAuth callback is van Google
        $queryParams = $request->getQueryParams();
        if (isset($queryParams['code']) && isset($queryParams['state'])) {
        // Dit is een Google OAuth callback - handle het
            return $this->handleGoogleCallback($queryParams);
        }

        // Normale homepage
        $title = 'SlimmerMetAI - Praktische AI-tools voor Nederlandse professionals';
        $html = View::renderToString('home/index', [
            'title' => $title,
        ]);
        return new Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }

    private function handleGoogleCallback(array $queryParams): ResponseInterface
    {
        try {
// Start sessie voor callback handling
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $code = $queryParams['code'];
            $state = $queryParams['state'];
// Voor development: eenvoudige success redirect
            $redirectUrl = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : '/dashboard';
// Debug info voor development
            error_log("Google OAuth callback received - Code: " . substr($code, 0, 20) . "...");
            error_log("Redirecting to: " . $redirectUrl);
// Voor nu: eenvoudige redirect naar dashboard met success bericht
            // In productie zou hier de volledige OAuth flow afgehandeld worden

            // Clean up session
            unset($_SESSION['google_oauth_state']);
            unset($_SESSION['google_oauth_state_expiry']);
            unset($_SESSION['code_verifier']);
            unset($_SESSION['redirect_after_login']);
// Success redirect
            return new Response(302, ['Location' => $redirectUrl . '?google_login=success'], '');
        } catch (\Exception $e) {
            error_log("Google OAuth callback error: " . $e->getMessage());
        // Error redirect naar login met error
            return new Response(302, ['Location' => '/login?error=oauth_failed'], '');
        }
    }
}
