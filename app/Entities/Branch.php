<?php

declare(strict_types=1);

namespace App\Entities;

use DateTime;
use JsonSerializable;

/**
 * Branch Entity
 *
 * Immutable value object representing a company branch / office.
 * All mutation produces a new instance via `with*()` helpers.
 */
final class Branch implements JsonSerializable
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $name,
        public readonly string  $code,
        public readonly array   $address,
        public readonly ?string $phone,
        public readonly ?string $email,
        public readonly ?int    $managerId,
        public readonly string  $status,
        public readonly array   $settings,
        public readonly bool    $isHead,
        public readonly DateTime $createdAt,
        public readonly DateTime $updatedAt,
    ) {}

    // -------------------------------------------------------------------------
    // Factory
    // -------------------------------------------------------------------------

    /**
     * Construct a Branch from a raw associative array (e.g. PDO fetch result).
     *
     * The `address` and `settings` columns are stored as JSON strings in the
     * database and are decoded here into plain PHP arrays.
     */
    public static function fromArray(array $data): self
    {
        $address = isset($data['address']) && is_string($data['address'])
            ? (array) json_decode($data['address'], true)
            : (array) ($data['address'] ?? []);

        $settings = isset($data['settings']) && is_string($data['settings'])
            ? (array) json_decode($data['settings'], true)
            : (array) ($data['settings'] ?? []);

        return new self(
            id:        (int) $data['id'],
            name:      (string) $data['name'],
            code:      (string) $data['code'],
            address:   $address,
            phone:     isset($data['phone']) ? (string) $data['phone'] : null,
            email:     isset($data['email']) ? (string) $data['email'] : null,
            managerId: isset($data['manager_id']) ? (int) $data['manager_id'] : null,
            status:    (string) ($data['status'] ?? 'active'),
            settings:  $settings,
            isHead:    (bool) ($data['is_head'] ?? false),
            createdAt: new DateTime($data['created_at']),
            updatedAt: new DateTime($data['updated_at']),
        );
    }

    // -------------------------------------------------------------------------
    // Domain helpers
    // -------------------------------------------------------------------------

    /**
     * Returns true when the branch is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Returns true when this branch is the head office.
     */
    public function isHeadOffice(): bool
    {
        return $this->isHead;
    }

    /**
     * Retrieve a single setting value with an optional default.
     */
    public function setting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * Returns a formatted single-line address string.
     */
    public function formattedAddress(): string
    {
        $parts = array_filter([
            $this->address['line1'] ?? ($this->address['address'] ?? ''),
            $this->address['city']  ?? '',
            $this->address['state'] ?? '',
            $this->address['country'] ?? '',
        ]);

        return implode(', ', $parts);
    }

    // -------------------------------------------------------------------------
    // Immutable mutation helpers
    // -------------------------------------------------------------------------

    /**
     * Return a copy of the entity with the given status.
     */
    public function withStatus(string $status): self
    {
        return new self(
            id:        $this->id,
            name:      $this->name,
            code:      $this->code,
            address:   $this->address,
            phone:     $this->phone,
            email:     $this->email,
            managerId: $this->managerId,
            status:    $status,
            settings:  $this->settings,
            isHead:    $this->isHead,
            createdAt: $this->createdAt,
            updatedAt: new DateTime(),
        );
    }

    // -------------------------------------------------------------------------
    // Serialisation
    // -------------------------------------------------------------------------

    /**
     * Convert entity to a plain associative array.
     */
    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'code'       => $this->code,
            'address'    => $this->address,
            'phone'      => $this->phone,
            'email'      => $this->email,
            'manager_id' => $this->managerId,
            'status'     => $this->status,
            'settings'   => $this->settings,
            'is_head'    => $this->isHead,
            'is_active'  => $this->isActive(),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
