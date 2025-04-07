<?php
/**
 * Validator Class
 * 
 * Biedt methoden voor het valideren en sanitizen van gebruikersinvoer.
 * 
 * @version 1.0.0
 * @author SlimmerMetAI Team
 */

class Validator {
    private $errors = [];
    private $data = [];
    private $rules = [];
    
    /**
     * Constructor
     * 
     * @param array $data De te valideren data
     * @param array $rules De validatieregels
     */
    public function __construct(array $data = [], array $rules = []) {
        $this->data = $data;
        $this->rules = $rules;
    }
    
    /**
     * Valideer de data tegen de regels
     * 
     * @return boolean True als alle validaties slagen, anders false
     */
    public function validate() {
        $this->errors = [];
        
        foreach ($this->rules as $field => $ruleset) {
            $rules = is_string($ruleset) ? explode('|', $ruleset) : $ruleset;
            
            foreach ($rules as $rule) {
                $params = [];
                
                // Als de regel parameters heeft (bijv. min:5)
                if (strpos($rule, ':') !== false) {
                    list($rule, $paramStr) = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                }
                
                $methodName = 'validate' . ucfirst($rule);
                
                // Als de validatiemethode bestaat
                if (method_exists($this, $methodName)) {
                    $value = isset($this->data[$field]) ? $this->data[$field] : null;
                    
                    // Als waarde niet aanwezig is en de regel is 'required'
                    if ($value === null && $rule !== 'required') {
                        continue;
                    }
                    
                    // Roep de validatiemethode aan
                    if (!$this->$methodName($field, $value, $params)) {
                        break; // Stop na de eerste fout voor dit veld
                    }
                }
            }
        }
        
        return empty($this->errors);
    }
    
    /**
     * Voeg handmatig een fout toe
     * 
     * @param string $field Het veld met de fout
     * @param string $message Het foutbericht
     */
    public function addError($field, $message) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        
        $this->errors[$field][] = $message;
    }
    
    /**
     * Krijg alle validatiefouten
     * 
     * @return array De validatiefouten
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Krijg de fouten voor een specifiek veld
     * 
     * @param string $field De veldnaam
     * @return array De fouten voor het veld
     */
    public function getErrorsForField($field) {
        return isset($this->errors[$field]) ? $this->errors[$field] : [];
    }
    
    /**
     * Haal alle fouten op als een platte array
     * 
     * @return array Alle foutmeldingen
     */
    public function getAllErrors() {
        $allErrors = [];
        
        foreach ($this->errors as $fieldErrors) {
            $allErrors = array_merge($allErrors, $fieldErrors);
        }
        
        return $allErrors;
    }
    
    /**
     * Controleer of een veld fouten heeft
     * 
     * @param string $field De veldnaam
     * @return boolean True als het veld fouten heeft
     */
    public function hasError($field) {
        return isset($this->errors[$field]) && !empty($this->errors[$field]);
    }
    
    /**
     * Verkrijg de gevalideerde data (zonder fouten)
     * 
     * @return array De gevalideerde data
     */
    public function getValidData() {
        $validData = [];
        
        foreach ($this->rules as $field => $rules) {
            if (!$this->hasError($field) && isset($this->data[$field])) {
                $validData[$field] = $this->data[$field];
            }
        }
        
        return $validData;
    }
    
    /**
     * Sanitize alle data volgens de regels
     * 
     * @return array De gesaniteerde data
     */
    public function sanitize() {
        $sanitizedData = [];
        
        foreach ($this->data as $field => $value) {
            if (isset($this->rules[$field])) {
                $rules = is_string($this->rules[$field]) ? explode('|', $this->rules[$field]) : $this->rules[$field];
                
                foreach ($rules as $rule) {
                    if (strpos($rule, 'sanitize') === 0) {
                        $methodName = $rule;
                        
                        if (method_exists($this, $methodName)) {
                            $value = $this->$methodName($value);
                        }
                    }
                }
            }
            
            $sanitizedData[$field] = $value;
        }
        
        $this->data = $sanitizedData;
        return $sanitizedData;
    }
    
    // ======================================================================
    // Validatiemethoden
    // ======================================================================
    
    /**
     * Valideer dat een veld ingevuld is
     */
    protected function validateRequired($field, $value, $params) {
        $valid = $value !== null && $value !== '';
        
        if (!$valid) {
            $this->addError($field, "Het veld '$field' is verplicht.");
        }
        
        return $valid;
    }
    
    /**
     * Valideer de minimale lengte van een string
     */
    protected function validateMin($field, $value, $params) {
        $min = (int) $params[0];
        $valid = strlen($value) >= $min;
        
        if (!$valid) {
            $this->addError($field, "Het veld '$field' moet minimaal $min tekens bevatten.");
        }
        
        return $valid;
    }
    
    /**
     * Valideer de maximale lengte van een string
     */
    protected function validateMax($field, $value, $params) {
        $max = (int) $params[0];
        $valid = strlen($value) <= $max;
        
        if (!$valid) {
            $this->addError($field, "Het veld '$field' mag maximaal $max tekens bevatten.");
        }
        
        return $valid;
    }
    
    /**
     * Valideer dat een waarde een geldig e-mailadres is
     */
    protected function validateEmail($field, $value, $params) {
        $valid = filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
        
        if (!$valid) {
            $this->addError($field, "Het veld '$field' moet een geldig e-mailadres bevatten.");
        }
        
        return $valid;
    }
    
    /**
     * Valideer dat een waarde numeriek is
     */
    protected function validateNumeric($field, $value, $params) {
        $valid = is_numeric($value);
        
        if (!$valid) {
            $this->addError($field, "Het veld '$field' moet een numerieke waarde bevatten.");
        }
        
        return $valid;
    }
    
    /**
     * Valideer dat een waarde een integer is
     */
    protected function validateInteger($field, $value, $params) {
        $valid = filter_var($value, FILTER_VALIDATE_INT) !== false;
        
        if (!$valid) {
            $this->addError($field, "Het veld '$field' moet een geheel getal zijn.");
        }
        
        return $valid;
    }
    
    /**
     * Valideer dat een waarde een URL is
     */
    protected function validateUrl($field, $value, $params) {
        $valid = filter_var($value, FILTER_VALIDATE_URL) !== false;
        
        if (!$valid) {
            $this->addError($field, "Het veld '$field' moet een geldige URL bevatten.");
        }
        
        return $valid;
    }
    
    /**
     * Valideer dat een waarde binnen een bereik valt
     */
    protected function validateBetween($field, $value, $params) {
        $min = (int) $params[0];
        $max = (int) $params[1];
        
        $valid = is_numeric($value) && $value >= $min && $value <= $max;
        
        if (!$valid) {
            $this->addError($field, "Het veld '$field' moet tussen $min en $max liggen.");
        }
        
        return $valid;
    }
    
    /**
     * Valideer dat een waarde een van de gegeven opties is
     */
    protected function validateIn($field, $value, $params) {
        $valid = in_array($value, $params);
        
        if (!$valid) {
            $options = implode(', ', $params);
            $this->addError($field, "Het veld '$field' moet een van de volgende waardes zijn: $options.");
        }
        
        return $valid;
    }
    
    /**
     * Valideer dat een waarde een datum is
     */
    protected function validateDate($field, $value, $params) {
        $format = isset($params[0]) ? $params[0] : 'Y-m-d';
        $date = \DateTime::createFromFormat($format, $value);
        $valid = $date && $date->format($format) === $value;
        
        if (!$valid) {
            $this->addError($field, "Het veld '$field' moet een geldige datum zijn in het formaat $format.");
        }
        
        return $valid;
    }
    
    /**
     * Valideer dat een waarde gelijk is aan een ander veld
     */
    protected function validateMatch($field, $value, $params) {
        $otherField = $params[0];
        $otherValue = isset($this->data[$otherField]) ? $this->data[$otherField] : null;
        
        $valid = $value === $otherValue;
        
        if (!$valid) {
            $this->addError($field, "Het veld '$field' moet overeenkomen met het veld '$otherField'.");
        }
        
        return $valid;
    }
    
    /**
     * Valideer dat een waarde voldoet aan een reguliere expressie
     */
    protected function validateRegex($field, $value, $params) {
        $pattern = $params[0];
        $valid = preg_match($pattern, $value) === 1;
        
        if (!$valid) {
            $this->addError($field, "Het veld '$field' heeft een ongeldig formaat.");
        }
        
        return $valid;
    }
    
    /**
     * Valideer dat een bestand is geüpload
     */
    protected function validateFile($field, $value, $params) {
        $valid = isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK;
        
        if (!$valid) {
            $this->addError($field, "Er is geen geldig bestand geüpload voor '$field'.");
        }
        
        return $valid;
    }
    
    // ======================================================================
    // Sanitatiemethoden
    // ======================================================================
    
    /**
     * Saniteer een tekst voor veilige weergave in HTML
     */
    public static function sanitizeString($value) {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Saniteer een e-mailadres
     */
    public static function sanitizeEmail($value) {
        $value = strtolower(trim($value));
        return filter_var($value, FILTER_SANITIZE_EMAIL);
    }
    
    /**
     * Saniteer een integer
     */
    public static function sanitizeInt($value) {
        return (int) $value;
    }
    
    /**
     * Saniteer een float
     */
    public static function sanitizeFloat($value) {
        return (float) $value;
    }
    
    /**
     * Saniteer een URL
     */
    public static function sanitizeUrl($value) {
        return filter_var(trim($value), FILTER_SANITIZE_URL);
    }
    
    /**
     * Strip alle HTML tags uit een tekst
     */
    public static function sanitizeStripTags($value) {
        return strip_tags($value);
    }
    
    /**
     * Saniteer een array
     */
    public static function sanitizeArray($array) {
        if (!is_array($array)) {
            return [];
        }
        
        $sanitized = [];
        
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeArray($value);
            } else {
                $sanitized[$key] = self::sanitizeString($value);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Verwijder onveilige tekens uit bestandsnamen
     */
    public static function sanitizeFileName($fileName) {
        // Verwijder onveilige karakters en pad traversal
        $fileName = basename($fileName);
        $fileName = preg_replace('/[^a-zA-Z0-9\-\_\.]/', '_', $fileName);
        
        // Verwijder meerdere opeenvolgende punten en underscores
        $fileName = preg_replace('/\.+/', '.', $fileName);
        $fileName = preg_replace('/_+/', '_', $fileName);
        
        return $fileName;
    }
}
