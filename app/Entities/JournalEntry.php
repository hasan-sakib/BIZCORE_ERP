<?php

declare(strict_types=1);

namespace App\Entities;

use JsonSerializable;

class JournalEntry implements JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly int $branchId,
        public readonly string $entryNumber,
        public readonly string $date,
        public readonly ?string $referenceType,
        public readonly ?int $referenceId,
        public readonly ?string $description,
        public readonly float $totalDebit,
        public readonly float $totalCredit,
        public readonly string $status,
        public readonly ?int $postedBy,
        public readonly ?string $postedAt,
        public readonly int $createdBy,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
        public readonly array $lines = [],
    ) {}

    public function isBalanced(): bool
    {
        return abs($this->totalDebit - $this->totalCredit) < 0.01;
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public static function fromArray(array $data): static
    {
        return new static(
            id: (int)$data['id'],
            branchId: (int)$data['branch_id'],
            entryNumber: $data['entry_number'],
            date: $data['date'],
            referenceType: $data['reference_type'] ?? null,
            referenceId: isset($data['reference_id']) ? (int)$data['reference_id'] : null,
            description: $data['description'] ?? null,
            totalDebit: (float)$data['total_debit'],
            totalCredit: (float)$data['total_credit'],
            status: $data['status'],
            postedBy: isset($data['posted_by']) ? (int)$data['posted_by'] : null,
            postedAt: $data['posted_at'] ?? null,
            createdBy: (int)$data['created_by'],
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            lines: $data['lines'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'id'             => $this->id,
            'branch_id'      => $this->branchId,
            'entry_number'   => $this->entryNumber,
            'date'           => $this->date,
            'reference_type' => $this->referenceType,
            'reference_id'   => $this->referenceId,
            'description'    => $this->description,
            'total_debit'    => $this->totalDebit,
            'total_credit'   => $this->totalCredit,
            'status'         => $this->status,
            'posted_by'      => $this->postedBy,
            'posted_at'      => $this->postedAt,
            'is_balanced'    => $this->isBalanced(),
            'lines'          => $this->lines,
            'created_at'     => $this->createdAt,
            'updated_at'     => $this->updatedAt,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
