<?php
/**
 * Forgot Password API endpoint voor SlimmerMetAI.com
 * Verwerkt verzoeken voor wachtwoordherstel
 */

// Definieer root path als dat nog niet is gedaan
if (!defined('SITE_ROOT')) {
    define('SITE_ROOT', dirname(dirname(dirname(dirname(__FILE__))))); // Ga drie niveaus omhoog vanuit api/auth/forgot-password.php
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
if (!isset($data['email'])) {
    error_response('E-mailadres is verplicht');
}

$email = filter_var(sanitize_input($data['email']), FILTER_VALIDATE_EMAIL);

// Valideer email
if (!$email) {
    error_response('Ongeldig e-mailadres');
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
    // Controleer of gebruiker bestaat
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    // Voor veiligheid sturen we altijd een succesvolle response, ook als de gebruiker niet bestaat
    if (!$user) {
        json_response([
            'success' => true,
            'message' => 'Als er een account bestaat met dit e-mailadres, dan is er een herstelinstructie verzonden.'
        ]);
        exit;
    }
    
    // Verwijder eventuele bestaande tokens
    $stmt = $pdo->prepare("DELETE FROM email_tokens WHERE user_id = ? AND type = 'password_reset'");
    $stmt->execute([$user['id']]);
    
    // Genereer een nieuw reset token
    $reset_token = bin2hex(random_bytes(32));
    $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Sla token op
    $stmt = $pdo->prepare("INSERT INTO email_tokens (user_id, token, type, expires_at) VALUES (?, ?, 'password_reset', ?)");
    $stmt->execute([$user['id'], $reset_token, $token_expiry]);
    
    // Bouw URL
    $reset_url = SITE_URL . '/reset-password?token=' . $reset_token;
    
    // Stel e-mail op
    $mail_subject = 'Wachtwoord herstellen voor SlimmerMetAI';
    $mail_body = "Hallo " . $user['name'] . ",\n\n";
    $mail_body .= "We hebben een verzoek ontvangen om je wachtwoord te herstellen.\n\n";
    $mail_body .= "Klik op de volgende link om je wachtwoord opnieuw in te stellen:\n";
    $mail_body .= "$reset_url\n\n";
    $mail_body .= "Deze link is 1 uur geldig.\n\n";
    $mail_body .= "Als je geen wachtwoordherstel hebt aangevraagd, kun je deze e-mail negeren.\n\n";
    $mail_body .= "Met vriendelijke groet,\n";
    $mail_body .= "Het SlimmerMetAI team";
    
    // Stuur e-mail indien MAIL_FROM is gedefinieerd
    if (defined('MAIL_FROM') && defined('MAIL_FROM_NAME')) {
        $headers = "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";
        $headers .= "Reply-To: " . (defined('MAIL_REPLY_TO') ? MAIL_REPLY_TO : MAIL_FROM) . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        mail($email, $mail_subject, $mail_body, $headers);
    }
    
    // Log de actie
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("Wachtwoordherstel aangevraagd voor gebruiker " . $user['id'] . " (" . $user['email'] . ")");
    }
    
    // Stuur succesvolle response
    json_response([
        'success' => true,
        'message' => 'Als er een account bestaat met dit e-mailadres, dan is er een herstelinstructie verzonden.'
    ]);
    
} catch (PDOException $e) {
    // Log de fout
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("Wachtwoordherstel mislukt: " . $e->getMessage());
    }
    error_response('Er is een fout opgetreden bij het aanvragen van wachtwoordherstel', 500);
} 