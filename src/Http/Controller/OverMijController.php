<?php
namespace App\Http\Controller;

use App\Infrastructure\View\View;

final class OverMijController
{
    public function index(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        $html = View::renderToString('over-mij/index', [
            'title' => 'Over Mij | Slimmer met AI',
        ]);
        return new \GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }
} 