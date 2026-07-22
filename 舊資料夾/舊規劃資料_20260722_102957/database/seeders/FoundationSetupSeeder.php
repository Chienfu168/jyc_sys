<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class FoundationSetupSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('foundation.roles', []) as $roleName) {
            Role::findOrCreate($roleName);
        }
    }
}

