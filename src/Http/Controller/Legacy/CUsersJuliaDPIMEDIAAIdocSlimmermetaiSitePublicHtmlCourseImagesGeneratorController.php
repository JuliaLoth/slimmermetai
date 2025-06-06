<?php
namespace App\Http\Controller\Legacy;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Response;
use App\Infrastructure\View\View;

final class CUsersJuliaDPIMEDIAAIdocSlimmermetaiSitePublicHtmlCourseImagesGeneratorController
{
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        // TODO: verplaats business-logica uit het oorspronkelijke bestand.
        $html = View::renderToString('legacy/cusersjuliadpimediaaidocslimmermetaisitepublichtmlcourseimagesgenerator');
        return new Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }
}