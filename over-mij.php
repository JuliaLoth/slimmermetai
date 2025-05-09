<?php
require_once __DIR__ . '/bootstrap.php';
use App\Infrastructure\View\View;

$content = '<h1>Over Mij</h1><p>Mijn naam is Julia en ik bouw SlimmerMetAI.</p>';

View::render('layout/main', [
    'title' => 'Over Mij | Slimmer met AI',
    'content' => $content,
]); 