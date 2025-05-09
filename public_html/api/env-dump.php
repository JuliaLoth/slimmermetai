<?php
// Tijdelijke diagnostische endpoint – verwijder zodra databaseverbinding werkt.

require_once __DIR__ . '/config.php'; // Laad dezelfde configuratie als de API-endpoints

header('Content-Type: text/plain');

echo "===== SlimmerMetAI .env Diagnostic =====\n";
echo "DB_HOST = " . DB_HOST . "\n";
echo "DB_NAME = " . DB_NAME . "\n";
echo "DB_USER = " . DB_USER . "\n";
echo "DB_PASS = " . DB_PASS . "\n";
echo "DEBUG_MODE = " . (DEBUG_MODE ? 'true' : 'false') . "\n";

echo "\n📐 Via parse_ini_file loaded? ";
// Toon of $_ENV gevuld is met sleutel DB_USER
var_export(isset($_ENV['DB_USER']));

echo "\n===========================================\n"; 