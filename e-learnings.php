<?php
require_once __DIR__ . '/bootstrap.php';

use App\Infrastructure\View\View;

ob_start();
?>
<section class="page-hero">
  <h1>AI E-learnings</h1>
  <p>Leer hoe je AI effectief inzet in je werk.</p>
</section>
<section class="section">
  <h2>Beschikbare cursussen</h2>
  <ul>
    <li>AI Basics – €149,95</li>
    <li>Prompt Engineering – €199,95</li>
    <li>Workflow Automatisering – €179,95</li>
  </ul>
</section>
<?php
$content = ob_get_clean();

View::render('layout/main', [
    'title' => 'E-learnings | Slimmer met AI',
    'content' => $content,
]); 