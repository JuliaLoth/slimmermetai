<?php

namespace App\Http\Controller;

use App\Infrastructure\View\View;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Response;

final class MyCoursesController
{
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $html = View::renderToString('mijn-cursussen/index', [
            'title' => 'Mijn Cursussen | Slimmer met AI',
        ]);
        return new Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }
}
