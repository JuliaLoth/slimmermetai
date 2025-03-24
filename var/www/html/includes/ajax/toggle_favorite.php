<?php
/**
 * AJAX handler voor het toevoegen/verwijderen van favorieten
 */

// Start de sessie als die nog niet is gestart
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Laad configuratiebestand
require_once __DIR__ . '/../config.php';

// Laad benodigde klassen
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Security.php';

// Initialiseer klassen
$db = Database::getInstance();
$auth = Auth::getInstance();
$security = Security::getInstance();

// Controleer of de gebruiker is ingelogd
if (!$auth->isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'U moet ingelogd zijn om deze actie uit te voeren.'
    ]);
    exit;
}

// Haal de huidige gebruiker op
$user = $auth->getCurrentUser();

// Controleer CSRF token
$security->validateCSRFToken();

// Valideer en saniteer input
$id = isset($_POST['id']) ? $security->sanitizeInput($_POST['id']) : null;
$type = isset($_POST['type']) ? $security->sanitizeInput($_POST['type']) : null;

// Controleer of alle benodigde parameters zijn opgegeven
if (!$id || !$type) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Ontbrekende parameters.'
    ]);
    exit;
}

// Controleer of het item type geldig is
$validTypes = ['elearning', 'tool', 'prompt'];
if (!in_array($type, $validTypes)) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Ongeldig item type.'
    ]);
    exit;
}

// Controleer of het item bestaat
$itemTable = '';
switch ($type) {
    case 'elearning':
        $itemTable = 'elearnings';
        break;
    case 'tool':
        $itemTable = 'tools';
        break;
    case 'prompt':
        $itemTable = 'prompts';
        break;
}

$itemExists = $db->getRow("SELECT id FROM {$itemTable} WHERE id = ?", [$id]);
if (!$itemExists) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Het item bestaat niet.'
    ]);
    exit;
}

// Controleer of het item al een favoriet is
$favorite = $db->getRow(
    "SELECT id FROM favorites WHERE user_id = ? AND item_id = ? AND item_type = ?",
    [$user['id'], $id, $type]
);

// Toggle favoriet status
if ($favorite) {
    // Verwijder favoriet
    $result = $db->query(
        "DELETE FROM favorites WHERE user_id = ? AND item_id = ? AND item_type = ?",
        [$user['id'], $id, $type]
    );
    $isFavorite = false;
} else {
    // Voeg toe als favoriet
    $result = $db->query(
        "INSERT INTO favorites (user_id, item_id, item_type, created_at) VALUES (?, ?, ?, NOW())",
        [$user['id'], $id, $type]
    );
    $isFavorite = true;
}

// Controleer of de query is gelukt
if ($result) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'is_favorite' => $isFavorite,
        'message' => $isFavorite ? 'Item toegevoegd aan favorieten.' : 'Item verwijderd uit favorieten.'
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Er is een fout opgetreden bij het bijwerken van de favorieten.'
    ]);
} 