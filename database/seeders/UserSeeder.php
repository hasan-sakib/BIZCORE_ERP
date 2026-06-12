<?php

declare(strict_types=1);

namespace Database\Seeders;

use PDO;
use RuntimeException;

/**
 * UserSeeder
 *
 * Creates the default system users.  All passwords use bcrypt cost-12 and
 * satisfy the application's password policy (upper, lower, digit, special).
 *
 * Must run after BranchSeeder and RoleSeeder.
 */
final class UserSeeder
{
    /** bcrypt cost factor — keep in sync with AuthService::BCRYPT_COST. */
    private const BCRYPT_COST = 12;

    /** Plain-text password used for all seed accounts. Change after first login. */
    private const DEFAULT_PASSWORD = 'Admin@1234';

    public function __construct(
        private readonly PDO $pdo,
    ) {}

    public function run(): void
    {
        $branchIds = $this->loadBranchIds();
        $roleIds   = $this->loadRoleIds();

        $hq = $branchIds['HQ'] ?? throw new RuntimeException('Branch HQ not found. Run BranchSeeder first.');

        $passwordHash = password_hash(self::DEFAULT_PASSWORD, PASSWORD_BCRYPT, ['cost' => self::BCRYPT_COST]);

        if ($passwordHash === false) {
            throw new RuntimeException('Password hashing failed.');
        }

        $now = date('Y-m-d H:i:s');

        $users = [
            [
                'branch_id'             => $hq,
                'role_id'               => $roleIds['super_admin']    ?? throw new RuntimeException('Role super_admin missing.'),
                'name'                  => 'Super Admin',
                'email'                 => 'super@bizcore.local',
                'phone'                 => '+8801700000010',
                'password'              => $passwordHash,
                'status'                => 'active',
                'failed_login_attempts' => 0,
                'created_at'            => $now,
                'updated_at'            => $now,
            ],
            [
                'branch_id'             => $hq,
                'role_id'               => $roleIds['branch_manager']  ?? throw new RuntimeException('Role branch_manager missing.'),
                'name'                  => 'Branch Manager',
                'email'                 => 'manager@bizcore.local',
                'phone'                 => '+8801700000011',
                'password'              => $passwordHash,
                'status'                => 'active',
                'failed_login_attempts' => 0,
                'created_at'            => $now,
                'updated_at'            => $now,
            ],
            [
                'branch_id'             => $hq,
                'role_id'               => $roleIds['accountant']       ?? throw new RuntimeException('Role accountant missing.'),
                'name'                  => 'Chief Accountant',
                'email'                 => 'accountant@bizcore.local',
                'phone'                 => '+8801700000012',
                'password'              => $passwordHash,
                'status'                => 'active',
                'failed_login_attempts' => 0,
                'created_at'            => $now,
                'updated_at'            => $now,
            ],
            [
                'branch_id'             => $hq,
                'role_id'               => $roleIds['hr_officer']       ?? throw new RuntimeException('Role hr_officer missing.'),
                'name'                  => 'HR Officer',
                'email'                 => 'hr@bizcore.local',
                'phone'                 => '+8801700000013',
                'password'              => $passwordHash,
                'status'                => 'active',
                'failed_login_attempts' => 0,
                'created_at'            => $now,
                'updated_at'            => $now,
            ],
            [
                'branch_id'             => $hq,
                'role_id'               => $roleIds['sales_executive']  ?? throw new RuntimeException('Role sales_executive missing.'),
                'name'                  => 'Sales Executive',
                'email'                 => 'sales@bizcore.local',
                'phone'                 => '+8801700000014',
                'password'              => $passwordHash,
                'status'                => 'active',
                'failed_login_attempts' => 0,
                'created_at'            => $now,
                'updated_at'            => $now,
            ],
        ];

        $sql = <<<'SQL'
            INSERT INTO users
                (branch_id, role_id, name, email, phone, password, status,
                 failed_login_attempts, created_at, updated_at)
            VALUES
                (:branch_id, :role_id, :name, :email, :phone, :password, :status,
                 :failed_login_attempts, :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE
                name                  = VALUES(name),
                phone                 = VALUES(phone),
                role_id               = VALUES(role_id),
                branch_id             = VALUES(branch_id),
                status                = VALUES(status),
                updated_at            = VALUES(updated_at)
        SQL;

        $stmt = $this->pdo->prepare($sql);

        foreach ($users as $user) {
            $stmt->execute($user);
        }
    }

    // -------------------------------------------------------------------------
    // Lookup helpers
    // -------------------------------------------------------------------------

    /** @return array<string, int>  code → id */
    private function loadBranchIds(): array
    {
        $rows = $this->pdo->query('SELECT id, code FROM branches')->fetchAll(PDO::FETCH_ASSOC);
        $map  = [];
        foreach ($rows as $row) {
            $map[$row['code']] = (int) $row['id'];
        }
        return $map;
    }

    /** @return array<string, int>  slug → id */
    private function loadRoleIds(): array
    {
        $rows = $this->pdo->query('SELECT id, slug FROM roles')->fetchAll(PDO::FETCH_ASSOC);
        $map  = [];
        foreach ($rows as $row) {
            $map[$row['slug']] = (int) $row['id'];
        }
        return $map;
    }
}
