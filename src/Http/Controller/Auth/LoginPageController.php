<?php
namespace App\Http\Controller\Auth;

use App\Infrastructure\View\View;

final class LoginPageController
{
    public function index(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        $html = View::renderToString('auth/login/index', [
            'title' => 'Inloggen | Slimmer met AI',
        ]);
        return new \GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }
} 