<?php

namespace Tests\Feature\Models;

use App\Enums\CircleRole;
use App\Enums\Role;
use App\Models\Circle;
use App\Models\User;
use App\Notifications\ResetPassword;
use App\Notifications\VerifyEmail;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Enums\ActivityEvent;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class UserTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_sends_the_queued_email_confirmation(): void
    {
        $user = User::factory()->unverified()->make();
        Notification::fake();

        $user->sendEmailVerificationNotification();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_sends_the_queued_password_reset_email_with_its_token(): void
    {
        $user = User::factory()->make();
        Notification::fake();

        $user->sendPasswordResetNotification('token-from-broker');

        Notification::assertSentTo($user, ResetPassword::class, fn (ResetPassword $notification): bool => $notification->token === 'token-from-broker');
    }

    public function test_hashes_the_password_when_it_is_assigned(): void
    {
        $user = new User(['password' => 'correct-horse-battery']);

        $this->assertNotSame('correct-horse-battery', $user->password);
        $this->assertTrue(Hash::check('correct-horse-battery', $user->password));
    }

    public function test_casts_email_verified_at_to_a_date(): void
    {
        $user = new User;
        $user->email_verified_at = '2026-11-07 12:00:00';

        $this->assertInstanceOf(CarbonInterface::class, $user->email_verified_at);
    }

    public function test_hides_the_password_and_remember_token_when_serialised(): void
    {
        $user = new User(['email' => 'member@example.com', 'password' => 'correct-horse-battery']);
        $user->remember_token = 'remember-me';

        $serialised = $user->toArray();

        $this->assertSame('member@example.com', $serialised['email']);
        $this->assertArrayNotHasKey('password', $serialised);
        $this->assertArrayNotHasKey('remember_token', $serialised);
    }

    public function test_mass_assigns_the_first_and_last_name(): void
    {
        $user = new User(['first_name' => 'Ada', 'last_name' => 'Lovelace']);

        $this->assertSame('Ada', $user->first_name);
        $this->assertSame('Lovelace', $user->last_name);
    }

    public function test_ignores_attributes_that_are_not_mass_assignable(): void
    {
        $user = new User(['email' => 'member@example.com', 'remember_token' => 'injected']);

        $this->assertSame('member@example.com', $user->email);
        $this->assertNull($user->remember_token);
    }

    public function test_does_not_let_the_role_be_mass_assigned(): void
    {
        $user = new User(['email' => 'member@example.com', 'role' => 'super_admin']);

        $this->assertNull($user->role);
    }

    public function test_casts_the_role_to_the_role_enum(): void
    {
        $user = new User;
        $user->role = 'admin';

        $this->assertSame(Role::Admin, $user->role);
    }

    public function test_a_new_member_is_an_ordinary_user(): void
    {
        $user = User::create([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'password' => 'Analytical1!',
        ]);

        $this->assertSame(Role::User, $user->fresh()->role);
    }

    public function test_the_factory_makes_ordinary_users_like_the_database_does(): void
    {
        $user = User::factory()->make();

        $this->assertSame(Role::User, $user->role);
    }

    public function test_circles_are_the_ones_the_member_is_in_with_their_role(): void
    {
        $user = User::factory()->create();
        $circle = Circle::factory()->create();
        $circle->members()->attach($user, ['role' => CircleRole::Admin]);
        Circle::factory()->create();

        $this->assertSame([$circle->id], $user->circles->modelKeys());
        $this->assertSame(CircleRole::Admin, $user->circles->first()->pivot->role);
    }

    public function test_staff_are_the_admins_and_super_admins(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $superAdmin = User::factory()->create(['role' => Role::SuperAdmin]);
        User::factory()->create(['role' => Role::User]);

        $this->assertEqualsCanonicalizing([$admin->id, $superAdmin->id], User::query()->staff()->pluck('id')->all());
    }

    public function test_logs_a_new_member_joining_without_their_password(): void
    {
        $user = User::factory()->create(['first_name' => 'Ada', 'email' => 'ada@example.com']);

        $activity = $this->lastActivity();
        $this->assertSame(ActivityEvent::Created->value, $activity->event);
        $this->assertTrue($activity->subject->is($user));
        $this->assertNull($activity->causer);
        $this->assertSame('Ada', $activity->attribute_changes['attributes']['first_name']);
        $this->assertSame('ada@example.com', $activity->attribute_changes['attributes']['email']);
        $this->assertArrayNotHasKey('password', $activity->attribute_changes['attributes']);
        $this->assertArrayNotHasKey('remember_token', $activity->attribute_changes['attributes']);
    }

    public function test_logs_changes_to_a_members_name_and_role_with_who_made_them(): void
    {
        $user = User::factory()->create(['first_name' => 'Ada', 'role' => Role::User]);
        $superAdmin = User::factory()->create(['role' => Role::SuperAdmin]);
        $this->actingAs($superAdmin);

        $user->forceFill(['first_name' => 'Augusta', 'role' => Role::Admin])->save();

        $activity = $this->lastActivity();
        $this->assertSame(ActivityEvent::Updated->value, $activity->event);
        $this->assertTrue($activity->subject->is($user));
        $this->assertTrue($activity->causer->is($superAdmin));
        $this->assertSame(['first_name' => 'Augusta', 'role' => 'admin'], $activity->attribute_changes['attributes']);
        $this->assertSame(['first_name' => 'Ada', 'role' => 'user'], $activity->attribute_changes['old']);
    }

    public function test_logs_a_member_confirming_their_email(): void
    {
        $user = User::factory()->unverified()->create();
        $this->actingAs($user);

        $user->markEmailAsVerified();

        $activity = $this->lastActivity();
        $this->assertSame(ActivityEvent::Updated->value, $activity->event);
        $this->assertSame(['email_verified_at'], array_keys($activity->attribute_changes['attributes']));
        $this->assertNotNull($activity->attribute_changes['attributes']['email_verified_at']);
        $this->assertNull($activity->attribute_changes['old']['email_verified_at']);
    }

    public function test_logs_that_the_password_changed_without_storing_it_or_its_hash(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $user->forceFill(['password' => 'Another-password-1'])->save();

        $activity = $this->lastActivity();
        $this->assertSame(ActivityEvent::Updated->value, $activity->event);
        $this->assertTrue($activity->causer->is($user));
        $this->assertSame(['password' => '[hidden]'], $activity->attribute_changes['attributes']);
        $this->assertSame(['password' => '[hidden]'], $activity->attribute_changes['old']);
        $this->assertStringNotContainsString($user->password, $activity->getRawOriginal('attribute_changes'));
    }

    public function test_does_not_log_a_new_remember_token(): void
    {
        $user = User::factory()->create();
        $logged = Activity::query()->count();

        $user->forceFill(['remember_token' => 'new-token'])->save();

        $this->assertSame($logged, Activity::query()->count());
    }

    public function test_does_not_log_a_save_that_only_touches_the_timestamps(): void
    {
        $user = User::factory()->create();
        $logged = Activity::query()->count();
        $this->travel(1)->minute();

        $user->touch();

        $this->assertSame($logged, Activity::query()->count());
    }

    public function test_logs_a_member_being_deleted_with_their_details(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com']);

        $user->delete();

        $activity = $this->lastActivity();
        $this->assertSame(ActivityEvent::Deleted->value, $activity->event);
        $this->assertSame($user->id, $activity->subject_id);
        $this->assertSame('ada@example.com', $activity->attribute_changes['old']['email']);
        $this->assertArrayNotHasKey('password', $activity->attribute_changes['old']);
    }

    /**
     * The newest audit log entry.
     */
    private function lastActivity(): Activity
    {
        return Activity::query()->latest('id')->firstOrFail();
    }
}
