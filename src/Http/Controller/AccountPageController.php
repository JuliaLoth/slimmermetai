<?php

namespace App\Http\Controller;

use App\Infrastructure\View\View;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Response;

final class AccountPageController
{
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        // TODO: Laad accountgegevens voor de ingelogde gebruiker
        $html = View::renderToString('account/index', [
            'title' => 'Account | Slimmer met AI',
        ]);
        return new Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }
}
