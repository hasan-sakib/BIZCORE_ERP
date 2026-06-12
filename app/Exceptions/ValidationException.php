<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when input data fails validation rules.
 */
final class ValidationException extends RuntimeException
{
    /**
     * @param  array<string, string[]> $errors  Field name → list of error messages.
     */
    public function __construct(
        private readonly array $errors,
        string $message = 'The given data was invalid.',
        int $code = 422,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * All validation errors grouped by field name.
     *
     * @return array<string, string[]>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Returns a flat list of all error messages across all fields.
     *
     * @return string[]
     */
    public function getAllMessages(): array
    {
        return array_merge(...array_values($this->errors));
    }

    /**
     * Returns true when the given field has at least one error.
     */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]) && count($this->errors[$field]) > 0;
    }

    /**
     * Returns the first error message for the given field, or null.
     */
    public function firstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }
}
