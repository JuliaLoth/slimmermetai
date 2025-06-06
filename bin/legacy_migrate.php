#!/usr/bin/env php
<?php
/**
 * legacy_migrate.php
 *
 * Hulpscript om legacy standalone PHP-bestanden in public_html automatisch
 * om te zetten naar PSR-7 controllers + view-templates.
 *
 * ── Werkwijze ──────────────────────────────────────────────────────────────
 * 1. Scant PUBLIC_ROOT voor *.php-bestanden (excl. index.php).
 * 2. Voor ieder bestand maakt het script:
 *    • src/Http/Controller/Legacy/<Name>Controller.php
 *    • resources/views/legacy/<name>.php (kopie origineel bestand)
 *    • Voeg of update routes/legacy.php met een $r->addRoute-regel.
 * 3. Legacy-bronbestand blijft bestaan tot de controller handmatig is
 *    gerefactord; daarna kan men het verwijderen.
 *
 * Uitvoeren:
 *   php bin/legacy_migrate.php [--dry-run]
 */

define('SKIP_DB', true);
require_once __DIR__ . '/../bootstrap.php';

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

const CONTROLLER_NS  = 'App\\Http\\Controller\\Legacy';
const CONTROLLER_DIR = __DIR__ . '/../src/Http/Controller/Legacy';
const VIEW_DIR       = __DIR__ . '/../resources/views/legacy';
const ROUTE_FILE     = __DIR__ . '/../routes/legacy.php';

$dryRun = in_array('--dry-run', $argv, true);

$fs = new Filesystem();
$finder = (new Finder())
    ->files()
    ->in(PUBLIC_ROOT)
    ->depth('>=0')
    ->name('*.php')
    ->notName('index.php');

$controllersCreated = 0;
$viewsCreated       = 0;
$routesAdded        = 0;

$routeLines = [];

foreach ($finder as $file) {
    $abs = str_replace('\\', '/', $file->getRealPath());
    $pub = str_replace('\\', '/', PUBLIC_ROOT);
    $relPath = ltrim(str_replace($pub, '', $abs), '/'); // bv login.php of api/foo.php
    $route = '/' . preg_replace('/\.php$/i', '', $relPath);

    // Genereer class-naam (LoginController e.d.)
    $classBase = preg_replace('/[^A-Za-z0-9]/', ' ', $route);
    $classBase = str_replace(' ', '', ucwords($classBase));
    $className = $classBase . 'Controller';
    $controllerPath = CONTROLLER_DIR . '/' . $className . '.php';
    $viewName  = strtolower($classBase);
    $viewPath  = VIEW_DIR . '/' . $viewName . '.php';

    // Controller
    if (!$fs->exists($controllerPath)) {
        $fs->mkdir(dirname($controllerPath));
        if (!$dryRun) {
            $fs->dumpFile($controllerPath, controllerStub($className, $viewName));
        }
        $controllersCreated++;
    }

    // View (kopie bestand)
    if (!$fs->exists($viewPath)) {
        $fs->mkdir(dirname($viewPath));
        if (!$dryRun) {
            $fs->copy($file->getRealPath(), $viewPath);
        }
        $viewsCreated++;
    }

    $routeLines[] = sprintf(
        "    $r->addRoute('GET', '%s', [%s::class, 'index']);",
        $route,
        CONTROLLER_NS . '\\' . $className
    );
    $routesAdded++;
}

// Route file bijwerken
if (!$dryRun) {
    $fs->mkdir(dirname(ROUTE_FILE));
    $content = "<?php\nreturn static function (\\FastRoute\\RouteCollector $r) {\n" . implode("\n", $routeLines) . "\n};\n";
    $fs->dumpFile(ROUTE_FILE, $content);
}

echo "[legacy:migrate] Controllers: $controllersCreated, Views: $viewsCreated, Routes: $routesAdded" . ($dryRun ? " (dry-run)" : "") . "\n";

// ─────────────────────────────────────────────────────────────────────────────
function controllerStub(string $className, string $viewName): string
{
    return <<<PHP
<?php
namespace App\Http\Controller\Legacy;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Response;
use App\Infrastructure\View\View;

final class {$className}
{
    public function index(ServerRequestInterface \$request): ResponseInterface
    {
        // TODO: verplaats business-logica uit het oorspronkelijke bestand.
        \$html = View::renderToString('legacy/{$viewName}');
        return new Response(200, ['Content-Type' => 'text/html; charset=utf-8'], \$html);
    }
}
PHP;
} 