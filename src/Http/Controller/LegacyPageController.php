<?php

namespace App\Http\Controller;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Response;

// Fallback definition for PHPStan compatibility
if (!defined('PUBLIC_ROOT')) {
    define('PUBLIC_ROOT', dirname(__DIR__, 3) . '/public_html');
}

/**
 * LegacyPageController
 *
 * Tijdelijke controller die oude standalone PHP-bestanden uitvoert binnen de
 * nieuwe PSR-7 / DI-architectuur.  Dit maakt het mogelijk de fallback in de
 * router te verwijderen, terwijl we pagina voor pagina refactoren.
 */
final class LegacyPageController
{
    public function render(ServerRequestInterface $request, string $legacyPath): ResponseInterface
    {
        // Sanity-check: file moet binnen PUBLIC_ROOT liggen
        $full = realpath(PUBLIC_ROOT . '/' . ltrim($legacyPath, '/'));
        if ($full === false || !str_starts_with($full, PUBLIC_ROOT) || !is_file($full)) {
            return new Response(404, [], 'Pagina niet gevonden');
        }

        // Voer bestand uit en vang output
        ob_start();
        require $full;
        $html = ob_get_clean();
        return new Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }
}
