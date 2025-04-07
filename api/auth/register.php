<?php
/**
 * Registratie API endpoint voor SlimmerMetAI.com
 * Verwerkt registratieverzoeken en maakt nieuwe gebruikers aan
 */

// Definieer root path als dat nog niet is gedaan
if (!defined('SITE_ROOT')) {
    define('SITE_ROOT', dirname(dirname(dirname(dirname(__FILE__))))); // Ga drie niveaus omhoog vanuit api/auth/register.php
}

// Include de API configuratie
require_once dirname(dirname(__FILE__)) . '/config.php';

// Start sessie voor CSRF
session_start();

// Alleen POST requests toestaan
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_response('Methode niet toegestaan', 405);
}

// Krijg JSON data uit request body
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Controleer verplichte velden
if (!isset($data['firstName']) || !isset($data['lastName']) || !isset($data['email']) || !isset($data['password'])) {
    error_response('Voornaam, achternaam, e-mailadres en wachtwoord zijn verplicht');
}

// Controleer CSRF-token
if (!isset($data['csrf_token'])) {
    error_response('CSRF-token ontbreekt', 403);
}

$csrf = CsrfProtection::getInstance();
if (!$csrf->validateToken($data['csrf_token'])) {
    error_response('Ongeldige CSRF-token. Vernieuw de pagina en probeer opnieuw.', 403);
}

// Valideer en sanitize input
$firstName = sanitize_input($data['firstName']);
$lastName = sanitize_input($data['lastName']);
$fullName = trim($firstName . ' ' . $lastName); // Combineer namen
$email = filter_var(sanitize_input($data['email']), FILTER_VALIDATE_EMAIL);
$password = $data['password'];
$termsAgreement = isset($data['termsAgreement']) ? (bool)$data['termsAgreement'] : false; // Check voor termsAgreement

// Controleer of algemene voorwaarden zijn geaccepteerd
if (!$termsAgreement) {
    error_response('Je moet akkoord gaan met de algemene voorwaarden en het privacybeleid');
}

// Valideer email
if (!$email) {
    error_response('Ongeldig e-mailadres');
}

// Valideer wachtwoord
if (strlen($password) < PASSWORD_MIN_LENGTH) {
    error_response('Wachtwoord moet minimaal ' . PASSWORD_MIN_LENGTH . ' tekens lang zijn');
}

// Controleer of wachtwoord voldoet aan complexiteitseisen
if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || 
    !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
    error_response('Wachtwoord moet minimaal één hoofdletter, één kleine letter, één cijfer en één speciaal teken bevatten');
}

// Controleer reCAPTCHA indien meegestuurd
if (isset($data['recaptchaToken']) && defined('RECAPTCHA_SECRET_KEY')) {
    $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
    $recaptcha_data = [
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $data['recaptchaToken']
    ];
    
    $recaptcha_options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($recaptcha_data)
        ]
    ];
    
    $recaptcha_context = stream_context_create($recaptcha_options);
    $recaptcha_result = file_get_contents($recaptcha_url, false, $recaptcha_context);
    $recaptcha_response = json_decode($recaptcha_result, true);
    
    if (!$recaptcha_response['success']) {
        error_response('reCAPTCHA verificatie mislukt', 400);
    }
}

try {
    // Controleer of e-mail al bestaat
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        error_response('Dit e-mailadres is al in gebruik');
    }
    
    // Hash het wachtwoord
    $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
    
    // Genereer verificatie token
    $verification_token = bin2hex(random_bytes(32));
    $token_expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Start transactie
    $pdo->beginTransaction();
    
    // Gebruiker invoegen
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, created_at, role) VALUES (?, ?, ?, NOW(), 'user')");
    $stmt->execute([$fullName, $email, $hashed_password]);
    
    $user_id = $pdo->lastInsertId();
    
    // Verificatie token opslaan
    $stmt = $pdo->prepare("INSERT INTO email_tokens (user_id, token, type, expires_at) VALUES (?, ?, 'verification', ?)");
    $stmt->execute([$user_id, $verification_token, $token_expiry]);
    
    // Commit transactie
    $pdo->commit();
    
    // Stuur verificatie e-mail (in productie)
    // Dit is een voorbeeld en moet in productie worden geïmplementeerd
    $verification_url = SITE_URL . '/verify-email?token=' . $verification_token;
    
    $mail_subject = 'Bevestig je e-mailadres bij SlimmerMetAI';
    $mail_body = "Hallo $firstName,\n\n";
    $mail_body .= "Bedankt voor je registratie bij SlimmerMetAI!\n\n";
    $mail_body .= "Klik op de volgende link om je e-mailadres te bevestigen:\n";
    $mail_body .= "$verification_url\n\n";
    $mail_body .= "Deze link is 24 uur geldig.\n\n";
    $mail_body .= "Met vriendelijke groet,\n";
    $mail_body .= "Het SlimmerMetAI team";
    
    // Stuur e-mail indien MAIL_FROM is gedefinieerd
    if (defined('MAIL_FROM') && defined('MAIL_FROM_NAME')) {
        $headers = "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";
        $headers .= "Reply-To: " . (defined('MAIL_REPLY_TO') ? MAIL_REPLY_TO : MAIL_FROM) . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        mail($email, $mail_subject, $mail_body, $headers);
    }
    
    // Haal gebruiker op om terug te sturen (zonder wachtwoord)
    $stmt = $pdo->prepare("SELECT id, name, email, role, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    // Genereer JWT token
    $token = generate_jwt_token($user);
    
    // Response
    json_response([
        'success' => true,
        'message' => 'Registratie succesvol. Controleer je e-mail om je account te bevestigen.',
        'token' => $token,
        'user' => $user
    ]);
    
} catch (PDOException $e) {
    // Rollback bij fout
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log de fout maar toon geen database details
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("Registratie error: " . $e->getMessage());
    }
    error_response('Er is een fout opgetreden bij de registratie. Probeer het later opnieuw.', 500);
} 