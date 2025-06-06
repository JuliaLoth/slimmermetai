<?php

namespace App\Http\Controller\Auth;

use App\Infrastructure\View\View;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Response;

final class ForgotPasswordPageController
{
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $html = View::renderToString('auth/forgot-password/index', [
            'title' => 'Wachtwoord vergeten | Slimmer met AI',
        ]);
        return new Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }
}
