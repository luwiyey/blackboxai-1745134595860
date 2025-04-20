<?php
class Validator {
    private static $instance = null;
    private $errors = [];
    private $customRules = [];

    private function __construct() {}

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function validate($data, $rules) {
        $this->errors = [];

        foreach ($rules as $field => $ruleString) {
            $fieldRules = explode('|', $ruleString);
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                if (strpos($rule, ':') !== false) {
                    list($ruleName, $ruleValue) = explode(':', $rule);
                } else {
                    $ruleName = $rule;
                    $ruleValue = null;
                }

                if (!$this->validateRule($field, $value, $ruleName, $ruleValue)) {
                    break;
                }
            }
        }

        return empty($this->errors);
    }

    private function validateRule($field, $value, $rule, $ruleParam = null) {
        $fieldName = ucwords(str_replace('_', ' ', $field));

        // Check for custom rule
        if (isset($this->customRules[$rule])) {
            if (!call_user_func($this->customRules[$rule], $value, $ruleParam)) {
                $this->addError($field, "The $fieldName field is invalid.");
                return false;
            }
            return true;
        }

        switch ($rule) {
            case 'required':
                if ($value === null || $value === '') {
                    $this->addError($field, "The $fieldName field is required.");
                    return false;
                }
                break;

            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "The $fieldName must be a valid email address.");
                    return false;
                }
                break;

            case 'min':
                if (is_string($value) && strlen($value) < $ruleParam) {
                    $this->addError($field, "The $fieldName must be at least $ruleParam characters.");
                    return false;
                } elseif (is_numeric($value) && $value < $ruleParam) {
                    $this->addError($field, "The $fieldName must be at least $ruleParam.");
                    return false;
                }
                break;

            case 'max':
                if (is_string($value) && strlen($value) > $ruleParam) {
                    $this->addError($field, "The $fieldName may not be greater than $ruleParam characters.");
                    return false;
                } elseif (is_numeric($value) && $value > $ruleParam) {
                    $this->addError($field, "The $fieldName may not be greater than $ruleParam.");
                    return false;
                }
                break;

            case 'numeric':
                if ($value && !is_numeric($value)) {
                    $this->addError($field, "The $fieldName must be a number.");
                    return false;
                }
                break;

            case 'integer':
                if ($value && !filter_var($value, FILTER_VALIDATE_INT)) {
                    $this->addError($field, "The $fieldName must be an integer.");
                    return false;
                }
                break;

            case 'alpha':
                if ($value && !ctype_alpha($value)) {
                    $this->addError($field, "The $fieldName may only contain letters.");
                    return false;
                }
                break;

            case 'alpha_num':
                if ($value && !ctype_alnum($value)) {
                    $this->addError($field, "The $fieldName may only contain letters and numbers.");
                    return false;
                }
                break;

            case 'alpha_dash':
                if ($value && !preg_match('/^[a-zA-Z0-9-_]+$/', $value)) {
                    $this->addError($field, "The $fieldName may only contain letters, numbers, dashes and underscores.");
                    return false;
                }
                break;

            case 'date':
                if ($value && !strtotime($value)) {
                    $this->addError($field, "The $fieldName must be a valid date.");
                    return false;
                }
                break;

            case 'url':
                if ($value && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, "The $fieldName must be a valid URL.");
                    return false;
                }
                break;

            case 'ip':
                if ($value && !filter_var($value, FILTER_VALIDATE_IP)) {
                    $this->addError($field, "The $fieldName must be a valid IP address.");
                    return false;
                }
                break;

            case 'in':
                $allowed = explode(',', $ruleParam);
                if ($value && !in_array($value, $allowed)) {
                    $this->addError($field, "The selected $fieldName is invalid.");
                    return false;
                }
                break;

            case 'not_in':
                $disallowed = explode(',', $ruleParam);
                if ($value && in_array($value, $disallowed)) {
                    $this->addError($field, "The selected $fieldName is invalid.");
                    return false;
                }
                break;

            case 'regex':
                if ($value && !preg_match($ruleParam, $value)) {
                    $this->addError($field, "The $fieldName format is invalid.");
                    return false;
                }
                break;

            case 'password':
                if ($value && !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $value)) {
                    $this->addError($field, "The $fieldName must be at least 8 characters and contain at least one uppercase letter, one lowercase letter, one number, and one special character.");
                    return false;
                }
                break;

            case 'phone':
                if ($value && !preg_match('/^\+?[0-9]{10,15}$/', $value)) {
                    $this->addError($field, "The $fieldName must be a valid phone number.");
                    return false;
                }
                break;

            case 'isbn':
                if ($value && !$this->isValidISBN($value)) {
                    $this->addError($field, "The $fieldName must be a valid ISBN.");
                    return false;
                }
                break;

            case 'student_id':
                if ($value && !preg_match('/^[0-9]{4}-[0-9]{5}$/', $value)) {
                    $this->addError($field, "The $fieldName must be in the format YYYY-XXXXX.");
                    return false;
                }
                break;

            case 'file':
                if (!isset($_FILES[$field])) {
                    $this->addError($field, "The $fieldName must be a file.");
                    return false;
                }
                break;

            case 'image':
                if (isset($_FILES[$field])) {
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    if (!in_array($_FILES[$field]['type'], $allowedTypes)) {
                        $this->addError($field, "The $fieldName must be an image (JPEG, PNG, GIF).");
                        return false;
                    }
                }
                break;

            case 'pdf':
                if (isset($_FILES[$field]) && $_FILES[$field]['type'] !== 'application/pdf') {
                    $this->addError($field, "The $fieldName must be a PDF file.");
                    return false;
                }
                break;

            case 'max_size':
                if (isset($_FILES[$field]) && $_FILES[$field]['size'] > ($ruleParam * 1024 * 1024)) {
                    $this->addError($field, "The $fieldName may not be greater than $ruleParam MB.");
                    return false;
                }
                break;
        }

        return true;
    }

    private function isValidISBN($isbn) {
        // Remove hyphens and spaces
        $isbn = str_replace(['-', ' '], '', $isbn);
        
        // Check ISBN-13
        if (strlen($isbn) === 13) {
            if (!ctype_digit($isbn)) {
                return false;
            }
            
            $sum = 0;
            for ($i = 0; $i < 12; $i++) {
                $sum += ($i % 2 === 0) ? $isbn[$i] : $isbn[$i] * 3;
            }
            
            $check = (10 - ($sum % 10)) % 10;
            return $check == $isbn[12];
        }
        
        // Check ISBN-10
        if (strlen($isbn) === 10) {
            if (!ctype_digit(substr($isbn, 0, 9))) {
                return false;
            }
            
            $sum = 0;
            for ($i = 0; $i < 9; $i++) {
                $sum += ($isbn[$i] * (10 - $i));
            }
            
            $check = (11 - ($sum % 11)) % 11;
            $lastChar = strtoupper($isbn[9]);
            
            if ($check === 10) {
                return $lastChar === 'X';
            }
            
            return $check == $lastChar;
        }
        
        return false;
    }

    public function addCustomRule($name, $callback) {
        $this->customRules[$name] = $callback;
    }

    public function addError($field, $message) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    public function getErrors() {
        return $this->errors;
    }

    public function getFirstError() {
        foreach ($this->errors as $fieldErrors) {
            if (!empty($fieldErrors)) {
                return reset($fieldErrors);
            }
        }
        return null;
    }

    public function hasErrors() {
        return !empty($this->errors);
    }

    private function __clone() {}
    private function __wakeup() {}
}
?>
