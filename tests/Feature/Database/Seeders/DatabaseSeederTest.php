<?php

namespace Tests\Feature\Database\Seeders;

use App\Models\Circle;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_seeds_the_accounts_then_the_circles_with_each_one_in_the_audit_log(): void
    {
        $this->seed();

        $this->assertTrue(User::query()->where('email', UserSeeder::SUPER_ADMIN)->exists(), 'the accounts are seeded');
        $this->assertGreaterThan(0, Circle::query()->count(), 'the circles are seeded');
        $this->assertSame(
            Circle::query()->count(),
            Activity::query()->where('subject_type', (new Circle)->getMorphClass())->where('event', 'created')->count(),
            'every seeded circle is in the audit log, so model events were on',
        );
    }
}
