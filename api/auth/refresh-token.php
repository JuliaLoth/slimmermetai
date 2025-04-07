<?php
/**
 * Refresh Token API endpoint voor SlimmerMetAI.com
 * Vernieuwt de JWT token met behulp van de refresh token cookie
 */

// Definieer root path als dat nog niet is gedaan
if (!defined('SITE_ROOT')) {
    define('SITE_ROOT', dirname(dirname(dirname(dirname(__FILE__))))); // Ga drie niveaus omhoog vanuit api/auth/refresh-token.php
}

// Include de API configuratie
require_once dirname(dirname(__FILE__)) . '/config.php';

// Start sessie voor CSRF
session_start();

// Alleen POST requests toestaan
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_response('Methode niet toegestaan', 405);
}

// Haal refresh token uit cookie
$refresh_token = isset($_COOKIE['refresh_token']) ? $_COOKIE['refresh_token'] : null;

if (!$refresh_token) {
    error_response('Geen refresh token gevonden', 401);
}

try {
    // Zoek refresh token in database
    $stmt = $pdo->prepare("
        SELECT r.*, u.* 
        FROM refresh_tokens r
        JOIN users u ON r.user_id = u.id
        WHERE r.token = ? AND r.expires_at > NOW()
    ");
    $stmt->execute([$refresh_token]);
    $result = $stmt->fetch();
    
    if (!$result) {
        // Token niet gevonden of verlopen
        error_response('Ongeldige of verlopen refresh token', 401);
    }
    
    // Maak user object
    $user = [
        'id' => $result['user_id'],
        'name' => $result['name'],
        'email' => $result['email'],
        'role' => $result['role']
    ];
    
    // Verwijder oud refresh token
    $stmt = $pdo->prepare("DELETE FROM refresh_tokens WHERE token = ?");
    $stmt->execute([$refresh_token]);
    
    // Maak nieuw refresh token
    $new_refresh_token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));
    
    // Sla nieuw refresh token op
    $stmt = $pdo->prepare("INSERT INTO refresh_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$user['id'], $new_refresh_token, $expires_at]);
    
    // Zet nieuwe cookie
    setcookie('refresh_token', $new_refresh_token, [
        'expires' => strtotime('+7 days'),
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    
    // Genereer nieuwe JWT token
    $token = generate_jwt_token($user);
    
    // Stuur token en gebruiker terug
    json_response([
        'success' => true,
        'token' => $token,
        'user' => $user
    ]);
    
} catch (PDOException $e) {
    // Log de fout maar toon geen database details
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("Refresh token error: " . $e->getMessage());
    }
    error_response('Er is een fout opgetreden bij het vernieuwen van je token', 500);
} 