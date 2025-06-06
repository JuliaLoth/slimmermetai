<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Infrastructure\View\View;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ElearningDashboardController
{
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $data = [
            'title' => 'E-Learning Platform | Slimmer met AI',
            'meta_description' => 'Ontwikkel je AI-vaardigheden met onze interactieve cursussen',
            'page' => 'e-learning-dashboard',
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => '/'],
                ['label' => 'E-learning Platform', 'url' => null]
            ]
        ];

        return View::renderToResponse('e-learning/dashboard', $data);
    }
}
