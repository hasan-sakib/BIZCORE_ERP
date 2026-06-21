<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        Branch::updateOrCreate(
            ['name' => 'Head Office'],
            [
                'name'    => 'Head Office',
                'code'    => 'HO',
                'address' => 'Dhaka, Bangladesh',
                'phone'   => null,
                'email'   => null,
                'status'  => 'active',
                'is_head' => true,
            ]
        );
    }
}
