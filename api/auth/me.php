<?php
/**
 * Me API endpoint voor SlimmerMetAI.com
 * Haalt de gegevens van de ingelogde gebruiker op
 */

// Definieer root path als dat nog niet is gedaan
if (!defined('SITE_ROOT')) {
    define('SITE_ROOT', dirname(dirname(dirname(dirname(__FILE__))))); // Ga drie niveaus omhoog vanuit api/auth/me.php
}

// Include de API configuratie
require_once dirname(dirname(__FILE__)) . '/config.php';

// Start sessie voor CSRF
session_start();

// Alleen GET requests toestaan
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    error_response('Methode niet toegestaan', 405);
}

// Check JWT token in de Authorization header
$headers = getallheaders();
$auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if (empty($auth_header) || !preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
    error_response('Niet geautoriseerd', 401);
}

$token = $matches[1];

// Valideer token
$user = validate_token($token);

if (!$user) {
    error_response('Ongeldige of verlopen token', 401);
}

try {
    // Haal volledige gebruikersgegevens op uit de database
    $stmt = $pdo->prepare("
        SELECT id, name, email, role, email_verified, profile_picture, created_at, updated_at, last_login 
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$user['id']]);
    $user_data = $stmt->fetch();
    
    if (!$user_data) {
        error_response('Gebruiker niet gevonden', 404);
    }
    
    // Converteer email_verified naar boolean
    $user_data['email_verified'] = (bool)$user_data['email_verified'];
    
    // Voeg volledige URL toe aan profielfoto indien nodig
    if ($user_data['profile_picture'] && !preg_match('/^https?:\/\//', $user_data['profile_picture'])) {
        $user_data['profile_picture'] = SITE_URL . '/' . ltrim($user_data['profile_picture'], '/');
    }
    
    // Voeg extra gebruikersvoorkeuren toe als die bestaan
    try {
        $stmt = $pdo->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $preferences = $stmt->fetch();
        
        if ($preferences) {
            $user_data['preferences'] = $preferences;
        }
    } catch (PDOException $e) {
        // Geen voorkeuren gevonden of tabel bestaat niet
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("Voorkeuren ophalen mislukt: " . $e->getMessage());
        }
    }
    
    // Stuur gebruikersgegevens terug
    json_response([
        'success' => true,
        'user' => $user_data
    ]);
    
} catch (PDOException $e) {
    // Log de fout maar toon geen database details
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("Gebruikersgegevens ophalen mislukt: " . $e->getMessage());
    }
    error_response('Er is een fout opgetreden bij het ophalen van je gegevens', 500);
} 