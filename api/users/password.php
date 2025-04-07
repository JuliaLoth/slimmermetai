<?php
/**
 * Wachtwoord wijzigen API endpoint voor SlimmerMetAI.com
 * Staat gebruikers toe hun wachtwoord te wijzigen
 */

// Definieer root path als dat nog niet is gedaan
if (!defined('SITE_ROOT')) {
    define('SITE_ROOT', dirname(dirname(dirname(dirname(__FILE__))))); // Ga drie niveaus omhoog vanuit api/users/password.php
}

// Include de API configuratie
require_once dirname(dirname(__FILE__)) . '/config.php';

// Start sessie voor CSRF
session_start();

// Alleen POST requests toestaan
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_response('Methode niet toegestaan', 405);
}

// Authenticatie vereist
$user = auth_check();

// Haal JSON data uit request body
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Controleer verplichte velden
if (!isset($data['currentPassword']) || !isset($data['newPassword'])) {
    error_response('Huidig wachtwoord en nieuw wachtwoord zijn verplicht');
}

$current_password = $data['currentPassword'];
$new_password = $data['newPassword'];

// Valideer nieuw wachtwoord
if (strlen($new_password) < PASSWORD_MIN_LENGTH) {
    error_response('Wachtwoord moet minimaal ' . PASSWORD_MIN_LENGTH . ' tekens lang zijn');
}

// Controleer of wachtwoord voldoet aan complexiteitseisen
if (!preg_match('/[A-Z]/', $new_password) || !preg_match('/[a-z]/', $new_password) || 
    !preg_match('/[0-9]/', $new_password) || !preg_match('/[^A-Za-z0-9]/', $new_password)) {
    error_response('Wachtwoord moet minimaal één hoofdletter, één kleine letter, één cijfer en één speciaal teken bevatten');
}

try {
    // Haal huidige wachtwoord op
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $result = $stmt->fetch();
    
    if (!$result) {
        error_response('Gebruiker niet gevonden', 404);
    }
    
    // Controleer huidig wachtwoord
    if (!password_verify($current_password, $result['password'])) {
        error_response('Huidig wachtwoord is onjuist', 401);
    }
    
    // Hash nieuw wachtwoord
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
    
    // Update wachtwoord
    $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$hashed_password, $user['id']]);
    
    // Verwijder alle refresh tokens van deze gebruiker (dwingt uitloggen op andere apparaten)
    $stmt = $pdo->prepare("DELETE FROM refresh_tokens WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    
    // Maak nieuw refresh token voor huidige sessie
    $refresh_token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));
    
    // Sla nieuw refresh token op
    $stmt = $pdo->prepare("INSERT INTO refresh_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$user['id'], $refresh_token, $expires_at]);
    
    // Zet nieuwe cookie
    setcookie('refresh_token', $refresh_token, [
        'expires' => strtotime('+7 days'),
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    
    // Genereer nieuw JWT token
    $user_data = [
        'id' => $user['id'],
        'email' => $user['email'],
        'name' => $user['name'],
        'role' => $user['role']
    ];
    
    $token = generate_jwt_token($user_data);
    
    // Stuur succesvolle response
    json_response([
        'success' => true,
        'message' => 'Wachtwoord succesvol gewijzigd',
        'token' => $token
    ]);
    
} catch (PDOException $e) {
    // Log de fout
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("Wachtwoord wijzigen mislukt: " . $e->getMessage());
    }
    error_response('Er is een fout opgetreden bij het wijzigen van je wachtwoord', 500);
} 