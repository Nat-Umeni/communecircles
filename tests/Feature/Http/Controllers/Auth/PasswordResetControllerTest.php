<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use App\Notifications\ResetPassword;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PasswordResetControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const string LINK_SENT = "If an account exists for that email, we've sent a link to reset your password.";

    private const string LINK_INVALID = 'This password reset link is invalid or has expired.';

    public function test_renders_the_forgot_password_page(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page->component('auth/ForgotPassword'));
    }

    public function test_emails_a_reset_link_to_an_existing_account(): void
    {
        $member = $this->member();
        Notification::fake();

        $response = $this->from(route('password.request'))->post(route('password.email'), ['email' => 'ada@example.com']);

        $response->assertRedirect(route('password.request'));
        $response->assertInertiaFlash('status', self::LINK_SENT);

        Notification::assertSentTo($member, ResetPassword::class);
    }

    public function test_shows_the_same_message_and_sends_nothing_for_an_email_with_no_account(): void
    {
        Notification::fake();

        $response = $this->from(route('password.request'))->post(route('password.email'), ['email' => 'nobody@example.com']);

        $response->assertRedirect(route('password.request'));
        $response->assertInertiaFlash('status', self::LINK_SENT);

        Notification::assertNothingSent();
    }

    public function test_shows_the_same_message_without_sending_again_when_asked_twice_within_a_minute(): void
    {
        $member = $this->member();
        Notification::fake();
        $this->post(route('password.email'), ['email' => 'ada@example.com']);

        $response = $this->from(route('password.request'))->post(route('password.email'), ['email' => 'ada@example.com']);

        $response->assertInertiaFlash('status', self::LINK_SENT);

        Notification::assertSentToTimes($member, ResetPassword::class, 1);
    }

    /**
     * @param  array<string, mixed>  $form
     */
    #[DataProvider('invalidEmails')]
    public function test_rejects_a_missing_or_invalid_email_with_its_message(array $form, string $message): void
    {
        Notification::fake();

        $response = $this->from(route('password.request'))->post(route('password.email'), $form);

        $response->assertRedirect(route('password.request'));
        $response->assertInvalid(['email' => $message]);

        Notification::assertNothingSent();
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidEmails(): array
    {
        return [
            'no email' => [[], 'The email field is required.'],
            'email that is not text' => [['email' => ['ada@example.com']], 'The email field must be a string.'],
            'email that is not an email address' => [['email' => 'ada'], 'The email field must be a valid email address.'],
        ];
    }

    public function test_throttles_reset_link_requests_after_5_in_a_minute(): void
    {
        Notification::fake();

        foreach (range(1, 5) as $attempt) {
            $this->post(route('password.email'), []);
        }

        $response = $this->post(route('password.email'), ['email' => 'ada@example.com']);

        $response->assertTooManyRequests();
    }

    public function test_redirects_a_signed_in_member_away_from_the_forgot_password_page(): void
    {
        $response = $this->actingAs($this->member())->get(route('password.request'));

        $response->assertRedirect(route('home'));
    }

    public function test_renders_the_reset_password_page_with_the_token_and_email_from_the_link(): void
    {
        $response = $this->get(route('password.reset', ['token' => 'token-from-email', 'email' => 'ada@example.com']));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('auth/ResetPassword')
            ->where('token', 'token-from-email')
            ->where('email', 'ada@example.com'));
    }

    public function test_a_valid_link_sets_the_new_password_and_sends_the_member_to_log_in(): void
    {
        $member = $this->member();
        $rememberTokenBefore = $member->remember_token;
        Event::fake([PasswordReset::class]);

        $response = $this->post(route('password.update'), $this->resetForm(Password::createToken($member)));

        $response->assertRedirect(route('login'));
        $response->assertInertiaFlash('status', 'Your password has been reset. You can log in with it now.');

        $member->refresh();
        $this->assertTrue(Hash::check('Brand-new-pass1!', $member->password));
        $this->assertNotSame($rememberTokenBefore, $member->remember_token);

        $this->assertGuest();

        Event::assertDispatched(PasswordReset::class, fn (PasswordReset $event): bool => $event->user->is($member));
    }

    public function test_a_link_works_only_once(): void
    {
        $member = $this->member();
        $token = Password::createToken($member);
        $this->post(route('password.update'), $this->resetForm($token));

        $response = $this->from(route('password.reset', ['token' => $token]))
            ->post(route('password.update'), [...$this->resetForm($token), 'password' => 'Another-pass1!', 'password_confirmation' => 'Another-pass1!']);

        $response->assertInvalid(['email' => self::LINK_INVALID]);

        $this->assertTrue(Hash::check('Brand-new-pass1!', $member->fresh()->password));
    }

    /**
     * @param  array<string, string>  $override
     */
    #[DataProvider('invalidLinks')]
    public function test_refuses_an_invalid_link_without_changing_the_password(array $override): void
    {
        $member = $this->member();
        $form = [...$this->resetForm(Password::createToken($member)), ...$override];

        $response = $this->from(route('password.reset', ['token' => $form['token']]))->post(route('password.update'), $form);

        $response->assertRedirect(route('password.reset', ['token' => $form['token']]));
        $response->assertInvalid(['email' => self::LINK_INVALID]);

        $this->assertTrue(Hash::check('Old-password1!', $member->fresh()->password));
    }

    /**
     * @return array<string, array{array<string, string>}>
     */
    public static function invalidLinks(): array
    {
        return [
            'wrong token' => [['token' => 'not-the-emailed-token']],
            'email with no account' => [['email' => 'nobody@example.com']],
            "another member's email" => [['email' => 'someone-else@example.com']],
        ];
    }

    public function test_refuses_a_link_older_than_an_hour(): void
    {
        $member = $this->member();
        $token = Password::createToken($member);
        $this->travel(61)->minutes();

        $response = $this->from(route('password.reset', ['token' => $token]))->post(route('password.update'), $this->resetForm($token));

        $response->assertInvalid(['email' => self::LINK_INVALID]);

        $this->assertTrue(Hash::check('Old-password1!', $member->fresh()->password));
    }

    /**
     * @param  array<string, mixed>  $override
     */
    #[DataProvider('invalidResetFields')]
    public function test_rejects_an_invalid_field_with_its_message(array $override, string $field, string $message): void
    {
        $member = $this->member();
        $form = [...$this->resetForm(Password::createToken($member)), ...$override];

        $response = $this->from(route('password.request'))->post(route('password.update'), $form);

        $response->assertRedirect(route('password.request'));
        $response->assertInvalid([$field => $message]);

        $this->assertTrue(Hash::check('Old-password1!', $member->fresh()->password));
    }

    /**
     * Each case breaks one rule of an otherwise valid form. The full set of password rules is tested in AppServiceProviderTest.
     *
     * @return array<string, array{array<string, mixed>, string, string}>
     */
    public static function invalidResetFields(): array
    {
        return [
            'no token' => [['token' => ''], 'token', 'The token field is required.'],
            'no email' => [['email' => ''], 'email', 'The email field is required.'],
            'email that is not an email address' => [['email' => 'ada'], 'email', 'The email field must be a valid email address.'],
            'no password' => [['password' => '', 'password_confirmation' => ''], 'password', 'The password field is required.'],
            'password that breaks the password rules' => [['password' => 'short1!', 'password_confirmation' => 'short1!'], 'password', 'The password field must be at least 8 characters.'],
            'password confirmation that does not match' => [['password_confirmation' => 'Different-pass1!'], 'password', 'The password field confirmation does not match.'],
        ];
    }

    public function test_throttles_password_resets_after_5_in_a_minute(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->post(route('password.update'), []);
        }

        $response = $this->post(route('password.update'), $this->resetForm('any-token'));

        $response->assertTooManyRequests();
    }

    public function test_redirects_a_signed_in_member_away_from_the_reset_password_page(): void
    {
        $response = $this->actingAs($this->member())->get(route('password.reset', ['token' => 'token-from-email']));

        $response->assertRedirect(route('home'));
    }

    /**
     * The member whose password is being reset, plus another member to check links can't be swapped between accounts.
     */
    private function member(): User
    {
        User::factory()->create(['email' => 'someone-else@example.com']);

        return User::factory()->create([
            'email' => 'ada@example.com',
            'password' => 'Old-password1!',
        ]);
    }

    /**
     * A reset form that passes every rule, for the given token.
     *
     * @return array<string, string>
     */
    private function resetForm(string $token): array
    {
        return [
            'token' => $token,
            'email' => 'ada@example.com',
            'password' => 'Brand-new-pass1!',
            'password_confirmation' => 'Brand-new-pass1!',
        ];
    }
}
