<?php
namespace App\Http\Controller\Auth;

use App\Infrastructure\View\View;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

final class RegisterPageController
{
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $html = View::renderToString('auth/register/index', [
            'title' => 'Registreren | Slimmer met AI',
        ]);
        return new Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }
} 