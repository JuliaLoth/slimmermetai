<?php

namespace App\Http\Controller;

use App\Infrastructure\View\View;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Response;

final class DashboardController
{
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $googleLoginSuccess = isset($queryParams['google_login']) && $queryParams['google_login'] === 'success';
        $html = View::renderToString('dashboard/index', [
            'title' => 'Dashboard | Slimmer met AI',
            'googleLoginSuccess' => $googleLoginSuccess,
        ]);
        return new Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }
}
