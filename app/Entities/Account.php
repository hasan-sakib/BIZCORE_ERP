<?php

declare(strict_types=1);

namespace App\Entities;

class Account
{
    public function __construct(
        public readonly int     $id,
        public readonly ?int    $parentId,
        public readonly string  $code,
        public readonly string  $name,
        public readonly string  $type,
        public readonly string  $normalBalance,
        public readonly float   $balance,
        public readonly bool    $isSystem,
        public readonly bool    $isActive,
        public readonly ?string $description,
        public readonly ?string $parentName,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id:            (int)$data['id'],
            parentId:      isset($data['parent_id']) ? (int)$data['parent_id'] : null,
            code:          $data['code'],
            name:          $data['name'],
            type:          $data['type'],
            normalBalance: $data['normal_balance'],
            balance:       (float)($data['balance'] ?? 0),
            isSystem:      (bool)($data['is_system'] ?? false),
            isActive:      (bool)($data['is_active'] ?? true),
            description:   $data['description'] ?? null,
            parentName:    $data['parent_name'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id'             => $this->id,
            'parent_id'      => $this->parentId,
            'code'           => $this->code,
            'name'           => $this->name,
            'type'           => $this->type,
            'normal_balance' => $this->normalBalance,
            'balance'        => $this->balance,
            'is_system'      => $this->isSystem,
            'is_active'      => $this->isActive,
            'description'    => $this->description,
            'parent_name'    => $this->parentName,
        ];
    }

    public function isDebitNormal(): bool
    {
        return $this->normalBalance === 'debit';
    }

    public function isCreditNormal(): bool
    {
        return $this->normalBalance === 'credit';
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'asset'     => 'Asset',
            'liability' => 'Liability',
            'equity'    => 'Equity',
            'revenue'   => 'Revenue',
            'expense'   => 'Expense',
            default     => ucfirst($this->type),
        };
    }
}
