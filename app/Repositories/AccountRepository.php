<?php

declare(strict_types=1);

namespace App\Repositories;

final class AccountRepository extends BaseRepository
{
    public function all(string $search = '', string $type = ''): array
    {
        $where  = ['1=1'];
        $params = [];

        if ($search !== '') {
            $where[]          = '(code LIKE :s OR name LIKE :s)';
            $params[':s']     = '%' . $search . '%';
        }
        if ($type !== '') {
            $where[]          = 'type = :type';
            $params[':type']  = $type;
        }

        $w = implode(' AND ', $where);
        return $this->fetchAll("SELECT * FROM accounts WHERE {$w} ORDER BY code ASC", $params);
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM accounts WHERE id = :id LIMIT 1', [':id' => $id]);
    }

    public function create(array $data): int
    {
        $this->modify(
            'INSERT INTO accounts (parent_id, code, name, type, subtype, normal_balance, description, is_active)
             VALUES (:parent_id, :code, :name, :type, :subtype, :normal_balance, :description, 1)',
            [
                ':parent_id'     => $data['parent_id'] ?: null,
                ':code'          => $data['code'],
                ':name'          => $data['name'],
                ':type'          => $data['type'],
                ':subtype'       => $data['subtype'] ?? null,
                ':normal_balance'=> $data['normal_balance'] ?? 'debit',
                ':description'   => $data['description'] ?? null,
            ],
        );
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $this->modify(
            'UPDATE accounts SET parent_id=:parent_id, code=:code, name=:name, type=:type,
             subtype=:subtype, normal_balance=:normal_balance, description=:description, is_active=:is_active
             WHERE id=:id',
            [
                ':parent_id'     => $data['parent_id'] ?: null,
                ':code'          => $data['code'],
                ':name'          => $data['name'],
                ':type'          => $data['type'],
                ':subtype'       => $data['subtype'] ?? null,
                ':normal_balance'=> $data['normal_balance'] ?? 'debit',
                ':description'   => $data['description'] ?? null,
                ':is_active'     => (int) ($data['is_active'] ?? 1),
                ':id'            => $id,
            ],
        );
    }

    public function delete(int $id): void
    {
        $this->modify('DELETE FROM accounts WHERE id = :id AND is_system = 0', [':id' => $id]);
    }
}
