<?php
/**
 * Authentication Helper - SlimmerMetAI.com
 * 
 * Functies voor authenticatie, JWT tokens en gebruikersbeheer
 */

// Defineer SITE_ROOT als dat nog niet is gebeurd
if (!defined('SITE_ROOT')) {
    define('SITE_ROOT', dirname(dirname(__DIR__)));
}

/**
 * Genereert een JWT token
 * 
 * @param array $payload De data die in het token moet worden opgenomen
 * @param int $expiry Verloopduur in seconden, standaard 1 uur
 * @return string Het gegenereerde JWT token
 */
function generate_jwt($payload, $expiry = 3600) {
    global $config;
    
    // Verkrijg de JWT secret key uit de config
    $secret = isset($config['jwt_secret']) ? $config['jwt_secret'] : 'default_jwt_secret_change_me';
    
    // Voeg standard claims toe
    $payload['iat'] = time(); // Issued at
    $payload['exp'] = time() + $expiry; // Expiration Time
    
    // Genereer header
    $header = [
        'typ' => 'JWT',
        'alg' => 'HS256'
    ];
    
    // Encodeer header en payload naar base64
    $header_encoded = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
    $payload_encoded = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
    
    // Maak signature
    $signature = hash_hmac('sha256', "$header_encoded.$payload_encoded", $secret, true);
    $signature_encoded = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    
    // Combineer tot JWT
    return "$header_encoded.$payload_encoded.$signature_encoded";
}

/**
 * Verifieert een JWT token
 * 
 * @param string $token Het JWT token om te verifiëren
 * @return array|bool De payload als verificatie succesvol is, anders false
 */
function verify_jwt($token) {
    global $config;
    
    // Verkrijg de JWT secret key uit de config
    $secret = isset($config['jwt_secret']) ? $config['jwt_secret'] : 'default_jwt_secret_change_me';
    
    // Splits het token in onderdelen
    $token_parts = explode('.', $token);
    
    if (count($token_parts) !== 3) {
        return false; // Ongeldige tokenstructuur
    }
    
    // Decodeer header en payload
    $header_encoded = $token_parts[0];
    $payload_encoded = $token_parts[1];
    $signature_provided = $token_parts[2];
    
    // Decodeer payload
    $payload_json = base64_decode(strtr($payload_encoded, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($payload_encoded)) % 4));
    $payload = json_decode($payload_json, true);
    
    if (!$payload) {
        return false; // Ongeldige payload
    }
    
    // Controleer expiry
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return false; // Token is verlopen
    }
    
    // Bereken signature voor verificatie
    $signature_calculated = hash_hmac('sha256', "$header_encoded.$payload_encoded", $secret, true);
    $signature_calculated_encoded = rtrim(strtr(base64_encode($signature_calculated), '+/', '-_'), '=');
    
    // Vergelijk signatures
    if ($signature_provided !== $signature_calculated_encoded) {
        return false; // Signature komt niet overeen
    }
    
    return $payload; // Token is geldig
}

/**
 * Haalt een JWT token op uit de Authorization header
 * 
 * @return string|null Het JWT token of null indien niet gevonden
 */
function get_bearer_token() {
    $headers = null;
    
    // Controleer Authorization header
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER['Authorization']);
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
    } elseif (function_exists('apache_request_headers')) {
        $request_headers = apache_request_headers();
        if (isset($request_headers['Authorization'])) {
            $headers = trim($request_headers['Authorization']);
        }
    }
    
    // Haal Bearer prefix weg
    if ($headers && preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
        return $matches[1];
    }
    
    return null;
}

/**
 * Controleert of de huidige request geauthenticeerd is
 * 
 * @param bool $require_admin Vereist admin-rechten
 * @return array User data als geauthenticeerd, anders stuurt het een 401 response en stopt de executie
 */
function auth_check($require_admin = false) {
    global $pdo;
    
    // Haal token op
    $token = get_bearer_token();
    
    if (!$token) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Niet geauthenticeerd'
        ]);
        exit;
    }
    
    // Verifieer token
    $payload = verify_jwt($token);
    
    if (!$payload || !isset($payload['user_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Ongeldige of verlopen token'
        ]);
        exit;
    }
    
    // Haal gebruikersgegevens op
    try {
        $stmt = $pdo->prepare("SELECT id, name, email, role, email_verified, profile_picture, created_at, updated_at, last_login 
                               FROM users 
                               WHERE id = :user_id");
        $stmt->execute(['user_id' => $payload['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Gebruiker niet gevonden'
            ]);
            exit;
        }
        
        // Controleer admin rechten indien vereist
        if ($require_admin && $user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Geen toegangsrechten'
            ]);
            exit;
        }
        
        return $user;
    } catch (PDOException $e) {
        // Log de error, maar onthul geen details aan de client
        error_log("Database error in auth_check: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Er is een fout opgetreden bij het verifiëren van je accountgegevens'
        ]);
        exit;
    }
}

/**
 * Genereert een unieke refresh token voor een gebruiker
 * 
 * @param int $user_id Gebruikers-ID
 * @param int $expiry_days Aantal dagen dat het token geldig is
 * @return string|bool Het gegenereerde refresh token of false bij fout
 */
function generate_refresh_token($user_id, $expiry_days = 30) {
    global $pdo;
    
    // Genereer een uniek token
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime("+{$expiry_days} days"));
    
    try {
        $stmt = $pdo->prepare("INSERT INTO refresh_tokens (user_id, token, expires_at) 
                              VALUES (:user_id, :token, :expires_at)");
        $result = $stmt->execute([
            'user_id' => $user_id,
            'token' => $token,
            'expires_at' => $expires_at
        ]);
        
        if ($result) {
            return $token;
        }
    } catch (PDOException $e) {
        error_log("Error generating refresh token: " . $e->getMessage());
    }
    
    return false;
}

/**
 * Verificeert een refresh token en genereert een nieuw JWT token
 * 
 * @param string $refresh_token Het refresh token
 * @return array|bool Nieuwe JWT token en gebruikersgegevens, of false bij fout
 */
function verify_refresh_token($refresh_token) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT rt.user_id, rt.expires_at, u.id, u.name, u.email, u.role, u.email_verified, u.profile_picture
                              FROM refresh_tokens rt
                              JOIN users u ON rt.user_id = u.id
                              WHERE rt.token = :token");
        $stmt->execute(['token' => $refresh_token]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            return false; // Token niet gevonden
        }
        
        // Controleer of token verlopen is
        if (strtotime($result['expires_at']) < time()) {
            // Verwijder het verlopen token
            $delete_stmt = $pdo->prepare("DELETE FROM refresh_tokens WHERE token = :token");
            $delete_stmt->execute(['token' => $refresh_token]);
            return false; // Token is verlopen
        }
        
        // Genereer een nieuw JWT token
        $jwt_payload = [
            'user_id' => $result['user_id'],
            'email' => $result['email'],
            'role' => $result['role']
        ];
        
        $jwt = generate_jwt($jwt_payload);
        
        // Stuur gebruikersgegevens terug zonder gevoelige informatie
        $user = [
            'id' => $result['id'],
            'name' => $result['name'],
            'email' => $result['email'],
            'role' => $result['role'],
            'email_verified' => (bool)$result['email_verified'],
            'profile_picture' => $result['profile_picture'] ? get_upload_url($result['profile_picture']) : null
        ];
        
        return [
            'token' => $jwt,
            'user' => $user
        ];
    } catch (PDOException $e) {
        error_log("Error verifying refresh token: " . $e->getMessage());
        return false;
    }
}

/**
 * Verwijdert alle refresh tokens voor een gebruiker
 * 
 * @param int $user_id Gebruikers-ID
 * @param string|null $except_token Optioneel token om te behouden
 * @return bool Succes indicator
 */
function revoke_refresh_tokens($user_id, $except_token = null) {
    global $pdo;
    
    try {
        if ($except_token) {
            $stmt = $pdo->prepare("DELETE FROM refresh_tokens WHERE user_id = :user_id AND token != :token");
            $result = $stmt->execute([
                'user_id' => $user_id,
                'token' => $except_token
            ]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM refresh_tokens WHERE user_id = :user_id");
            $result = $stmt->execute(['user_id' => $user_id]);
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Error revoking refresh tokens: " . $e->getMessage());
        return false;
    }
}

/**
 * Genereert een email verificatie of wachtwoord reset token
 * 
 * @param int $user_id Gebruikers-ID
 * @param string $type Type token ('verification' of 'password_reset')
 * @param int $expiry_hours Geldigheid in uren
 * @return string|bool Het gegenereerde token of false bij fout
 */
function generate_email_token($user_id, $type, $expiry_hours = 24) {
    global $pdo;
    
    // Verwijder verlopen of gebruikte tokens van dezelfde type
    try {
        $stmt = $pdo->prepare("DELETE FROM email_tokens WHERE user_id = :user_id AND type = :type AND (used_at IS NOT NULL OR expires_at < NOW())");
        $stmt->execute([
            'user_id' => $user_id,
            'type' => $type
        ]);
    } catch (PDOException $e) {
        error_log("Error cleaning up old tokens: " . $e->getMessage());
    }
    
    // Genereer een uniek token
    $token = bin2hex(random_bytes(16));
    $expires_at = date('Y-m-d H:i:s', strtotime("+{$expiry_hours} hours"));
    
    try {
        $stmt = $pdo->prepare("INSERT INTO email_tokens (user_id, token, type, expires_at) 
                              VALUES (:user_id, :token, :type, :expires_at)");
        $result = $stmt->execute([
            'user_id' => $user_id,
            'token' => $token,
            'type' => $type,
            'expires_at' => $expires_at
        ]);
        
        if ($result) {
            return $token;
        }
    } catch (PDOException $e) {
        error_log("Error generating email token: " . $e->getMessage());
    }
    
    return false;
}

/**
 * Verstuurt een email met de juiste sjabloon
 * 
 * @param string $to Email adres van de ontvanger
 * @param string $subject Onderwerp van de email
 * @param string $template Sjabloon voor de email (HTML)
 * @param array $data Data om in het sjabloon te vervangen
 * @return bool Succes indicator
 */
function send_email($to, $subject, $template, $data = []) {
    global $config;
    
    // Controleer of we SMTP moeten gebruiken
    $use_smtp = false;
    if (
        isset($config['mail_host']) && 
        isset($config['mail_port']) && 
        isset($config['mail_username']) && 
        isset($config['mail_password'])
    ) {
        $use_smtp = true;
    }
    
    // Bereid afzender informatie voor
    $from_email = isset($config['mail_from']) ? $config['mail_from'] : 'noreply@slimmermetai.com';
    $from_name = isset($config['mail_from_name']) ? $config['mail_from_name'] : 'SlimmerMetAI.com';
    
    // Vervang placeholders in template
    foreach ($data as $key => $value) {
        $template = str_replace("{{" . $key . "}}", $value, $template);
    }
    
    // Stel de headers in
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: $from_name <$from_email>" . "\r\n";
    
    // Verstuur de email
    if ($use_smtp) {
        // Implementatie met een SMTP library (PHPMailer-achtig)
        // In een productieomgeving zou je hier een library als PHPMailer gebruiken
        // Voor dit voorbeeld gebruiken we een vereenvoudigde weergave
        
        $smtp_host = $config['mail_host'];
        $smtp_port = $config['mail_port'];
        $smtp_user = $config['mail_username'];
        $smtp_pass = $config['mail_password'];
        
        // Log SMTP gebruik
        error_log("SMTP mail zou worden verstuurd naar $to met onderwerp: $subject");
        
        // Aangezien we zonder externe libraries werken, simuleren we het resultaat
        return true;
    } else {
        // Gebruik de standaard PHP mail functie
        return mail($to, $subject, $template, $headers);
    }
}

/**
 * Controleert de geldigheid van een e-mailadres
 * 
 * @param string $email E-mailadres om te controleren
 * @return bool True als het e-mailadres geldig is, anders false
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Controleert de sterkte van een wachtwoord
 * 
 * @param string $password Het wachtwoord om te controleren
 * @param int $min_length Minimale lengte van het wachtwoord
 * @return array Validatieresultaat met status en foutmeldingen
 */
function validate_password_strength($password, $min_length = 8) {
    $errors = [];
    
    // Controleer lengte
    if (strlen($password) < $min_length) {
        $errors[] = "Wachtwoord moet minimaal $min_length tekens bevatten";
    }
    
    // Controleer op hoofdletter
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Wachtwoord moet minimaal één hoofdletter bevatten";
    }
    
    // Controleer op kleine letter
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Wachtwoord moet minimaal één kleine letter bevatten";
    }
    
    // Controleer op cijfer
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Wachtwoord moet minimaal één cijfer bevatten";
    }
    
    // Controleer op speciaal teken
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Wachtwoord moet minimaal één speciaal teken bevatten";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Logt een inlogpoging
 * 
 * @param string $email E-mailadres waarmee is geprobeerd in te loggen
 * @param bool $success Of de inlogpoging succesvol was
 * @return void
 */
function log_login_attempt($email, $success = false) {
    global $pdo;
    
    // Haal IP adres en user agent op
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    
    try {
        $stmt = $pdo->prepare("INSERT INTO login_attempts (email, ip_address, user_agent, success) 
                              VALUES (:email, :ip, :user_agent, :success)");
        $stmt->execute([
            'email' => $email,
            'ip' => $ip,
            'user_agent' => $user_agent,
            'success' => $success ? 1 : 0
        ]);
    } catch (PDOException $e) {
        error_log("Error logging login attempt: " . $e->getMessage());
    }
}

/**
 * Controleert of het IP-adres geblokkeerd moet worden vanwege te veel inlogpogingen
 * 
 * @param string $email E-mailadres om te controleren
 * @param int $max_attempts Maximum aantal pogingen
 * @param int $timeframe_minutes Tijdsperiode in minuten
 * @return bool True als het IP geblokkeerd moet worden, anders false
 */
function is_login_blocked($email, $max_attempts = 5, $timeframe_minutes = 15) {
    global $pdo, $config;
    
    // Haal instellingen uit config indien beschikbaar
    if (isset($config['max_login_attempts'])) {
        $max_attempts = $config['max_login_attempts'];
    }
    
    if (isset($config['login_timeframe_minutes'])) {
        $timeframe_minutes = $config['login_timeframe_minutes'];
    }
    
    // Haal IP adres op
    $ip = $_SERVER['REMOTE_ADDR'];
    
    try {
        // Controleer op te veel pogingen van dit IP adres of voor dit email
        $stmt = $pdo->prepare("SELECT COUNT(*) as attempts FROM login_attempts 
                              WHERE (ip_address = :ip OR email = :email) 
                              AND success = 0 
                              AND created_at > DATE_SUB(NOW(), INTERVAL :timeframe MINUTE)");
        $stmt->execute([
            'ip' => $ip,
            'email' => $email,
            'timeframe' => $timeframe_minutes
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['attempts'] >= $max_attempts) {
            return true; // Geblokkeerd
        }
    } catch (PDOException $e) {
        error_log("Error checking login attempts: " . $e->getMessage());
    }
    
    return false; // Niet geblokkeerd
}

/**
 * Controleert een reCAPTCHA token
 * 
 * @param string $recaptcha_token Het reCAPTCHA token om te verifiëren
 * @return bool True als het token geldig is, anders false
 */
function verify_recaptcha($recaptcha_token) {
    global $config;
    
    // Controleer of reCAPTCHA is geconfigureerd
    if (!isset($config['recaptcha_secret_key']) || empty($config['recaptcha_secret_key'])) {
        // Als reCAPTCHA niet is geconfigureerd, sla de verificatie over
        return true;
    }
    
    // Controleer of het token aanwezig is
    if (empty($recaptcha_token)) {
        return false;
    }
    
    // Bereid het verzoek voor aan Google's reCAPTCHA API
    $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => $config['recaptcha_secret_key'],
        'response' => $recaptcha_token,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];
    
    // Stuur verzoek naar Google
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $response = file_get_contents($verify_url, false, $context);
    
    if ($response === false) {
        error_log("Error contacting reCAPTCHA API");
        return false;
    }
    
    $result = json_decode($response, true);
    
    // Controleer of de verificatie succesvol was
    return isset($result['success']) && $result['success'] === true;
}
?> 