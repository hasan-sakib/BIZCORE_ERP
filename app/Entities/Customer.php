<?php

declare(strict_types=1);

namespace App\Entities;

class Customer
{
    public function __construct(
        public readonly int     $id,
        public readonly int     $branchId,
        public readonly string  $customerCode,
        public readonly string  $name,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly string  $type,
        public readonly ?string $billingAddress,
        public readonly ?string $shippingAddress,
        public readonly float   $creditLimit,
        public readonly int     $creditPeriod,
        public readonly float   $balance,
        public readonly ?string $vatNumber,
        public readonly bool    $isActive,
        public readonly ?string $createdAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id:              (int)$data['id'],
            branchId:        (int)$data['branch_id'],
            customerCode:    $data['customer_code'],
            name:            $data['name'],
            email:           $data['email'] ?? null,
            phone:           $data['phone'] ?? null,
            type:            $data['type'] ?? 'individual',
            billingAddress:  $data['billing_address'] ?? null,
            shippingAddress: $data['shipping_address'] ?? null,
            creditLimit:     (float)($data['credit_limit'] ?? 0),
            creditPeriod:    (int)($data['credit_period'] ?? 30),
            balance:         (float)($data['balance'] ?? 0),
            vatNumber:       $data['vat_number'] ?? null,
            isActive:        (bool)($data['is_active'] ?? true),
            createdAt:       $data['created_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id'               => $this->id,
            'branch_id'        => $this->branchId,
            'customer_code'    => $this->customerCode,
            'name'             => $this->name,
            'email'            => $this->email,
            'phone'            => $this->phone,
            'type'             => $this->type,
            'billing_address'  => $this->billingAddress,
            'shipping_address' => $this->shippingAddress,
            'credit_limit'     => $this->creditLimit,
            'credit_period'    => $this->creditPeriod,
            'balance'          => $this->balance,
            'vat_number'       => $this->vatNumber,
            'is_active'        => $this->isActive,
            'created_at'       => $this->createdAt,
        ];
    }

    public function isOverCreditLimit(float $additionalAmount = 0): bool
    {
        return $this->creditLimit > 0
            && ($this->balance + $additionalAmount) > $this->creditLimit;
    }

    public function getAvailableCredit(): float
    {
        return max(0, $this->creditLimit - $this->balance);
    }
}
