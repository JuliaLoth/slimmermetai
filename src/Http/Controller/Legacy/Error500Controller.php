<?php

namespace App\Http\Controller\Legacy;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Response;
use App\Infrastructure\View\View;

final class Error500Controller
{
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $html = View::renderToString('legacy/500');
        return new Response(500, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }
}
