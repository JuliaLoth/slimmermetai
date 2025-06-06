<?php

namespace App\Http\Controller;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Response;

final class CourseImagesGeneratorController
{
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        // TODO: implementeer gegenereerde course images logic.
        $html = '<h1>Course Images Generator</h1><p>Deze functionaliteit wordt binnenkort gemigreerd naar een CLI-command.</p>';
        return new Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }
}
