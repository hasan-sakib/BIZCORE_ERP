<?php

declare(strict_types=1);

namespace App\Validation;

use App\Core\Database;

class Validator
{
    private array $errors = [];

    public function __construct(private readonly Database $db) {}

    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $rule) {
            $ruleList = is_array($rule) ? $rule : explode('|', (string) $rule);
            foreach ($ruleList as $r) {
                $this->applyRule($data, $field, (string) $r);
            }
        }

        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function first(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    private function applyRule(array $data, string $field, string $rule): void
    {
        [$ruleName, $param] = array_pad(explode(':', $rule, 2), 2, null);
        $value = $data[$field] ?? null;

        switch ($ruleName) {
            case 'required':
                if ($value === null || $value === '') {
                    $this->errors[$field][] = "The {$field} field is required.";
                }
                break;
            case 'email':
                if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field][] = "The {$field} must be a valid email address.";
                }
                break;
            case 'min':
                if ($value !== null && mb_strlen((string) $value) < (int) $param) {
                    $this->errors[$field][] = "The {$field} must be at least {$param} characters.";
                }
                break;
            case 'max':
                if ($value !== null && mb_strlen((string) $value) > (int) $param) {
                    $this->errors[$field][] = "The {$field} may not exceed {$param} characters.";
                }
                break;
            case 'numeric':
                if ($value !== null && $value !== '' && !is_numeric($value)) {
                    $this->errors[$field][] = "The {$field} must be a number.";
                }
                break;
            case 'integer':
                if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->errors[$field][] = "The {$field} must be an integer.";
                }
                break;
        }
    }
}
