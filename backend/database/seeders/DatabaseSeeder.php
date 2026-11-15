<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Guard against running development seeders in production
        if (App::environment(['local', 'testing', 'development'])) {
            $this->call([
                SuperAdminEnterpriseSeeder::class,
                TenantAdminEnterpriseSeeder::class,
            ]);
        }
    }
}
