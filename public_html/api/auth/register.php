<?php
// Proxy bestand in public_html voor Registratie endpoint
// Probeert meerdere paden om het oorspronkelijke script buiten public_html te vinden

$possiblePaths = [
    dirname(__DIR__, 3) . '/api/auth/register.php',   // root/api/auth/register.php (meest voorkomend)
    dirname(__DIR__, 4) . '/api/auth/register.php',   // één niveau verder omhoog (voor shared hosting)
    dirname(__DIR__, 2) . '/api/auth/register.php'    // twee niveaus omhoog (fallback)
];

$original = null;
foreach ($possiblePaths as $path) {
    if (is_file($path)) {
        $original = $path;
        break;
    }
}

if (!$original) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Interne serverfout: origineel register.php niet gevonden',
        'attemptedPaths' => $possiblePaths
    ]);
    exit;
}

require $original; 