<?php
/**
 * JwtHandler Class
 * 
 * Biedt functionaliteit voor het genereren en valideren van JWT-tokens.
 * 
 * @version 1.0.0
 * @author SlimmerMetAI Team
 */

class JwtHandler {
    private $secret;
    private $algorithm;
    private $expiration;
    
    /**
     * Constructor
     * 
     * @param string $secret De geheime sleutel voor het ondertekenen van tokens
     * @param string $algorithm Het te gebruiken algoritme (standaard HS256)
     * @param int $expiration De standaard expiratie tijd in seconden (standaard 3600)
     */
    public function __construct($secret = null, $algorithm = 'HS256', $expiration = 3600) {
        if ($secret === null) {
            // Laad de configuratie als die al bestaat
            if (class_exists('Config')) {
                $config = Config::getInstance();
                $secret = $config->get('jwt_secret');
                $expiration = $config->get('jwt_expiration', $expiration);
            } else {
                // Fallback naar constanten als Config niet bestaat
                $secret = defined('JWT_SECRET') ? JWT_SECRET : '';
                $expiration = defined('JWT_EXPIRATION') ? JWT_EXPIRATION : $expiration;
            }
        }
        
        if (empty($secret)) {
            throw new Exception('JWT secret is niet geconfigureerd.');
        }
        
        $this->secret = $secret;
        $this->algorithm = $algorithm;
        $this->expiration = $expiration;
    }
    
    /**
     * Genereer een JWT-token
     * 
     * @param array $payload De payload voor het token
     * @param int $expiration Optioneel: specifieke expiratie tijd
     * @return string Het gegenereerde token
     */
    public function generateToken($payload, $expiration = null) {
        $expTime = time() + ($expiration !== null ? $expiration : $this->expiration);
        
        // Standaardclaims toevoegen
        $payload['iat'] = time();     // Issued At
        $payload['exp'] = $expTime;   // Expiration Time
        $payload['nbf'] = time();     // Not Before
        
        // Als geen JTI is opgegeven, genereer er een
        if (!isset($payload['jti'])) {
            $payload['jti'] = $this->generateJti();
        }
        
        // Header
        $header = [
            'alg' => $this->algorithm,
            'typ' => 'JWT'
        ];
        
        // Encodeer de header en payload naar base64
        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        
        // Maak de signature
        $signature = $this->generateSignature($headerEncoded, $payloadEncoded);
        $signatureEncoded = $this->base64UrlEncode($signature);
        
        // Combineer alles tot een token
        return "$headerEncoded.$payloadEncoded.$signatureEncoded";
    }
    
    /**
     * Valideer een JWT-token
     * 
     * @param string $token Het te valideren token
     * @return array|false De payload als het token geldig is, anders false
     */
    public function validateToken($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return false;
        }
        
        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
        
        // Decodeer de header en payload
        $header = json_decode($this->base64UrlDecode($headerEncoded), true);
        $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);
        
        if (!$header || !$payload) {
            return false;
        }
        
        // Controleer het algoritme
        if (!isset($header['alg']) || $header['alg'] !== $this->algorithm) {
            return false;
        }
        
        // Controleer de signature
        $signature = $this->base64UrlDecode($signatureEncoded);
        $expectedSignature = $this->generateSignature($headerEncoded, $payloadEncoded);
        
        if (!$this->secureCompare($signature, $expectedSignature)) {
            return false;
        }
        
        // Controleer de expiratie
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }
        
        // Controleer de not-before claim
        if (isset($payload['nbf']) && $payload['nbf'] > time()) {
            return false;
        }
        
        return $payload;
    }
    
    /**
     * Vernieuw een token
     * 
     * @param string $token Het te vernieuwen token
     * @param int $expiration Optioneel: nieuwe expiratie tijd
     * @return string|false Het vernieuwde token of false bij fout
     */
    public function refreshToken($token, $expiration = null) {
        $payload = $this->validateToken($token);
        
        if (!$payload) {
            return false;
        }
        
        // Verwijder de oude tijds-claims
        unset($payload['iat']);
        unset($payload['exp']);
        unset($payload['nbf']);
        
        // Genereer een nieuw token
        return $this->generateToken($payload, $expiration);
    }
    
    /**
     * Genereer een signature
     * 
     * @param string $headerEncoded De gecodeerde header
     * @param string $payloadEncoded De gecodeerde payload
     * @return string De gegenereerde signature
     * @throws Exception Als het algoritme niet ondersteund wordt
     */
    private function generateSignature($headerEncoded, $payloadEncoded) {
        $data = "$headerEncoded.$payloadEncoded";
        
        switch ($this->algorithm) {
            case 'HS256':
                return hash_hmac('sha256', $data, $this->secret, true);
            case 'HS384':
                return hash_hmac('sha384', $data, $this->secret, true);
            case 'HS512':
                return hash_hmac('sha512', $data, $this->secret, true);
            default:
                throw new Exception("Algoritme '$this->algorithm' wordt niet ondersteund.");
        }
    }
    
    /**
     * Genereer een unieke token-ID
     * 
     * @return string Een unieke ID
     */
    private function generateJti() {
        return bin2hex(random_bytes(16));
    }
    
    /**
     * Encodeer een string naar base64url
     * 
     * @param string $data De te encoderen data
     * @return string De geëncodeerde data
     */
    private function base64UrlEncode($data) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }
    
    /**
     * Decodeer een base64url-string
     * 
     * @param string $data De te decoderen data
     * @return string De gedecodeerde data
     */
    private function base64UrlDecode($data) {
        $padding = strlen($data) % 4;
        
        if ($padding > 0) {
            $data .= str_repeat('=', 4 - $padding);
        }
        
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
    
    /**
     * Vergelijk twee strings op een veilige manier (bescherming tegen timing attacks)
     * 
     * @param string $a Eerste string
     * @param string $b Tweede string
     * @return boolean True als de strings gelijk zijn
     */
    private function secureCompare($a, $b) {
        if (function_exists('hash_equals')) {
            return hash_equals($a, $b);
        }
        
        // Fallback voor PHP < 5.6
        if (strlen($a) !== strlen($b)) {
            return false;
        }
        
        $result = 0;
        
        for ($i = 0; $i < strlen($a); $i++) {
            $result |= ord($a[$i]) ^ ord($b[$i]);
        }
        
        return $result === 0;
    }
    
    /**
     * Haal de payload uit een token zonder validatie (voor debugging)
     * 
     * @param string $token Het JWT-token
     * @return array|false De payload of false bij fout
     */
    public function getPayloadWithoutValidation($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return false;
        }
        
        return json_decode($this->base64UrlDecode($parts[1]), true);
    }
}
