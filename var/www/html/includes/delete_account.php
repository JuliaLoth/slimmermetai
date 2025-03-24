<?php
/**
 * Script om gebruikersaccounts te verwijderen
 */

// Start de sessie als die nog niet is gestart
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Laad configuratiebestand
require_once __DIR__ . '/config.php';

// Laad benodigde klassen
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Security.php';
require_once __DIR__ . '/GDPR.php';

// Initialiseer klassen
$db = Database::getInstance();
$auth = Auth::getInstance();
$security = Security::getInstance();
$gdpr = GDPR::getInstance();

// Controleer of de gebruiker is ingelogd
if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

// Controleer sessie integriteit
$security->checkSessionIntegrity();

// Controleer CSRF token
$security->validateCSRFToken();

// Haal de huidige gebruiker op
$user = $auth->getCurrentUser();

try {
    // Start een transactie
    $db->beginTransaction();
    
    // Gebruik de GDPR klasse om gebruikersgegevens te verwijderen
    $deleted = $gdpr->deleteUserData($user['id']);
    
    if ($deleted) {
        // Commit de transactie
        $db->commit();
        
        // Log de gebruiker uit
        $auth->logout();
        
        // Stuur door naar de homepage met een succesbericht
        $_SESSION['success_message'] = 'Je account is succesvol verwijderd. We vinden het jammer dat je vertrekt!';
        header('Location: ../index.php');
        exit;
    } else {
        // Rollback de transactie
        $db->rollback();
        
        // Stuur door naar de profielpagina met een foutmelding
        $_SESSION['error_message'] = 'Er is een fout opgetreden bij het verwijderen van je account. Probeer het later opnieuw.';
        header('Location: ../profiel.php');
        exit;
    }
} catch (Exception $e) {
    // Rollback bij een fout
    $db->rollback();
    
    // Log de fout
    error_log("Fout bij het verwijderen van account ID {$user['id']}: " . $e->getMessage());
    
    // Stuur door naar de profielpagina met een foutmelding
    $_SESSION['error_message'] = 'Er is een fout opgetreden bij het verwijderen van je account. Probeer het later opnieuw.';
    header('Location: ../profiel.php');
    exit;
} 