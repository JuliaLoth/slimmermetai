<?php
require_once __DIR__ . '/bootstrap.php';

use App\Infrastructure\View\View;

View::render('layout/main', [
    'title'   => 'Home | Slimmer met AI',
    'content' => '<section class="page-hero"><h1>Slimmer met AI</h1><p>Praktische AI-tools en e-learnings voor Nederlandse professionals.</p><a href="/tools.php" class="btn btn-primary">Bekijk Tools</a></section>'
]);
?> 