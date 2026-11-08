<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class EmailVerificationControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_shows_an_unconfirmed_member_the_check_your_inbox_page(): void
    {
        $response = $this->actingAs(User::factory()->unverified()->create())->get(route('verification.notice'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page->component('auth/VerifyEmail'));
    }

    public function test_sends_a_confirmed_member_home_from_the_check_your_inbox_page(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('verification.notice'));

        $response->assertRedirect(route('home'));
    }

    public function test_sends_a_guest_to_log_in_from_the_check_your_inbox_page(): void
    {
        $response = $this->get(route('verification.notice'));

        $response->assertRedirect(route('login'));
    }

    public function test_the_link_from_the_email_confirms_the_address_and_sends_the_member_home(): void
    {
        $member = User::factory()->unverified()->create();
        Event::fake([Verified::class]);

        $response = $this->actingAs($member)->get($this->confirmationLink($member));

        $response->assertRedirect(route('home'));
        $response->assertInertiaFlash('status', 'Thanks, your email address is confirmed.');

        $this->assertTrue($member->fresh()->hasVerifiedEmail());

        Event::assertDispatched(Verified::class, fn (Verified $event): bool => $event->user->is($member));
    }

    public function test_refuses_a_link_whose_hash_does_not_match_the_email(): void
    {
        $member = User::factory()->unverified()->create();

        $response = $this->actingAs($member)->get($this->confirmationLink($member, hash: sha1('someone-else@example.com')));

        $response->assertForbidden();

        $this->assertFalse($member->fresh()->hasVerifiedEmail());
    }

    public function test_refuses_another_members_link(): void
    {
        $member = User::factory()->unverified()->create();
        $other = User::factory()->unverified()->create();

        $response = $this->actingAs($member)->get($this->confirmationLink($other));

        $response->assertForbidden();

        $this->assertFalse($member->fresh()->hasVerifiedEmail());
        $this->assertFalse($other->fresh()->hasVerifiedEmail());
    }

    public function test_refuses_an_expired_link(): void
    {
        $member = User::factory()->unverified()->create();

        $response = $this->actingAs($member)->get($this->confirmationLink($member, expires: now()->subMinute()));

        $response->assertForbidden();

        $this->assertFalse($member->fresh()->hasVerifiedEmail());
    }

    public function test_refuses_a_link_without_a_signature(): void
    {
        $member = User::factory()->unverified()->create();

        $response = $this->actingAs($member)->get(route('verification.verify', ['id' => $member->id, 'hash' => sha1($member->email)]));

        $response->assertForbidden();

        $this->assertFalse($member->fresh()->hasVerifiedEmail());
    }

    public function test_a_link_for_an_address_that_is_already_confirmed_just_sends_the_member_home(): void
    {
        $member = User::factory()->create();
        Event::fake([Verified::class]);

        $response = $this->actingAs($member)->get($this->confirmationLink($member));

        $response->assertRedirect(route('home'));

        Event::assertNotDispatched(Verified::class);
    }

    public function test_sends_a_guest_with_a_link_to_log_in_first(): void
    {
        $member = User::factory()->unverified()->create();

        $response = $this->get($this->confirmationLink($member));

        $response->assertRedirect(route('login'));

        $this->assertFalse($member->fresh()->hasVerifiedEmail());
    }

    public function test_resends_the_confirmation_email(): void
    {
        $member = User::factory()->unverified()->create();
        Notification::fake();

        $response = $this->actingAs($member)->from(route('verification.notice'))->post(route('verification.send'));

        $response->assertRedirect(route('verification.notice'));
        $response->assertInertiaFlash('status', "We've sent a new confirmation link to your email address.");

        Notification::assertSentTo($member, VerifyEmail::class);
    }

    public function test_does_not_resend_once_the_address_is_confirmed(): void
    {
        $member = User::factory()->create();
        Notification::fake();

        $response = $this->actingAs($member)->post(route('verification.send'));

        $response->assertRedirect(route('home'));

        Notification::assertNotSentTo($member, VerifyEmail::class);
    }

    public function test_throttles_resending_after_5_in_a_minute(): void
    {
        $member = User::factory()->unverified()->create();
        Notification::fake();

        foreach (range(1, 5) as $attempt) {
            $this->actingAs($member)->post(route('verification.send'));
        }

        $response = $this->actingAs($member)->post(route('verification.send'));

        $response->assertTooManyRequests();
    }

    public function test_sends_a_guest_who_asks_for_a_new_link_to_log_in(): void
    {
        $response = $this->post(route('verification.send'));

        $response->assertRedirect(route('login'));
    }

    /**
     * A signed link like the one in the confirmation email, with an optional wrong hash or expiry.
     */
    private function confirmationLink(User $member, ?string $hash = null, ?Carbon $expires = null): string
    {
        return URL::temporarySignedRoute('verification.verify', $expires ?? now()->addHour(), [
            'id' => $member->id,
            'hash' => $hash ?? sha1($member->email),
        ]);
    }
}
