<?php
// dev-router.php
// Start de server met:
//   php -S 127.0.0.1:8000 -t public_html public_html/dev-router.php
// Dit script zorgt ervoor dat:
//   • Bestaande statische bestanden rechtstreeks door de ingebouwde server
//     geserveerd worden (css/js/svg/ico/…) zodat we geen 404 krijgen.
//   • Alle overige requests naar index.php gaan, waar de routering van
//     onze applicatie plaatsvindt.

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// 1. Bestaat het gevraagde bestand fysiek in de public_html dir?
$requested = __DIR__ . $uri;
if ($uri !== '/' && file_exists($requested)) {
    // Laat de built-in server het zelf afhandelen
    return false;
}

// 2. Anders alle verzoeken naar de front-controller
require __DIR__ . '/index.php'; 