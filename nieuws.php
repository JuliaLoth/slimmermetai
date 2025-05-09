<?php
require_once __DIR__ . '/bootstrap.php';
use App\Infrastructure\View\View;

$content = '<h1>Nieuws</h1><p>Laatste updates en artikelen over AI.</p>';

View::render('layout/main', [
    'title' => 'Nieuws | Slimmer met AI',
    'content' => $content,
]); 