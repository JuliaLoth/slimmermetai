<?php
/**
 * Authentication middleware voor SlimmerMetAI
 * Zorgt voor het verifiëren van JWT tokens en gebruikersrechten
 */

require_once dirname(dirname(__FILE__)) . '/config/db.php';

// Functie om JWT secret key op te halen
function getJwtSecret() {
    return getEnv('JWT_SECRET', 'default_jwt_secret_change_me');
}

// JWT token decoderen en verifiëren
function decodeJwt($token) {
    $secret = getJwtSecret();
    
    // Splits het token
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }
    
    $header = $parts[0];
    $payload = $parts[1];
    $signature = $parts[2];
    
    // Decodeer de payload
    $payload_decoded = json_decode(base64_decode(strtr($payload, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($payload)) % 4)), true);
    if (!$payload_decoded) {
        return false;
    }
    
    // Controleer of het token niet verlopen is
    if (isset($payload_decoded['exp']) && $payload_decoded['exp'] < time()) {
        return false;
    }
    
    // Verifieer de handtekening
    $data = $header . '.' . $payload;
    $signature_check = base64_encode(hash_hmac('sha256', $data, $secret, true));
    $signature_check = rtrim(strtr($signature_check, '+/', '-_'), '=');
    
    if ($signature !== $signature_check) {
        return false;
    }
    
    return $payload_decoded;
}

// Haal de Authorization Bearer token uit de headers
function getBearerToken() {
    $headers = null;
    
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
    } elseif (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER['Authorization']);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    
    if (!empty($headers) && preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
        return $matches[1];
    }
    
    return null;
}

// Functie om JWT token te genereren
function generateJwt($payload) {
    $secret = getJwtSecret();
    $issuedAt = time();
    $expire = $issuedAt + getEnv('JWT_EXPIRES_SECONDS', 3600); // 1 uur standaard
    
    $token = [
        'iat' => $issuedAt,         // Issued at: time toen het token werd gegenereerd
        'iss' => getEnv('SITE_URL', 'https://slimmermetai.com'), // Issuer
        'exp' => $expire,           // Expire
    ];
    
    $token = array_merge($token, $payload);
    
    // Encodeer Header
    $header = [
        'typ' => 'JWT',
        'alg' => 'HS256'
    ];
    $base64UrlHeader = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
    
    // Encodeer Payload
    $base64UrlPayload = rtrim(strtr(base64_encode(json_encode($token)), '+/', '-_'), '=');
    
    // Maak Signature
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
    $base64UrlSignature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    
    // Maak JWT Token
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

// Controleer of gebruiker is ingelogd
function isAuthenticated() {
    $token = getBearerToken();
    if (!$token) {
        return false;
    }
    
    $payload = decodeJwt($token);
    if (!$payload || !isset($payload['user_id'])) {
        return false;
    }
    
    return $payload;
}

// Controleer of gebruiker admin is
function isAdmin() {
    $payload = isAuthenticated();
    if (!$payload) {
        return false;
    }
    
    return isset($payload['role']) && $payload['role'] === 'admin';
}

// Middleware functie voor authenticatie
function requireAuth() {
    $payload = isAuthenticated();
    
    if (!$payload) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized'
        ]);
        exit;
    }
    
    return $payload;
}

// Middleware functie voor admin rechten
function requireAdmin() {
    $payload = isAuthenticated();
    
    if (!$payload) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized'
        ]);
        exit;
    }
    
    if (!isset($payload['role']) || $payload['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Forbidden - Admin rights required'
        ]);
        exit;
    }
    
    return $payload;
}

// Haal de huidige ingelogde gebruiker op
function getCurrentUser() {
    $payload = isAuthenticated();
    
    if (!$payload || !isset($payload['user_id'])) {
        return null;
    }
    
    try {
        $user = getUserRow("SELECT * FROM users WHERE id = ?", [$payload['user_id']]);
        
        if ($user) {
            // Verwijder wachtwoord uit de gebruikersgegevens
            unset($user['password']);
        }
        
        return $user;
    } catch (Exception $e) {
        error_log('Error getting current user: ' . $e->getMessage());
        return null;
    }
}

// Functie om refresh token te genereren
function generateRefreshToken($userId) {
    $token = bin2hex(random_bytes(32));
    $expiresIn = getEnv('REFRESH_TOKEN_EXPIRES', '30d');
    
    // Bepaal de verlooptijd op basis van de string (bijvoorbeeld '30d' voor 30 dagen)
    if (preg_match('/^(\d+)([hdmy])$/', $expiresIn, $matches)) {
        $value = (int)$matches[1];
        $unit = $matches[2];
        
        switch ($unit) {
            case 'h': // uren
                $expiresAt = date('Y-m-d H:i:s', strtotime("+{$value} hours"));
                break;
            case 'd': // dagen
                $expiresAt = date('Y-m-d H:i:s', strtotime("+{$value} days"));
                break;
            case 'm': // maanden
                $expiresAt = date('Y-m-d H:i:s', strtotime("+{$value} months"));
                break;
            case 'y': // jaren
                $expiresAt = date('Y-m-d H:i:s', strtotime("+{$value} years"));
                break;
            default:
                $expiresAt = date('Y-m-d H:i:s', strtotime("+30 days")); // standaard 30 dagen
        }
    } else {
        $expiresAt = date('Y-m-d H:i:s', strtotime("+30 days")); // standaard 30 dagen
    }
    
    try {
        $query = "INSERT INTO refresh_tokens (user_id, token, expires_at) VALUES (?, ?, ?)";
        insertUserId($query, [$userId, $token, $expiresAt]);
        return $token;
    } catch (Exception $e) {
        error_log('Error generating refresh token: ' . $e->getMessage());
        return false;
    }
}

// Controleer een refresh token en genereer een nieuw access token
function verifyRefreshToken($refreshToken) {
    try {
        $query = "SELECT rt.*, u.* FROM refresh_tokens rt 
                 JOIN users u ON rt.user_id = u.id 
                 WHERE rt.token = ? AND rt.expires_at > NOW()";
        
        $result = getUserRow($query, [$refreshToken]);
        
        if (!$result) {
            return false;
        }
        
        // Genereer nieuw JWT access token
        $payload = [
            'user_id' => $result['user_id'],
            'email' => $result['email'],
            'role' => $result['role']
        ];
        
        $jwt = generateJwt($payload);
        
        // Verwijder wachtwoord uit gebruikersgegevens
        unset($result['password']);
        
        return [
            'token' => $jwt,
            'user' => $result
        ];
    } catch (Exception $e) {
        error_log('Error verifying refresh token: ' . $e->getMessage());
        return false;
    }
}

// Verwijder alle refresh tokens voor een gebruiker
function revokeRefreshTokens($userId, $exceptToken = null) {
    try {
        if ($exceptToken) {
            $query = "DELETE FROM refresh_tokens WHERE user_id = ? AND token != ?";
            queryUsers($query, [$userId, $exceptToken]);
        } else {
            $query = "DELETE FROM refresh_tokens WHERE user_id = ?";
            queryUsers($query, [$userId]);
        }
        
        return true;
    } catch (Exception $e) {
        error_log('Error revoking refresh tokens: ' . $e->getMessage());
        return false;
    }
}
?> 