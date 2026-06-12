<?php

declare(strict_types=1);

namespace App\Entities;

use JsonSerializable;

class Employee implements JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly string $employeeNumber,
        public readonly ?int $userId,
        public readonly int $branchId,
        public readonly int $departmentId,
        public readonly int $designationId,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly ?string $phone,
        public readonly ?string $dateOfBirth,
        public readonly ?string $gender,
        public readonly ?string $bloodGroup,
        public readonly ?string $nidNumber,
        public readonly ?string $religion,
        public readonly ?string $maritalStatus,
        public readonly array $address,
        public readonly array $emergencyContact,
        public readonly ?string $bankDetails,
        public readonly string $joinDate,
        public readonly ?string $confirmationDate,
        public readonly string $status,
        public readonly ?string $avatar,
        public readonly array $documents,
        public readonly ?int $createdBy,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
        public readonly ?string $departmentName = null,
        public readonly ?string $designationName = null,
        public readonly ?string $branchName = null,
    ) {}

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getAge(): ?int
    {
        if (!$this->dateOfBirth) return null;
        return (int)(new \DateTime($this->dateOfBirth))->diff(new \DateTime())->y;
    }

    public function getYearsOfService(): float
    {
        return round(
            (new \DateTime($this->joinDate))->diff(new \DateTime())->days / 365.25,
            1
        );
    }

    public static function fromArray(array $data): static
    {
        return new static(
            id: (int)$data['id'],
            employeeNumber: $data['employee_number'],
            userId: isset($data['user_id']) ? (int)$data['user_id'] : null,
            branchId: (int)$data['branch_id'],
            departmentId: (int)$data['department_id'],
            designationId: (int)$data['designation_id'],
            firstName: $data['first_name'],
            lastName: $data['last_name'],
            email: $data['email'],
            phone: $data['phone'] ?? null,
            dateOfBirth: $data['date_of_birth'] ?? null,
            gender: $data['gender'] ?? null,
            bloodGroup: $data['blood_group'] ?? null,
            nidNumber: $data['nid_number'] ?? null,
            religion: $data['religion'] ?? null,
            maritalStatus: $data['marital_status'] ?? null,
            address: is_string($data['address'] ?? null) ? (json_decode($data['address'], true) ?? []) : ($data['address'] ?? []),
            emergencyContact: is_string($data['emergency_contact'] ?? null) ? (json_decode($data['emergency_contact'], true) ?? []) : ($data['emergency_contact'] ?? []),
            bankDetails: $data['bank_details'] ?? null,
            joinDate: $data['join_date'],
            confirmationDate: $data['confirmation_date'] ?? null,
            status: $data['status'],
            avatar: $data['avatar'] ?? null,
            documents: is_string($data['documents'] ?? null) ? (json_decode($data['documents'], true) ?? []) : ($data['documents'] ?? []),
            createdBy: isset($data['created_by']) ? (int)$data['created_by'] : null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            departmentName: $data['department_name'] ?? null,
            designationName: $data['designation_name'] ?? null,
            branchName: $data['branch_name'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id'                => $this->id,
            'employee_number'   => $this->employeeNumber,
            'user_id'           => $this->userId,
            'branch_id'         => $this->branchId,
            'department_id'     => $this->departmentId,
            'designation_id'    => $this->designationId,
            'first_name'        => $this->firstName,
            'last_name'         => $this->lastName,
            'full_name'         => $this->getFullName(),
            'email'             => $this->email,
            'phone'             => $this->phone,
            'date_of_birth'     => $this->dateOfBirth,
            'gender'            => $this->gender,
            'blood_group'       => $this->bloodGroup,
            'nid_number'        => $this->nidNumber,
            'religion'          => $this->religion,
            'marital_status'    => $this->maritalStatus,
            'address'           => $this->address,
            'emergency_contact' => $this->emergencyContact,
            'join_date'         => $this->joinDate,
            'confirmation_date' => $this->confirmationDate,
            'status'            => $this->status,
            'avatar'            => $this->avatar,
            'documents'         => $this->documents,
            'department_name'   => $this->departmentName,
            'designation_name'  => $this->designationName,
            'branch_name'       => $this->branchName,
            'created_at'        => $this->createdAt,
            'updated_at'        => $this->updatedAt,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
