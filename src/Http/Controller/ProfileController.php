<?php
namespace App\Http\Controller;

use App\Infrastructure\View\View;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Response;

final class ProfileController
{
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        // TODO: Vervang met echte gebruikersgegevens via de AuthService zodra beschikbaar
        $html = View::renderToString('profiel/index', [
            'title' => 'Profiel | Slimmer met AI',
        ]);
        return new Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }
} 