<?php
// Proxy bestand in public_html voor Wachtwoord-vergeten endpoint
// Zoekt het originele script buiten public_html en voert het uit

$possiblePaths = [
    dirname(__DIR__, 3) . '/api/auth/forgot-password.php',  // root/api/auth/
    dirname(__DIR__, 4) . '/api/auth/forgot-password.php',  // één niveau verder
    dirname(__DIR__, 2) . '/api/auth/forgot-password.php'   // fallback
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
        'error' => 'Interne serverfout: origineel forgot-password.php niet gevonden',
        'attemptedPaths' => $possiblePaths
    ]);
    exit;
}

require $original; 