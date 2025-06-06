<?php

namespace App\Http\Controller\Legacy;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Response;
use App\Infrastructure\View\View;

final class CUsersJuliaDPIMEDIAAIdocSlimmermetaiSitePublicHtmlApiEnvDumpController
{
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        // TODO: verplaats business-logica uit het oorspronkelijke bestand.
        $html = View::renderToString('legacy/cusersjuliadpimediaaidocslimmermetaisitepublichtmlapienvdump');
        return new Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }
}
