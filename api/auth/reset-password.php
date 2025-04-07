<?php
/**
 * Reset Password API endpoint voor SlimmerMetAI.com
 * Verwerkt wachtwoordherstel na het ontvangen van een reset token
 */

// Definieer root path als dat nog niet is gedaan
if (!defined('SITE_ROOT')) {
    define('SITE_ROOT', dirname(dirname(dirname(dirname(__FILE__))))); // Ga drie niveaus omhoog vanuit api/auth/reset-password.php
}

// Include de API configuratie
require_once dirname(dirname(__FILE__)) . '/config.php';

// Start sessie voor CSRF
session_start();

// Alleen POST requests toestaan
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_response('Methode niet toegestaan', 405);
}

// Haal JSON data uit request body
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Controleer verplichte velden
if (!isset($data['token']) || !isset($data['password'])) {
    error_response('Token en wachtwoord zijn verplicht');
}

$token = sanitize_input($data['token']);
$password = $data['password'];

// Valideer wachtwoord
if (strlen($password) < PASSWORD_MIN_LENGTH) {
    error_response('Wachtwoord moet minimaal ' . PASSWORD_MIN_LENGTH . ' tekens lang zijn');
}

// Controleer of wachtwoord voldoet aan complexiteitseisen
if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || 
    !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
    error_response('Wachtwoord moet minimaal één hoofdletter, één kleine letter, één cijfer en één speciaal teken bevatten');
}

try {
    // Zoek token in database
    $stmt = $pdo->prepare("
        SELECT et.*, u.id as user_id, u.email, u.name 
        FROM email_tokens et
        JOIN users u ON et.user_id = u.id
        WHERE et.token = ? 
        AND et.type = 'password_reset' 
        AND et.expires_at > NOW()
        AND et.used_at IS NULL
    ");
    $stmt->execute([$token]);
    $result = $stmt->fetch();
    
    if (!$result) {
        error_response('Ongeldige of verlopen token', 400);
    }
    
    // Start transactie
    $pdo->beginTransaction();
    
    // Markeer token als gebruikt
    $stmt = $pdo->prepare("UPDATE email_tokens SET used_at = NOW() WHERE id = ?");
    $stmt->execute([$result['id']]);
    
    // Hash het wachtwoord
    $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
    
    // Update wachtwoord
    $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$hashed_password, $result['user_id']]);
    
    // Verwijder alle refresh tokens (forceert uitloggen op alle apparaten)
    $stmt = $pdo->prepare("DELETE FROM refresh_tokens WHERE user_id = ?");
    $stmt->execute([$result['user_id']]);
    
    // Commit transactie
    $pdo->commit();
    
    // Bouw gebruikersobject
    $user = [
        'id' => $result['user_id'],
        'email' => $result['email'],
        'name' => $result['name']
    ];
    
    // Genereer nieuw JWT token
    $jwt_token = generate_jwt_token($user);
    
    // Maak nieuw refresh token
    $refresh_token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));
    
    // Sla refresh token op
    $stmt = $pdo->prepare("INSERT INTO refresh_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$user['id'], $refresh_token, $expires_at]);
    
    // Zet cookie
    setcookie('refresh_token', $refresh_token, [
        'expires' => strtotime('+7 days'),
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    
    // Stuur succesvolle response
    json_response([
        'success' => true,
        'message' => 'Wachtwoord succesvol gewijzigd',
        'token' => $jwt_token,
        'user' => $user
    ]);
    
} catch (PDOException $e) {
    // Rollback bij fout
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log de fout
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("Wachtwoord reset mislukt: " . $e->getMessage());
    }
    error_response('Er is een fout opgetreden bij het herstellen van je wachtwoord', 500);
} 