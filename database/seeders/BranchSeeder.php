<?php

declare(strict_types=1);

namespace Database\Seeders;

use PDO;

/**
 * BranchSeeder
 *
 * Seeds the `branches` table with the Head Office and the first regional
 * branch. Must run before any seeder that creates users or employees, as
 * those records carry a `branch_id` foreign key.
 */
final class BranchSeeder
{
    public function __construct(
        private readonly PDO $pdo,
    ) {}

    public function run(): void
    {
        $branches = [
            [
                'code'       => 'HQ',
                'name'       => 'BizCore HQ',
                'address'    => json_encode(['street' => 'House 12, Road 5', 'area' => 'Gulshan-1', 'city' => 'Dhaka', 'country' => 'Bangladesh']),
                'phone'      => '+8801700000001',
                'email'      => 'hq@bizcore.local',
                'status'     => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'code'       => 'CTG',
                'name'       => 'BizCore Chittagong',
                'address'    => json_encode(['street' => 'Plot 7, Agrabad Commercial Area', 'area' => 'Agrabad', 'city' => 'Chittagong', 'country' => 'Bangladesh']),
                'phone'      => '+8801700000002',
                'email'      => 'chittagong@bizcore.local',
                'status'     => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $sql = <<<'SQL'
            INSERT INTO branches
                (code, name, address, phone, email, status, created_at, updated_at)
            VALUES
                (:code, :name, :address, :phone, :email, :status, :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE
                name       = VALUES(name),
                address    = VALUES(address),
                updated_at = VALUES(updated_at)
        SQL;

        $stmt = $this->pdo->prepare($sql);

        foreach ($branches as $branch) {
            $stmt->execute($branch);
        }
    }
}
