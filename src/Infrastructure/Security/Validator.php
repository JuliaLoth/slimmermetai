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
    protected function validateRequired(string $field, mixed $value, array $params): bool
    {
        $v = $value !== null && $value !== '';
        if (!$v) {
            $this->addError($field, "Het veld '$field' is verplicht.");
        }
        return $v;
    }
    protected function validateMin(string $field, mixed $value, array $params): bool
    {
        $min = (int)$params[0];
        $v = strlen((string)$value) >= $min;
        if (!$v) {
            $this->addError($field, "Het veld '$field' moet minimaal $min tekens bevatten.");
        }
        return $v;
    }
    protected function validateMax(string $field, mixed $value, array $params): bool
    {
        $max = (int)$params[0];
        $ok = strlen((string)$value) <= $max;
        if (!$ok) {
            $this->addError($field, "Het veld '$field' mag maximaal $max tekens bevatten.");
        }
        return $ok;
    }
    protected function validateEmail(string $field, mixed $value, array $params): bool
    {
        $ok = filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
        if (!$ok) {
            $this->addError($field, "Het veld '$field' moet een geldig e-mailadres bevatten.");
        }
        return $ok;
    }
    protected function validateNumeric(string $field, mixed $value, array $params): bool
    {
        $ok = is_numeric($value);
        if (!$ok) {
            $this->addError($field, "Het veld '$field' moet numeriek zijn.");
        }
        return $ok;
    }
    protected function validateInteger(string $field, mixed $value, array $params): bool
    {
        $ok = filter_var($value, FILTER_VALIDATE_INT) !== false;
        if (!$ok) {
            $this->addError($field, "Het veld '$field' moet een geheel getal zijn.");
        }
        return $ok;
    }
    protected function validateUrl(string $field, mixed $value, array $params): bool
    {
        $ok = filter_var($value, FILTER_VALIDATE_URL) !== false;
        if (!$ok) {
            $this->addError($field, "Het veld '$field' moet een geldige URL bevatten.");
        }
        return $ok;
    }
    protected function validateBetween(string $field, mixed $value, array $params): bool
    {
        $min = (int)$params[0];
        $max = (int)$params[1];
        $ok = is_numeric($value) && $value >= $min && $value <= $max;
        if (!$ok) {
            $this->addError($field, "Het veld '$field' moet tussen $min en $max liggen.");
        }
        return $ok;
    }
    protected function validateIn(string $field, mixed $value, array $params): bool
    {
        $ok = in_array($value, $params);
        if (!$ok) {
            $this->addError($field, "Het veld '$field' moet een van de volgende waardes zijn: " . implode(', ', $params) . '.');
        }
        return $ok;
    }
    protected function validateDate(string $field, mixed $value, array $params): bool
    {
        $fmt = $params[0] ?? 'Y-m-d';
        $d = \DateTime::createFromFormat($fmt, (string)$value);
        $ok = $d && $d->format($fmt) === (string)$value;
        if (!$ok) {
            $this->addError($field, "Het veld '$field' moet een geldige datum zijn in het formaat $fmt.");
        }
        return $ok;
    }
    protected function validateMatch(string $field, mixed $value, array $params): bool
    {
        $other = $params[0];
        $ok = $value === ($this->data[$other] ?? null);
        if (!$ok) {
            $this->addError($field, "Het veld '$field' moet overeenkomen met het veld '$other'.");
        }
        return $ok;
    }
    protected function validateRegex(string $field, mixed $value, array $params): bool
    {
        $pat = $params[0];
        $ok = preg_match($pat, (string)$value) === 1;
        if (!$ok) {
            $this->addError($field, "Het veld '$field' heeft een ongeldig formaat.");
        }
        return $ok;
    }
    protected function validateFile(string $field, mixed $value, array $params): bool
    {
        $ok = isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK;
        if (!$ok) {
            $this->addError($field, "Er is geen geldig bestand geüpload voor '$field'.");
        }
        return $ok;
    }
    // ================== SANITIZERS ==================
    public static function sanitizeString(mixed $v): string
    {
        return htmlspecialchars(trim((string)$v), ENT_QUOTES, 'UTF-8');
    }

    public static function sanitizeEmail(mixed $v): string
    {
        return filter_var(strtolower(trim((string)$v)), FILTER_SANITIZE_EMAIL);
    }

    public static function sanitizeInt(mixed $v): int
    {
        return (int)$v;
    }

    public static function sanitizeFloat(mixed $v): float
    {
        return (float)$v;
    }

    public static function sanitizeUrl(mixed $v): string
    {
        return filter_var(trim((string)$v), FILTER_SANITIZE_URL);
    }

    public static function sanitizeStripTags(mixed $v): string
    {
        return strip_tags((string)$v);
    }

    public static function sanitizeArray(mixed $a): array
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

    public static function sanitizeFileName(mixed $n): string
    {
        $n = basename((string)$n);
        $n = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $n);
        $n = preg_replace('/\.+/', '.', $n);
        $n = preg_replace('/_+/', '_', $n);
        return $n;
    }
}
