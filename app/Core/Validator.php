<?php

declare(strict_types=1);

namespace App\Core;

class Validator
{
    private array $errors = [];
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $ruleString) {
            $fieldRules = explode('|', $ruleString);
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                $this->applyRule($field, $value, $rule, $data);
            }
        }

        return empty($this->errors);
    }

    private function applyRule(string $field, mixed $value, string $rule, array $data): void
    {
        [$ruleName, $param] = array_pad(explode(':', $rule, 2), 2, null);

        match ($ruleName) {
            'required'   => $this->validateRequired($field, $value),
            'email'      => $this->validateEmail($field, $value),
            'min'        => $this->validateMin($field, $value, (int)$param),
            'max'        => $this->validateMax($field, $value, (int)$param),
            'numeric'    => $this->validateNumeric($field, $value),
            'integer'    => $this->validateInteger($field, $value),
            'string'     => $this->validateString($field, $value),
            'boolean'    => $this->validateBoolean($field, $value),
            'url'        => $this->validateUrl($field, $value),
            'ip'         => $this->validateIp($field, $value),
            'date'       => $this->validateDate($field, $value),
            'in'         => $this->validateIn($field, $value, $param),
            'not_in'     => $this->validateNotIn($field, $value, $param),
            'unique'     => $this->validateUnique($field, $value, $param, $data),
            'exists'     => $this->validateExists($field, $value, $param),
            'confirmed'  => $this->validateConfirmed($field, $value, $data),
            'same'       => $this->validateSame($field, $value, $param, $data),
            'regex'      => $this->validateRegex($field, $value, $param),
            'nullable'   => null,
            'sometimes'  => null,
            default      => null,
        };
    }

    private function validateRequired(string $field, mixed $value): void
    {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            $this->addError($field, "The {$field} field is required.");
        }
    }

    private function validateEmail(string $field, mixed $value): void
    {
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "The {$field} must be a valid email address.");
        }
    }

    private function validateMin(string $field, mixed $value, int $min): void
    {
        if ($value === null || $value === '') return;
        if (is_string($value) && mb_strlen($value) < $min) {
            $this->addError($field, "The {$field} must be at least {$min} characters.");
        } elseif (is_numeric($value) && (float)$value < $min) {
            $this->addError($field, "The {$field} must be at least {$min}.");
        }
    }

    private function validateMax(string $field, mixed $value, int $max): void
    {
        if ($value === null || $value === '') return;
        if (is_string($value) && mb_strlen($value) > $max) {
            $this->addError($field, "The {$field} may not be greater than {$max} characters.");
        } elseif (is_numeric($value) && (float)$value > $max) {
            $this->addError($field, "The {$field} may not be greater than {$max}.");
        }
    }

    private function validateNumeric(string $field, mixed $value): void
    {
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->addError($field, "The {$field} must be a number.");
        }
    }

    private function validateInteger(string $field, mixed $value): void
    {
        if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->addError($field, "The {$field} must be an integer.");
        }
    }

    private function validateString(string $field, mixed $value): void
    {
        if ($value !== null && !is_string($value)) {
            $this->addError($field, "The {$field} must be a string.");
        }
    }

    private function validateBoolean(string $field, mixed $value): void
    {
        if ($value !== null && !in_array($value, [true, false, 1, 0, '1', '0', 'true', 'false'], true)) {
            $this->addError($field, "The {$field} must be true or false.");
        }
    }

    private function validateUrl(string $field, mixed $value): void
    {
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->addError($field, "The {$field} must be a valid URL.");
        }
    }

    private function validateIp(string $field, mixed $value): void
    {
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_IP)) {
            $this->addError($field, "The {$field} must be a valid IP address.");
        }
    }

    private function validateDate(string $field, mixed $value): void
    {
        if ($value !== null && $value !== '') {
            $d = \DateTime::createFromFormat('Y-m-d', $value);
            if (!$d || $d->format('Y-m-d') !== $value) {
                $this->addError($field, "The {$field} must be a valid date (Y-m-d).");
            }
        }
    }

    private function validateIn(string $field, mixed $value, ?string $param): void
    {
        if ($value !== null && $value !== '' && $param !== null) {
            $allowed = explode(',', $param);
            if (!in_array($value, $allowed, true)) {
                $this->addError($field, "The selected {$field} is invalid.");
            }
        }
    }

    private function validateNotIn(string $field, mixed $value, ?string $param): void
    {
        if ($value !== null && $value !== '' && $param !== null) {
            $disallowed = explode(',', $param);
            if (in_array($value, $disallowed, true)) {
                $this->addError($field, "The selected {$field} is invalid.");
            }
        }
    }

    private function validateUnique(string $field, mixed $value, ?string $param, array $data): void
    {
        if ($value === null || $value === '' || $param === null) return;
        [$table, $column, $exceptId] = array_pad(explode(',', $param), 3, null);
        $column = $column ?? $field;

        $query = $this->db->table($table)->where($column, $value);
        if ($exceptId !== null && isset($data['id'])) {
            $query = $query->where('id', '!=', $data['id']);
        }

        if ($query->count() > 0) {
            $this->addError($field, "The {$field} has already been taken.");
        }
    }

    private function validateExists(string $field, mixed $value, ?string $param): void
    {
        if ($value === null || $value === '' || $param === null) return;
        [$table, $column] = array_pad(explode(',', $param), 2, null);
        $column = $column ?? 'id';

        if ($this->db->table($table)->where($column, $value)->count() === 0) {
            $this->addError($field, "The selected {$field} is invalid.");
        }
    }

    private function validateConfirmed(string $field, mixed $value, array $data): void
    {
        $confirmField = $field . '_confirmation';
        if ($value !== ($data[$confirmField] ?? null)) {
            $this->addError($field, "The {$field} confirmation does not match.");
        }
    }

    private function validateSame(string $field, mixed $value, ?string $param, array $data): void
    {
        if ($param !== null && $value !== ($data[$param] ?? null)) {
            $this->addError($field, "The {$field} and {$param} must match.");
        }
    }

    private function validateRegex(string $field, mixed $value, ?string $param): void
    {
        if ($value !== null && $value !== '' && $param !== null && !preg_match($param, (string)$value)) {
            $this->addError($field, "The {$field} format is invalid.");
        }
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFirstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
}
