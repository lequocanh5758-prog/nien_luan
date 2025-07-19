<?php
/**
 * Advanced Input Validator - Comprehensive input validation and sanitization
 * Priority: HIGH - Security enhancement
 */

class InputValidator {
    
    // Validation rules
    const RULE_REQUIRED = 'required';
    const RULE_EMAIL = 'email';
    const RULE_PHONE = 'phone';
    const RULE_NUMERIC = 'numeric';
    const RULE_INTEGER = 'integer';
    const RULE_FLOAT = 'float';
    const RULE_MIN_LENGTH = 'min_length';
    const RULE_MAX_LENGTH = 'max_length';
    const RULE_MIN_VALUE = 'min_value';
    const RULE_MAX_VALUE = 'max_value';
    const RULE_REGEX = 'regex';
    const RULE_IN = 'in';
    const RULE_NOT_IN = 'not_in';
    const RULE_URL = 'url';
    const RULE_DATE = 'date';
    const RULE_ALPHA = 'alpha';
    const RULE_ALPHA_NUMERIC = 'alpha_numeric';
    const RULE_NO_HTML = 'no_html';
    const RULE_NO_SCRIPT = 'no_script';
    
    private $errors = [];
    private $data = [];
    
    /**
     * Validate input data against rules
     * 
     * @param array $data Input data to validate
     * @param array $rules Validation rules
     * @return bool True if validation passes
     */
    public function validate($data, $rules) {
        $this->errors = [];
        $this->data = $data;
        
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $this->validateField($field, $value, $fieldRules);
        }
        
        return empty($this->errors);
    }
    
    /**
     * Validate a single field
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param array|string $rules Validation rules
     */
    private function validateField($field, $value, $rules) {
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }
        
        foreach ($rules as $rule) {
            $this->applyRule($field, $value, $rule);
        }
    }
    
    /**
     * Apply a single validation rule
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param string $rule Validation rule
     */
    private function applyRule($field, $value, $rule) {
        $parts = explode(':', $rule, 2);
        $ruleName = $parts[0];
        $ruleValue = $parts[1] ?? null;
        
        switch ($ruleName) {
            case self::RULE_REQUIRED:
                if (empty($value) && $value !== '0') {
                    $this->addError($field, "Field {$field} is required");
                }
                break;
                
            case self::RULE_EMAIL:
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "Field {$field} must be a valid email address");
                }
                break;
                
            case self::RULE_PHONE:
                if (!empty($value) && !preg_match('/^[0-9+\-\s\(\)]{10,15}$/', $value)) {
                    $this->addError($field, "Field {$field} must be a valid phone number");
                }
                break;
                
            case self::RULE_NUMERIC:
                if (!empty($value) && !is_numeric($value)) {
                    $this->addError($field, "Field {$field} must be numeric");
                }
                break;
                
            case self::RULE_INTEGER:
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
                    $this->addError($field, "Field {$field} must be an integer");
                }
                break;
                
            case self::RULE_FLOAT:
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_FLOAT)) {
                    $this->addError($field, "Field {$field} must be a float");
                }
                break;
                
            case self::RULE_MIN_LENGTH:
                if (!empty($value) && strlen($value) < (int)$ruleValue) {
                    $this->addError($field, "Field {$field} must be at least {$ruleValue} characters long");
                }
                break;
                
            case self::RULE_MAX_LENGTH:
                if (!empty($value) && strlen($value) > (int)$ruleValue) {
                    $this->addError($field, "Field {$field} must not exceed {$ruleValue} characters");
                }
                break;
                
            case self::RULE_MIN_VALUE:
                if (!empty($value) && (float)$value < (float)$ruleValue) {
                    $this->addError($field, "Field {$field} must be at least {$ruleValue}");
                }
                break;
                
            case self::RULE_MAX_VALUE:
                if (!empty($value) && (float)$value > (float)$ruleValue) {
                    $this->addError($field, "Field {$field} must not exceed {$ruleValue}");
                }
                break;
                
            case self::RULE_REGEX:
                if (!empty($value) && !preg_match($ruleValue, $value)) {
                    $this->addError($field, "Field {$field} format is invalid");
                }
                break;
                
            case self::RULE_IN:
                $allowedValues = explode(',', $ruleValue);
                if (!empty($value) && !in_array($value, $allowedValues)) {
                    $this->addError($field, "Field {$field} must be one of: " . implode(', ', $allowedValues));
                }
                break;
                
            case self::RULE_NOT_IN:
                $disallowedValues = explode(',', $ruleValue);
                if (!empty($value) && in_array($value, $disallowedValues)) {
                    $this->addError($field, "Field {$field} cannot be one of: " . implode(', ', $disallowedValues));
                }
                break;
                
            case self::RULE_URL:
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, "Field {$field} must be a valid URL");
                }
                break;
                
            case self::RULE_DATE:
                if (!empty($value) && !strtotime($value)) {
                    $this->addError($field, "Field {$field} must be a valid date");
                }
                break;
                
            case self::RULE_ALPHA:
                if (!empty($value) && !preg_match('/^[a-zA-Z\s]+$/', $value)) {
                    $this->addError($field, "Field {$field} must contain only letters");
                }
                break;
                
            case self::RULE_ALPHA_NUMERIC:
                if (!empty($value) && !preg_match('/^[a-zA-Z0-9\s]+$/', $value)) {
                    $this->addError($field, "Field {$field} must contain only letters and numbers");
                }
                break;
                
            case self::RULE_NO_HTML:
                if (!empty($value) && $value !== strip_tags($value)) {
                    $this->addError($field, "Field {$field} cannot contain HTML tags");
                }
                break;
                
            case self::RULE_NO_SCRIPT:
                if (!empty($value) && preg_match('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', $value)) {
                    $this->addError($field, "Field {$field} cannot contain script tags");
                }
                break;
        }
    }
    
    /**
     * Add validation error
     * 
     * @param string $field Field name
     * @param string $message Error message
     */
    private function addError($field, $message) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
        
        if (class_exists('Logger')) {
            Logger::warning("Validation error", [
                'field' => $field,
                'message' => $message,
                'value' => $this->data[$field] ?? null
            ]);
        }
    }
    
    /**
     * Get validation errors
     * 
     * @return array Validation errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Get errors for a specific field
     * 
     * @param string $field Field name
     * @return array Field errors
     */
    public function getFieldErrors($field) {
        return $this->errors[$field] ?? [];
    }
    
    /**
     * Check if validation has errors
     * 
     * @return bool True if there are errors
     */
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    /**
     * Get first error message
     * 
     * @return string|null First error message
     */
    public function getFirstError() {
        foreach ($this->errors as $fieldErrors) {
            if (!empty($fieldErrors)) {
                return $fieldErrors[0];
            }
        }
        return null;
    }
    
    /**
     * Sanitize input data
     * 
     * @param array $data Input data
     * @param array $rules Sanitization rules
     * @return array Sanitized data
     */
    public static function sanitize($data, $rules = []) {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            $rule = $rules[$key] ?? 'string';
            $sanitized[$key] = self::sanitizeValue($value, $rule);
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize a single value
     * 
     * @param mixed $value Value to sanitize
     * @param string $type Sanitization type
     * @return mixed Sanitized value
     */
    public static function sanitizeValue($value, $type = 'string') {
        if ($value === null || $value === '') {
            return $value;
        }
        
        switch ($type) {
            case 'email':
                return filter_var($value, FILTER_SANITIZE_EMAIL);
                
            case 'int':
            case 'integer':
                return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
                
            case 'float':
                return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                
            case 'url':
                return filter_var($value, FILTER_SANITIZE_URL);
                
            case 'html':
                return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                
            case 'strip_tags':
                return strip_tags($value);
                
            case 'trim':
                return trim($value);
                
            case 'alpha':
                return preg_replace('/[^a-zA-Z\s]/', '', $value);
                
            case 'alpha_numeric':
                return preg_replace('/[^a-zA-Z0-9\s]/', '', $value);
                
            case 'numeric':
                return preg_replace('/[^0-9.]/', '', $value);
                
            case 'string':
            default:
                // Remove null bytes and control characters
                $value = str_replace(chr(0), '', $value);
                $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
                return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * Quick validation helper
     * 
     * @param array $data Input data
     * @param array $rules Validation rules
     * @return array Result with 'valid' boolean and 'errors' array
     */
    public static function quickValidate($data, $rules) {
        $validator = new self();
        $isValid = $validator->validate($data, $rules);
        
        return [
            'valid' => $isValid,
            'errors' => $validator->getErrors(),
            'first_error' => $validator->getFirstError()
        ];
    }
}