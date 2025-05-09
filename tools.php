<?php
require_once __DIR__ . '/bootstrap.php';

use App\Infrastructure\View\View;

$content = '<h1>AI Tools</h1><p>Hier vind je onze handige AI-tools.</p>';

View::render('layout/main', [
    'title' => 'AI Tools | Slimmer met AI',
    'content' => $content,
]); 