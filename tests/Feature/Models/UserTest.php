<?php

namespace Tests\Feature\Models;

use App\Models\User;
use App\Notifications\ResetPassword;
use App\Notifications\VerifyEmail;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserTest extends TestCase
{
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
}
