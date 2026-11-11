<?php

namespace Tests\Feature\Database\Seeders;

use App\Enums\User\Role;
use App\Models\Circle;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[DataProvider('accounts')]
    public function test_creates_each_dev_account_with_its_role_and_the_password_password(string $email, string $name, Role $role, bool $confirmed): void
    {
        $this->seed(UserSeeder::class);

        $account = User::query()->where('email', $email)->sole();
        $this->assertSame($name, "{$account->first_name} {$account->last_name}");
        $this->assertSame($role, $account->role);
        $this->assertSame($confirmed, $account->hasVerifiedEmail());
        $this->assertTrue(Hash::check('password', $account->password));
    }

    /**
     * @return array<string, array{string, string, Role, bool}>
     */
    public static function accounts(): array
    {
        return [
            'super admin' => [UserSeeder::SUPER_ADMIN, 'Super Admin', Role::SuperAdmin, true],
            'staff admin' => [UserSeeder::STAFF_ADMIN, 'Staff Admin', Role::Admin, true],
            'circle owner' => [UserSeeder::CIRCLE_OWNER, 'Circle Owner', Role::User, true],
            'circle admin' => [UserSeeder::CIRCLE_ADMIN, 'Circle Admin', Role::User, true],
            'circle member' => [UserSeeder::CIRCLE_MEMBER, 'Circle Member', Role::User, true],
            'unconfirmed member' => [UserSeeder::UNCONFIRMED, 'Unconfirmed Member', Role::User, false],
        ];
    }

    public function test_the_dev_accounts_are_old_enough_to_start_a_circle_straight_away(): void
    {
        $this->seed(UserSeeder::class);

        $this->assertTrue(User::query()->where('email', UserSeeder::CIRCLE_OWNER)->sole()->can('create', Circle::class));
    }

    public function test_adds_plenty_of_people_to_fill_circles(): void
    {
        $this->seed(UserSeeder::class);

        $this->assertSame(count(self::accounts()) + UserSeeder::PEOPLE, User::query()->count());
    }
}
