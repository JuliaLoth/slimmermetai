<?php
namespace App\Http\Controller;

use App\Infrastructure\View\View;

final class HomeController
{
    public function index(): void
    {
        $title = 'SlimmerMetAI - Praktische AI-tools voor Nederlandse professionals';
        View::render('home/index', [
            'title' => $title,
        ]);
    }
} 