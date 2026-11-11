<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Everything for a dev database (php artisan migrate:fresh --seed): the accounts to sign in as, then circles in every
 * state. Model events stay on, so the audit log records the seeded circles and memberships like real ones.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CircleSeeder::class,
        ]);
    }
}
