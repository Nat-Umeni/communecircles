<?php

namespace Database\Seeders;

use App\Enums\User\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * The dev accounts to sign in as (each with the password "password"), named for what they are, and plenty of people
 * to fill circles.
 */
class UserSeeder extends Seeder
{
    public const string SUPER_ADMIN = 'superadmin@example.com';

    public const string STAFF_ADMIN = 'staffadmin@example.com';

    /** Owns the big circle (CircleSeeder::BIG_CIRCLE). */
    public const string CIRCLE_OWNER = 'circleowner@example.com';

    /** An admin of the big circle. */
    public const string CIRCLE_ADMIN = 'circleadmin@example.com';

    /** A plain member of the big circle, with a request and invites for others. */
    public const string CIRCLE_MEMBER = 'circlemember@example.com';

    /** Hasn't confirmed their email yet. */
    public const string UNCONFIRMED = 'unconfirmed@example.com';

    /** How many other people there are to fill circles. */
    public const int PEOPLE = 120;

    public function run(): void
    {
        // A month old, so they're past the wait new accounts have before starting a circle.
        $joined = now()->subMonth();

        User::factory()->create(['first_name' => 'Super', 'last_name' => 'Admin', 'email' => self::SUPER_ADMIN, 'role' => Role::SuperAdmin, 'created_at' => $joined]);
        User::factory()->create(['first_name' => 'Staff', 'last_name' => 'Admin', 'email' => self::STAFF_ADMIN, 'role' => Role::Admin, 'created_at' => $joined]);
        User::factory()->create(['first_name' => 'Circle', 'last_name' => 'Owner', 'email' => self::CIRCLE_OWNER, 'created_at' => $joined]);
        User::factory()->create(['first_name' => 'Circle', 'last_name' => 'Admin', 'email' => self::CIRCLE_ADMIN, 'created_at' => $joined]);
        User::factory()->create(['first_name' => 'Circle', 'last_name' => 'Member', 'email' => self::CIRCLE_MEMBER, 'created_at' => $joined]);
        User::factory()->unverified()->create(['first_name' => 'Unconfirmed', 'last_name' => 'Member', 'email' => self::UNCONFIRMED, 'created_at' => $joined]);

        User::factory()->count(self::PEOPLE)->create(['created_at' => $joined]);
    }
}
