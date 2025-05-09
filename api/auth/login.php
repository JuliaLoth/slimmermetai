<?php
/**
 * Login API endpoint voor SlimmerMetAI.com
 * Verwerkt inlogverzoeken en geeft JWT tokens terug
 */

// Laad de benodigde bestanden
require_once dirname(dirname(__DIR__)) . '/includes/init.php';
require_once INCLUDES_ROOT . '/utils/ApiResponse.php';
require_once INCLUDES_ROOT . '/auth/Authentication.php';

use App\Infrastructure\Security\Validator;

// Initialiseer authenticatie
$auth = Authentication::getInstance();

// Alleen POST requests toestaan
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::methodNotAllowed('Methode niet toegestaan', ['POST']);
}

// Krijg JSON data uit request body
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Controleer of de data geldig JSON is
if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
    ApiResponse::error('Ongeldige JSON data', 400);
}

// Valideer verplichte velden
$validator = new Validator($data, [
    'email' => 'required|email',
    'password' => 'required|min:6'
]);

if (!$validator->validate()) {
    ApiResponse::validationError($validator->getErrors(), 'Validatiefout');
}

// Valideer reCAPTCHA indien ingeschakeld en meegestuurd
if (defined('RECAPTCHA_SECRET_KEY') && !empty(RECAPTCHA_SECRET_KEY) && isset($data['recaptchaToken'])) {
    $isValid = verifyRecaptcha($data['recaptchaToken']);
    
    if (!$isValid) {
        ApiResponse::error('reCAPTCHA verificatie mislukt', 400);
    }
}

// Sanitize input
$email = Validator::sanitizeEmail($data['email']);
$password = $data['password']; // Wachtwoord niet sanitizen
$remember = isset($data['remember']) ? (bool)$data['remember'] : false;

// Login poging uitvoeren
$result = $auth->login($email, $password, $remember);

// Controleer resultaat
if (isset($result['error'])) {
    $status = $result['status'] ?? 400;
    ApiResponse::error($result['error'], $status);
}

// Stel refresh token cookie in
if (isset($result['tokens']['refresh_token'])) {
    setcookie('refresh_token', $result['tokens']['refresh_token'], [
        'expires' => strtotime('+7 days'),
        'path' => '/',
        'domain' => COOKIE_DOMAIN,
        'secure' => COOKIE_SECURE,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

// Stuur succesresponse
ApiResponse::success([
    'user' => $result['user'],
    'access_token' => $result['tokens']['access_token'],
    'expires_at' => $result['tokens']['expires_at']
], 'Inloggen gelukt');

/**
 * Controleer reCAPTCHA token
 * 
 * @param string $token Het reCAPTCHA token
 * @return bool True als het token geldig is
 */
function verifyRecaptcha($token) {
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result === false) {
        return false;
    }
    
    $responseData = json_decode($result, true);
    
    return isset($responseData['success']) && $responseData['success'] === true;
}
