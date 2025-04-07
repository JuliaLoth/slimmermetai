<?php
/**
 * ApiResponse Class
 * 
 * Biedt een consistente manier om API-responses te genereren.
 * 
 * @version 1.0.0
 * @author SlimmerMetAI Team
 */

class ApiResponse {
    /**
     * Stuur een succesvol response
     * 
     * @param mixed $data De data om terug te sturen
     * @param string $message Een optioneel bericht
     * @param int $statusCode De HTTP-statuscode
     */
    public static function success($data = null, $message = null, $statusCode = 200) {
        self::sendResponse([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }
    
    /**
     * Stuur een foutresponse
     * 
     * @param string $message Het foutbericht
     * @param int $statusCode De HTTP-statuscode
     * @param mixed $errors Aanvullende foutinformatie
     */
    public static function error($message, $statusCode = 400, $errors = null) {
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        self::sendResponse($response, $statusCode);
    }
    
    /**
     * Stuur een validatiefoutresponse
     * 
     * @param array $errors Associatieve array met veldnamen en foutberichten
     * @param string $message Een algemeen foutbericht
     */
    public static function validationError($errors, $message = 'Validatiefout') {
        self::error($message, 422, $errors);
    }
    
    /**
     * Stuur een niet gevonden response
     * 
     * @param string $message Het foutbericht
     */
    public static function notFound($message = 'Resource niet gevonden') {
        self::error($message, 404);
    }
    
    /**
     * Stuur een ongeautoriseerd response
     * 
     * @param string $message Het foutbericht
     */
    public static function unauthorized($message = 'Ongeautoriseerde toegang') {
        self::error($message, 401);
    }
    
    /**
     * Stuur een verboden response
     * 
     * @param string $message Het foutbericht
     */
    public static function forbidden($message = 'Toegang geweigerd') {
        self::error($message, 403);
    }
    
    /**
     * Stuur een server error response
     * 
     * @param string $message Het foutbericht
     * @param mixed $error De fout (optioneel, alleen in debug-modus)
     */
    public static function serverError($message = 'Interne serverfout', $error = null) {
        // Log de fout
        if (class_exists('ErrorHandler')) {
            $errorHandler = ErrorHandler::getInstance();
            $errorHandler->logError($message, ['error' => $error]);
        }
        
        // In debugmodus kunnen we meer details tonen
        if (defined('DEBUG_MODE') && DEBUG_MODE && $error) {
            self::error($message, 500, ['details' => (string) $error]);
        } else {
            self::error($message, 500);
        }
    }
    
    /**
     * Stuur een method not allowed response
     * 
     * @param string $message Het foutbericht
     * @param array $allowedMethods Toegestane HTTP-methoden
     */
    public static function methodNotAllowed($message = 'Methode niet toegestaan', $allowedMethods = []) {
        $headers = [];
        
        if (!empty($allowedMethods)) {
            $headers['Allow'] = implode(', ', $allowedMethods);
        }
        
        self::sendResponse([
            'success' => false,
            'message' => $message
        ], 405, $headers);
    }
    
    /**
     * Stuur een te veel requests response
     * 
     * @param string $message Het foutbericht
     * @param int $retryAfter Seconden tot nieuwe pogingen toegestaan zijn
     */
    public static function tooManyRequests($message = 'Te veel verzoeken', $retryAfter = 60) {
        $headers = ['Retry-After' => $retryAfter];
        
        self::sendResponse([
            'success' => false,
            'message' => $message,
            'retry_after' => $retryAfter
        ], 429, $headers);
    }
    
    /**
     * Stuur een response als de client content uit cache kan gebruiken
     * 
     * @param string $etag De ETag-waarde
     */
    public static function notModified($etag = null) {
        $headers = [];
        
        if ($etag) {
            $headers['ETag'] = $etag;
        }
        
        http_response_code(304);
        
        if (!empty($headers)) {
            foreach ($headers as $name => $value) {
                header("$name: $value");
            }
        }
        
        exit;
    }
    
    /**
     * Algemene methode om een response te sturen
     * 
     * @param mixed $data De te verzenden data
     * @param int $statusCode De HTTP-statuscode
     * @param array $headers Extra HTTP-headers
     */
    private static function sendResponse($data, $statusCode = 200, $headers = []) {
        // Zet HTTP-statuscode
        http_response_code($statusCode);
        
        // Zet headers
        header('Content-Type: application/json; charset=UTF-8');
        
        // CORS-headers
        header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*'));
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400'); // 24 uur
        
        // Extra headers
        if (!empty($headers)) {
            foreach ($headers as $name => $value) {
                header("$name: $value");
            }
        }
        
        // OPTIONS-verzoeken direct beantwoorden
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit;
        }
        
        // Stuur JSON-response
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
