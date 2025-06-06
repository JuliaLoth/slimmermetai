<?php

namespace App\Http\Controller\Auth;

use App\Infrastructure\View\View;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Response;

final class LoginSuccessPageController
{
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $html = View::renderToString('auth/login-success/index', [
            'title' => 'Inloggen gelukt | Slimmer met AI',
        ]);
        return new Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }
}
