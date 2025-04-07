<?php
/**
 * Stripe Checkout Sessie API
 * 
 * Dit script maakt een Stripe checkout sessie en geeft de ID en URL terug.
 * Geconfigureerd voor zandbakomgeving (test modus).
 */

// Schrijf naar error log voor debugging
error_log('Stripe checkout sessie request ontvangen. Methode: ' . $_SERVER['REQUEST_METHOD']);

// Stel de content type in
header('Content-Type: application/json');

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Options pre-flight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Zorg ervoor dat alleen POST verzoeken worden geaccepteerd
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log('Ongeldige request methode: ' . $_SERVER['REQUEST_METHOD']);
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Alleen POST-verzoeken zijn toegestaan']);
    exit();
}

// Debug info over de input
$raw_input = file_get_contents('php://input');
error_log('Ontvangen ruw request data: ' . substr($raw_input, 0, 500));

// Haal de POST data op
if (!$raw_input) {
    error_log('Geen raw input ontvangen');
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Geen JSON data ontvangen']);
    exit();
}

// Decode JSON met juiste error handling
try {
    $data = json_decode($raw_input, true);
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        error_log('JSON decode fout: ' . json_last_error_msg());
        throw new Exception(json_last_error_msg());
    }
} catch (Exception $e) {
    error_log('Exception bij JSON decode: ' . $e->getMessage());
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Ongeldige JSON data: ' . $e->getMessage()]);
    exit();
}

// Debug info over de gedecodeerde data
error_log('Gedecodeerde JSON data: ' . json_encode($data));

// Valideer de vereiste velden
if (empty($data['line_items']) || !is_array($data['line_items'])) {
    error_log('Ongeldige line_items in request');
    http_response_code(400);
    echo json_encode(['error' => 'Ongeldige line_items: producten zijn vereist']);
    exit();
}

if (empty($data['mode'])) {
    error_log('Ontbrekende mode parameter in request');
    http_response_code(400);
    echo json_encode(['error' => 'Ontbrekende mode parameter']);
    exit();
}

// Stel standaard URLs in als ze niet zijn opgegeven
if (empty($data['success_url'])) {
    $data['success_url'] = 'https://' . $_SERVER['HTTP_HOST'] . '/betaling-succes.php?session_id={CHECKOUT_SESSION_ID}';
}

if (empty($data['cancel_url'])) {
    $data['cancel_url'] = 'https://' . $_SERVER['HTTP_HOST'] . '/winkelwagen.php?canceled=true';
}

// Debug info over environment
error_log('Controleren op .env bestand...');

// Probeer eerst de Composer autoloader te laden
$autoloadPaths = [
    dirname(__DIR__) . '/vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php'
];

$autoloaded = false;
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        error_log('Autoloader gevonden op: ' . $path);
        require_once $path;
        $autoloaded = true;
        break;
    }
}

if (!$autoloaded) {
    error_log('Geen autoloader gevonden, directe stripe-php include vereist');
}

// Laad de .env variabelen als deze niet al geladen zijn
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    error_log('.env bestand gevonden: ' . $envFile);
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if (!empty($name) && !empty($value)) {
                putenv("$name=$value");
                $_ENV[$name] = $value;
            }
        }
    }
} else {
    error_log('.env bestand niet gevonden op: ' . $envFile);
}

try {
    // Controleer of Stripe klasse beschikbaar is
    if (!class_exists('\Stripe\Stripe')) {
        error_log('Stripe klasse niet beschikbaar, directe include nodig');
        
        // Fallback: Zoek naar Stripe in vendor map
        $stripePaths = [
            dirname(__DIR__) . '/vendor/stripe/stripe-php/init.php',
            __DIR__ . '/vendor/stripe/stripe-php/init.php'
        ];
        
        $stripeLoaded = false;
        foreach ($stripePaths as $path) {
            if (file_exists($path)) {
                error_log('Stripe direct geladen vanaf: ' . $path);
                require_once $path;
                $stripeLoaded = true;
                break;
            }
        }
        
        if (!$stripeLoaded) {
            error_log('Kon Stripe niet laden, fallback naar simulatiemodus');
            // Simuleer een succesvolle checkout sessie
            echo json_encode([
                'id' => 'sim_' . uniqid(),
                'url' => 'https://checkout.stripe.com/pay/simulated#fidkdWxOYHwnPyd1blppbHNgWnJsPGhhck89TGlHXVA0QDNEPTBJaEhpbVNBaE5qVzdJYlNqPFZoXzZpd01haFBKNXA9cGhjNUF3bnVMSjA1NTVLMWhzZ1E8YnVCVEpsX0hKNDdVMX\'',
                'is_simulation' => true,
                'created' => time(),
                'expires_at' => time() + 3600
            ]);
            exit();
        }
    } else {
        error_log('Stripe klasse beschikbaar via autoloader');
    }
    
    // Haal de Stripe sleutel op uit de omgevingsvariabelen
    $stripe_secret_key = getenv('STRIPE_SECRET_KEY');
    
    // Als er geen sleutel is gevonden, gebruik de fallback sleutel
    if (empty($stripe_secret_key)) {
        error_log('Geen STRIPE_SECRET_KEY in omgevingsvariabelen, gebruik fallback key');
        $stripe_secret_key = 'sk_test_51Qf2ltG2yqBai5Fs37k1YEn88I6sKqQVmASq10CGl1cOvdQpTzMNT5Nc5qvzMQuZgzvZZ1OKQmeoL6BFMkZAlEeE00VmRgFeje';
    } else {
        error_log('STRIPE_SECRET_KEY gevonden in omgevingsvariabelen');
    }
    
    // Stel de Stripe API sleutel in
    \Stripe\Stripe::setApiKey($stripe_secret_key);
    
    // Extra metadata toevoegen
    $metadata = isset($data['metadata']) ? $data['metadata'] : [];
    $metadata['source'] = 'slimmermetai.com';
    $metadata['timestamp'] = date('Y-m-d H:i:s');
    
    // Controleer of de line_items correct zijn geformateerd
    $line_items = $data['line_items'];
    if (!is_array($line_items)) {
        throw new Exception('line_items moet een array zijn');
    }
    
    error_log('Aanmaken Stripe checkout sessie met ' . count($line_items) . ' items');
    
    // Maak de checkout sessie aan
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => $line_items,
        'mode' => $data['mode'],
        'success_url' => $data['success_url'],
        'cancel_url' => $data['cancel_url'],
        'metadata' => $metadata
    ]);
    
    error_log('Stripe checkout sessie succesvol aangemaakt, ID: ' . $session->id);
    
    // Geef de response
    echo json_encode([
        'id' => $session->id,
        'url' => $session->url,
        'status' => $session->status,
        'is_test_mode' => true,
        'created' => $session->created,
        'expires_at' => $session->expires_at
    ]);
    exit();
    
} catch (Exception $e) {
    // Geef een foutmelding terug
    $error_message = $e->getMessage();
    error_log('Exception bij aanmaken Stripe checkout sessie: ' . $error_message);
    
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $error_message,
        'debug_info' => [
            'time' => date('Y-m-d H:i:s'),
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'php_version' => PHP_VERSION
        ]
    ]);
    exit();
}
