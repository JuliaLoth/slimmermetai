<?php
// Proxy bestand in public_html voor Reset Password endpoint
$paths = [
    dirname(__DIR__, 3) . '/api/auth/reset-password.php',
    dirname(__DIR__, 4) . '/api/auth/reset-password.php',
    dirname(__DIR__, 2) . '/api/auth/reset-password.php'
];
$orig = null;
foreach ($paths as $p) {
    if (is_file($p)) { $orig = $p; break; }
}
if (!$orig) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error'=>'Originele reset-password.php niet gevonden', 'attempted'=>$paths]);
    exit;
}
require $orig; 