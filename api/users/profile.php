<?php
/**
 * Profiel API endpoint voor SlimmerMetAI.com
 * Haalt profiel op of werkt het bij
 */

// Definieer root path als dat nog niet is gedaan
if (!defined('SITE_ROOT')) {
    define('SITE_ROOT', dirname(dirname(dirname(dirname(__FILE__))))); // Ga drie niveaus omhoog vanuit api/users/profile.php
}

// Include de API configuratie
require_once dirname(dirname(__FILE__)) . '/config.php';

// Start sessie voor CSRF
session_start();

// Authenticatie vereist
$user = auth_check();

// GET: Profielgegevens ophalen
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Haal profiel op
        $stmt = $pdo->prepare("
            SELECT id, name, email, role, email_verified, profile_picture, created_at, updated_at, last_login
            FROM users
            WHERE id = ?
        ");
        $stmt->execute([$user['id']]);
        $profile = $stmt->fetch();
        
        if (!$profile) {
            error_response('Profiel niet gevonden', 404);
        }
        
        // Converteer email_verified naar boolean
        $profile['email_verified'] = (bool)$profile['email_verified'];
        
        // Voeg volledige URL toe aan profielfoto indien nodig
        if ($profile['profile_picture'] && !preg_match('/^https?:\/\//', $profile['profile_picture'])) {
            $profile['profile_picture'] = SITE_URL . '/' . ltrim($profile['profile_picture'], '/');
        }
        
        // Stuur profiel terug
        json_response([
            'success' => true,
            'profile' => $profile
        ]);
    } catch (PDOException $e) {
        // Log de fout
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("Profiel ophalen mislukt: " . $e->getMessage());
        }
        error_response('Er is een fout opgetreden bij het ophalen van je profiel', 500);
    }
}

// PUT: Profielgegevens bijwerken
if ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Haal JSON data uit request body
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);
    
    if (!$data) {
        error_response('Geen geldige JSON data ontvangen');
    }
    
    // Valideer en sanitize input
    $name = isset($data['name']) ? sanitize_input($data['name']) : null;
    
    // Controleer of tenminste één veld is ingevuld
    if ($name === null) {
        error_response('Je moet tenminste één veld opgeven om bij te werken');
    }
    
    try {
        // Update alleen de opgegeven velden
        $updates = [];
        $params = [];
        
        if ($name !== null) {
            $updates[] = "name = ?";
            $params[] = $name;
        }
        
        // Geen updates nodig?
        if (empty($updates)) {
            json_response([
                'success' => true,
                'message' => 'Niets om bij te werken'
            ]);
            exit;
        }
        
        // Voeg user ID toe
        $params[] = $user['id'];
        
        // Update profiel
        $stmt = $pdo->prepare("
            UPDATE users
            SET " . implode(", ", $updates) . ", updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute($params);
        
        // Haal bijgewerkt profiel op
        $stmt = $pdo->prepare("
            SELECT id, name, email, role, email_verified, profile_picture, created_at, updated_at, last_login
            FROM users
            WHERE id = ?
        ");
        $stmt->execute([$user['id']]);
        $updated_profile = $stmt->fetch();
        
        // Converteer email_verified naar boolean
        $updated_profile['email_verified'] = (bool)$updated_profile['email_verified'];
        
        // Voeg volledige URL toe aan profielfoto indien nodig
        if ($updated_profile['profile_picture'] && !preg_match('/^https?:\/\//', $updated_profile['profile_picture'])) {
            $updated_profile['profile_picture'] = SITE_URL . '/' . ltrim($updated_profile['profile_picture'], '/');
        }
        
        // Stuur bijgewerkt profiel terug
        json_response([
            'success' => true,
            'message' => 'Profiel bijgewerkt',
            'profile' => $updated_profile
        ]);
    } catch (PDOException $e) {
        // Log de fout
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("Profiel bijwerken mislukt: " . $e->getMessage());
        }
        error_response('Er is een fout opgetreden bij het bijwerken van je profiel', 500);
    }
}

// Andere methodes zijn niet toegestaan
error_response('Methode niet toegestaan', 405); 