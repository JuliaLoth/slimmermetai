<?php
// Proxy bestand in public_html voor Google token verificatie
// Stuurt de request door naar de eigenlijke API in /api/auth/google-token.php (buiten public_html)

// Zet pad naar originele script
$original = dirname(__DIR__, 3) . '/api/auth/google-token.php';

if (!is_file($original)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Interne serverfout: original google-token.php niet gevonden']);
    exit;
}

require $original; 