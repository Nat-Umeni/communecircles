<?php

namespace Tests\Feature\Models;

use App\Enums\CircleRole;
use App\Models\Circle;
use App\Models\CircleUser;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Activitylog\Enums\ActivityEvent;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class CircleUserTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_casts_the_role_to_the_circle_role_enum(): void
    {
        $membership = new CircleUser;
        $membership->role = 'admin';

        $this->assertSame(CircleRole::Admin, $membership->role);
    }

    public function test_logs_someone_joining_a_circle_with_their_role_and_who_let_them_in(): void
    {
        $circle = Circle::factory()->create();
        $person = User::factory()->create();
        $owner = User::factory()->create();
        $this->actingAs($owner);

        $circle->members()->attach($person, ['role' => CircleRole::Member]);

        $membership = $circle->membershipOf($person);
        $activity = $this->lastActivity();
        $this->assertSame(ActivityEvent::Created->value, $activity->event);
        $this->assertTrue($activity->subject->is($membership));
        $this->assertTrue($activity->causer->is($owner));
        $this->assertSame(['circle_id' => $circle->id, 'user_id' => $person->id, 'role' => 'member'], $activity->attribute_changes['attributes']);
    }

    public function test_logs_a_change_of_role(): void
    {
        $circle = Circle::factory()->create();
        $person = User::factory()->create();
        $circle->members()->attach($person, ['role' => CircleRole::Member]);

        $circle->members()->updateExistingPivot($person->id, ['role' => CircleRole::Admin]);

        $activity = $this->lastActivity();
        $this->assertSame(ActivityEvent::Updated->value, $activity->event);
        $this->assertSame(['role' => 'admin'], $activity->attribute_changes['attributes']);
        $this->assertSame(['role' => 'member'], $activity->attribute_changes['old']);
    }

    public function test_does_not_log_a_save_that_only_touches_the_timestamps(): void
    {
        $circle = Circle::factory()->create();
        $person = User::factory()->create();
        $circle->members()->attach($person, ['role' => CircleRole::Member]);
        $logged = Activity::query()->count();
        $this->travel(1)->minute();

        $circle->membershipOf($person)->touch();

        $this->assertSame($logged, Activity::query()->count());
    }

    public function test_logs_someone_leaving_a_circle(): void
    {
        $circle = Circle::factory()->create();
        $person = User::factory()->create();
        $circle->members()->attach($person, ['role' => CircleRole::Member]);

        $circle->members()->detach($person);

        $activity = $this->lastActivity();
        $this->assertSame(ActivityEvent::Deleted->value, $activity->event);
        $this->assertSame($circle->id, $activity->attribute_changes['old']['circle_id']);
        $this->assertSame($person->id, $activity->attribute_changes['old']['user_id']);
    }

    /**
     * The newest audit log entry.
     */
    private function lastActivity(): Activity
    {
        return Activity::query()->latest('id')->firstOrFail();
    }
}
