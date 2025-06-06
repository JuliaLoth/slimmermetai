<?php
// router.php
// Gebruik dit bestand in combinatie met: php -S 127.0.0.1:80 router.php
// zodat Friendly URLs (bv. /tools) en alias-bestanden (/tools.php) via index.php worden afgehandeld

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

$publicPath = __DIR__ . '/public_html';
$requested = $publicPath . $uri;

// DEV-ASSET ROUTE HANDLER: Serve Vite bundles in development (alleen in development mode)
if ((getenv('APP_ENV') === 'local' || getenv('APP_ENV') === 'development') && str_starts_with($uri, '/dev-asset/')) {
    $assetPath = substr($uri, strlen('/dev-asset/'));
    $assetFile = $publicPath . '/assets/js/' . $assetPath;
    
    if (file_exists($assetFile)) {
        // Set appropriate content type
        $ext = pathinfo($assetFile, PATHINFO_EXTENSION);
        $contentType = match($ext) {
            'js' => 'application/javascript',
            'css' => 'text/css',
            'map' => 'application/json',
            default => 'text/plain'
        };
        
        header('Content-Type: ' . $contentType);
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        readfile($assetFile);
        return true;
    }
    
    // 404 voor niet-bestaande dev assets
    http_response_code(404);
    echo "Dev asset not found: $assetPath";
    return true;
}

// Bestaat het gevraagde bestand? Laat de ingebouwde server het direct verwerken.
if ($uri !== '/' && file_exists($requested)) {
    // Voor .php-bestanden die bestaan wil je de server het direct laten uitvoeren.
    return false;
}

// Anders alle requests naar onze front-controller
require $publicPath . '/index.php'; 