<?php

namespace App\Infrastructure\Security;

/**
 * Validator Class
 *
 * Biedt methoden voor het valideren en sanitizen van gebruikersinvoer.
 *
 * @version 1.0.0
 */
class Validator
{
    private array $errors = [];
    private array $data = [];
    private array $rules = [];

    public function __construct(array $data = [], array $rules = [])
    {
        $this->data = $data;
        $this->rules = $rules;
    }

    public function validate(): bool
    {
        $this->errors = [];
        foreach ($this->rules as $field => $ruleset) {
            $rules = is_string($ruleset) ? explode('|', $ruleset) : $ruleset;
            foreach ($rules as $rule) {
                $params = [];
                if (strpos($rule, ':') !== false) {
                    [$rule, $paramStr] = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                }
                $methodName = 'validate' . ucfirst($rule);
                if (method_exists($this, $methodName)) {
                    $value = $this->data[$field] ?? null;
                    if ($value === null && $rule !== 'required') {
                        continue;
                    }
                    if (!$this->$methodName($field, $value, $params)) {
                        break;
                    }
                }
            }
        }
        return empty($this->errors);
    }

    public function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
    public function getErrors(): array
    {
        return $this->errors;
    }
    public function getErrorsForField(string $field): array
    {
        return $this->errors[$field] ?? [];
    }
    public function getAllErrors(): array
    {
        return array_merge(...array_values($this->errors));
    }
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]) && !empty($this->errors[$field]);
    }
    public function getValidData(): array
    {
        $validData = [];
        foreach ($this->rules as $field => $rules) {
            if (!$this->hasError($field) && isset($this->data[$field])) {
                $validData[$field] = $this->data[$field];
            }
        }
        return $validData;
    }
    public function sanitize(): array
    {
        $sanitizedData = [];
        foreach ($this->data as $field => $value) {
            if (isset($this->rules[$field])) {
                $rules = is_string($this->rules[$field]) ? explode('|', $this->rules[$field]) : $this->rules[$field];
                foreach ($rules as $rule) {
                    if (str_starts_with($rule, 'sanitize')) {
                        if (method_exists($this, $rule)) {
                                $value = $this->$rule($value);
                        }
                    }
                }
            }
            $sanitizedData[$field] = $value;
        }
        $this->data = $sanitizedData;
        return $sanitizedData;
    }
    // ================== VALIDATORS ==================
    protected function validateRequired($field, $value, $params)
    {
        $v = $value !== null && $value !== '';
        if (!$v) {
            $this->addError($field, "Het veld '$field' is verplicht.");
        }
        return $v;
    }
    protected function validateMin($field, $value, $p)
    {
        $min = (int)$p[0];
        $v = strlen($value) >= $min;
        if (!$v) {
            $this->addError($field, "Het veld '$field' moet minimaal $min tekens bevatten.");
        }
        return $v;
    }
    protected function validateMax($f, $v, $p)
    {
        $max = (int)$p[0];
        $ok = strlen($v) <= $max;
        if (!$ok) {
            $this->addError($f, "Het veld '$f' mag maximaal $max tekens bevatten.");
        }
        return $ok;
    }
    protected function validateEmail($f, $v, $p)
    {
        $ok = filter_var($v, FILTER_VALIDATE_EMAIL) !== false;
        if (!$ok) {
            $this->addError($f, "Het veld '$f' moet een geldig e-mailadres bevatten.");
        }
        return $ok;
    }
    protected function validateNumeric($f, $v, $p)
    {
        $ok = is_numeric($v);
        if (!$ok) {
            $this->addError($f, "Het veld '$f' moet numeriek zijn.");
        }
        return $ok;
    }
    protected function validateInteger($f, $v, $p)
    {
        $ok = filter_var($v, FILTER_VALIDATE_INT) !== false;
        if (!$ok) {
            $this->addError($f, "Het veld '$f' moet een geheel getal zijn.");
        }
        return $ok;
    }
    protected function validateUrl($f, $v, $p)
    {
        $ok = filter_var($v, FILTER_VALIDATE_URL) !== false;
        if (!$ok) {
            $this->addError($f, "Het veld '$f' moet een geldige URL bevatten.");
        }
        return $ok;
    }
    protected function validateBetween($f, $v, $p)
    {
        $min = (int)$p[0];
        $max = (int)$p[1];
        $ok = is_numeric($v) && $v >= $min && $v <= $max;
        if (!$ok) {
            $this->addError($f, "Het veld '$f' moet tussen $min en $max liggen.");
        }
        return $ok;
    }
    protected function validateIn($f, $v, $p)
    {
        $ok = in_array($v, $p);
        if (!$ok) {
            $this->addError($f, "Het veld '$f' moet een van de volgende waardes zijn: " . implode(', ', $p) . '.');
        }
        return $ok;
    }
    protected function validateDate($f, $v, $p)
    {
        $fmt = $p[0] ?? 'Y-m-d';
        $d = \DateTime::createFromFormat($fmt, $v);
        $ok = $d && $d->format($fmt) === $v;
        if (!$ok) {
            $this->addError($f, "Het veld '$f' moet een geldige datum zijn in het formaat $fmt.");
        }
        return $ok;
    }
    protected function validateMatch($f, $v, $p)
    {
        $other = $p[0];
        $ok = $v === ($this->data[$other] ?? null);
        if (!$ok) {
            $this->addError($f, "Het veld '$f' moet overeenkomen met het veld '$other'.");
        }
        return $ok;
    }
    protected function validateRegex($f, $v, $p)
    {
        $pat = $p[0];
        $ok = preg_match($pat, $v) === 1;
        if (!$ok) {
            $this->addError($f, "Het veld '$f' heeft een ongeldig formaat.");
        }
        return $ok;
    }
    protected function validateFile($f, $v, $p)
    {
        $ok = isset($_FILES[$f]) && $_FILES[$f]['error'] === UPLOAD_ERR_OK;
        if (!$ok) {
            $this->addError($f, "Er is geen geldig bestand geüpload voor '$f'.");
        }
        return $ok;
    }
    // ================== SANITIZERS ==================
    public static function sanitizeString($v)
    {
        return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8');
    }

    public static function sanitizeEmail($v)
    {
        return filter_var(strtolower(trim($v)), FILTER_SANITIZE_EMAIL);
    }

    public static function sanitizeInt($v)
    {
        return (int)$v;
    }

    public static function sanitizeFloat($v)
    {
        return (float)$v;
    }

    public static function sanitizeUrl($v)
    {
        return filter_var(trim($v), FILTER_SANITIZE_URL);
    }

    public static function sanitizeStripTags($v)
    {
        return strip_tags($v);
    }

    public static function sanitizeArray($a)
    {
        if (!is_array($a)) {
            return[];
        }
        $s = [];
        foreach ($a as $k => $v) {
            $s[$k] = is_array($v) ? self::sanitizeArray($v) : self::sanitizeString($v);
        }
        return $s;
    }

    public static function sanitizeFileName($n)
    {
        $n = basename($n);
        $n = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $n);
        $n = preg_replace('/\.+/', '.', $n);
        $n = preg_replace('/_+/', '_', $n);
        return $n;
    }
}
