<?php
/**
 * Verify Email API endpoint voor SlimmerMetAI.com
 * Verwerkt e-mailadresverificatie met behulp van een token
 */

// Definieer root path als dat nog niet is gedaan
if (!defined('SITE_ROOT')) {
    define('SITE_ROOT', dirname(dirname(dirname(dirname(__FILE__))))); // Ga drie niveaus omhoog vanuit api/auth/verify-email.php
}

// Include de API configuratie
require_once dirname(dirname(__FILE__)) . '/config.php';

// Start sessie voor CSRF
session_start();

// Alleen GET of POST requests toestaan
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_response('Methode niet toegestaan', 405);
}

// Haal token uit query parameter of request body
$token = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = isset($_GET['token']) ? sanitize_input($_GET['token']) : '';
} else {
    // POST: Haal JSON data uit request body
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);
    $token = isset($data['token']) ? sanitize_input($data['token']) : '';
}

if (empty($token)) {
    error_response('Token is verplicht', 400);
}

try {
    // Zoek token in database
    $stmt = $pdo->prepare("
        SELECT et.*, u.id as user_id, u.email, u.name 
        FROM email_tokens et
        JOIN users u ON et.user_id = u.id
        WHERE et.token = ? 
        AND et.type = 'verification' 
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
    
    // Markeer e-mail als geverifieerd
    $stmt = $pdo->prepare("UPDATE users SET email_verified = 1, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$result['user_id']]);
    
    // Commit transactie
    $pdo->commit();
    
    // Bouw gebruikersobject
    $user = [
        'id' => $result['user_id'],
        'email' => $result['email'],
        'name' => $result['name'],
        'email_verified' => true
    ];
    
    // Genereer nieuw JWT token als de gebruiker nog niet was ingelogd
    $jwt_token = generate_jwt_token($user);
    
    // Stuur succesvolle response
    json_response([
        'success' => true,
        'message' => 'E-mailadres succesvol geverifieerd',
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
        error_log("E-mailadres verificatie mislukt: " . $e->getMessage());
    }
    error_response('Er is een fout opgetreden bij het verifiëren van je e-mailadres', 500);
} 