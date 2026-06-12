<?php

declare(strict_types=1);

namespace App\Entities;

use JsonSerializable;

class Invoice implements JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly int $branchId,
        public readonly int $customerId,
        public readonly ?int $salesOrderId,
        public readonly string $invoiceNumber,
        public readonly string $invoiceDate,
        public readonly string $dueDate,
        public readonly int $warehouseId,
        public readonly float $subtotal,
        public readonly float $vatAmount,
        public readonly float $discountAmount,
        public readonly float $totalAmount,
        public readonly float $paidAmount,
        public readonly float $balance,
        public readonly string $status,
        public readonly ?string $notes,
        public readonly ?string $terms,
        public readonly int $createdBy,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
        public readonly array $items = [],
        public readonly ?string $customerName = null,
        public readonly ?string $customerPhone = null,
    ) {}

    public function isOverdue(): bool
    {
        return $this->status !== 'paid'
            && $this->status !== 'cancelled'
            && $this->dueDate < date('Y-m-d');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function getDaysOverdue(): int
    {
        if (!$this->isOverdue()) return 0;
        return (int)(new \DateTime($this->dueDate))->diff(new \DateTime())->days;
    }

    public static function fromArray(array $data): static
    {
        return new static(
            id: (int)$data['id'],
            branchId: (int)$data['branch_id'],
            customerId: (int)$data['customer_id'],
            salesOrderId: isset($data['sales_order_id']) ? (int)$data['sales_order_id'] : null,
            invoiceNumber: $data['invoice_number'],
            invoiceDate: $data['invoice_date'],
            dueDate: $data['due_date'],
            warehouseId: (int)$data['warehouse_id'],
            subtotal: (float)$data['subtotal'],
            vatAmount: (float)$data['vat_amount'],
            discountAmount: (float)$data['discount_amount'],
            totalAmount: (float)$data['total_amount'],
            paidAmount: (float)$data['paid_amount'],
            balance: (float)$data['balance'],
            status: $data['status'],
            notes: $data['notes'] ?? null,
            terms: $data['terms'] ?? null,
            createdBy: (int)$data['created_by'],
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            items: $data['items'] ?? [],
            customerName: $data['customer_name'] ?? null,
            customerPhone: $data['customer_phone'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id'              => $this->id,
            'branch_id'       => $this->branchId,
            'customer_id'     => $this->customerId,
            'sales_order_id'  => $this->salesOrderId,
            'invoice_number'  => $this->invoiceNumber,
            'invoice_date'    => $this->invoiceDate,
            'due_date'        => $this->dueDate,
            'warehouse_id'    => $this->warehouseId,
            'subtotal'        => $this->subtotal,
            'vat_amount'      => $this->vatAmount,
            'discount_amount' => $this->discountAmount,
            'total_amount'    => $this->totalAmount,
            'paid_amount'     => $this->paidAmount,
            'balance'         => $this->balance,
            'status'          => $this->status,
            'notes'           => $this->notes,
            'terms'           => $this->terms,
            'items'           => $this->items,
            'customer_name'   => $this->customerName,
            'is_overdue'      => $this->isOverdue(),
            'days_overdue'    => $this->getDaysOverdue(),
            'created_at'      => $this->createdAt,
            'updated_at'      => $this->updatedAt,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
