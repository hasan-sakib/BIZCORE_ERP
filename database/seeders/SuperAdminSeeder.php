<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $role   = Role::where('slug', 'super_admin')->firstOrFail();
        $branch = Branch::where('is_head', true)->firstOrFail();

        User::updateOrCreate(
            ['email' => 'admin@bizcore.local'],
            [
                'branch_id'         => $branch->id,
                'role_id'           => $role->id,
                'name'              => 'Super Admin',
                'email'             => 'admin@bizcore.local',
                'password'          => Hash::make('Admin@1234'),
                'status' => UserStatus::Active,
            ]
        );
    }
}
