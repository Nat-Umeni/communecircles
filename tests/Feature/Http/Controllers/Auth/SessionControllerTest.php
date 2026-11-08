<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SessionControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_renders_the_login_page(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page->component('auth/Login'));
    }

    public function test_valid_credentials_log_the_member_in_and_redirect_home(): void
    {
        $member = $this->member();

        $response = $this->post(route('login.store'), $this->validForm());

        $response->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($member);
    }

    public function test_redirects_to_the_page_the_member_was_sent_to_log_in_from(): void
    {
        $this->member();

        $response = $this->withSession(['url.intended' => url('/circles/permaculture')])
            ->post(route('login.store'), $this->validForm());

        $response->assertRedirect(url('/circles/permaculture'));
    }

    public function test_remember_me_keeps_the_member_logged_in_with_a_remember_cookie(): void
    {
        $member = $this->member();

        $response = $this->post(route('login.store'), [...$this->validForm(), 'remember' => true]);

        $response->assertCookie(Auth::guard()->getRecallerName());

        $this->assertNotNull($member->fresh()->remember_token);
    }

    public function test_does_not_set_a_remember_cookie_without_remember_me(): void
    {
        $this->member();

        $response = $this->post(route('login.store'), $this->validForm());

        $response->assertCookieMissing(Auth::guard()->getRecallerName());
    }

    /**
     * @param  array<string, string>  $override
     */
    #[DataProvider('wrongCredentials')]
    public function test_rejects_wrong_credentials_without_saying_which_part_was_wrong(array $override): void
    {
        $this->member();

        $response = $this->from(route('login'))->post(route('login.store'), [...$this->validForm(), ...$override]);

        $response->assertRedirect(route('login'));
        $response->assertInvalid(['email' => 'These credentials do not match our records.']);

        $this->assertGuest();
    }

    /**
     * @return array<string, array{array<string, string>}>
     */
    public static function wrongCredentials(): array
    {
        return [
            'wrong password' => [['password' => 'Wrong-password1!']],
            'email with no account' => [['email' => 'nobody@example.com']],
        ];
    }

    public function test_rejects_an_empty_form_with_a_required_message_for_each_field(): void
    {
        $response = $this->from(route('login'))->post(route('login.store'), []);

        $response->assertRedirect(route('login'));
        $response->assertInvalid([
            'email' => 'The email field is required.',
            'password' => 'The password field is required.',
        ]);

        $this->assertGuest();
    }

    /**
     * @param  array<string, mixed>  $override
     */
    #[DataProvider('invalidFields')]
    public function test_rejects_an_invalid_field_with_its_message(array $override, string $field, string $message): void
    {
        $this->member();

        $response = $this->from(route('login'))->post(route('login.store'), [...$this->validForm(), ...$override]);

        $response->assertRedirect(route('login'));
        $response->assertInvalid([$field => $message]);

        $this->assertGuest();
    }

    /**
     * Each case breaks one rule of an otherwise valid form.
     *
     * @return array<string, array{array<string, mixed>, string, string}>
     */
    public static function invalidFields(): array
    {
        return [
            'email that is not text' => [['email' => ['ada@example.com']], 'email', 'The email field must be a string.'],
            'email that is not an email address' => [['email' => 'ada'], 'email', 'The email field must be a valid email address.'],
            'email longer than 255 characters' => [['email' => str_repeat('a', 244).'@example.com'], 'email', 'The email field must not be greater than 255 characters.'],
            'password that is not text' => [['password' => ['Analytical1!']], 'password', 'The password field must be a string.'],
        ];
    }

    public function test_throttles_login_attempts_after_5_in_a_minute(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->post(route('login.store'), []);
        }

        $response = $this->post(route('login.store'), $this->validForm());

        $response->assertTooManyRequests();
    }

    public function test_redirects_a_signed_in_member_away_from_the_login_page(): void
    {
        $response = $this->actingAs($this->member())->get(route('login'));

        $response->assertRedirect(route('home'));
    }

    public function test_does_not_log_in_again_for_a_signed_in_member(): void
    {
        $member = $this->member();
        User::factory()->create(['email' => 'other@example.com', 'password' => 'Analytical1!']);

        $response = $this->actingAs($member)->post(route('login.store'), [
            'email' => 'other@example.com',
            'password' => 'Analytical1!',
        ]);

        $response->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($member);
    }

    public function test_logs_the_member_out_and_redirects_home(): void
    {
        $response = $this->actingAs($this->member())->post(route('logout'));

        $response->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_logging_out_clears_the_session_and_replaces_its_csrf_token(): void
    {
        $response = $this->actingAs($this->member())
            ->withSession(['_token' => 'token-before-logout', 'draft' => 'unsent post'])
            ->post(route('logout'));

        $response->assertSessionMissing('draft');
        $this->assertNotSame('token-before-logout', session()->token());
    }

    public function test_sends_a_guest_who_logs_out_to_the_login_page(): void
    {
        $response = $this->post(route('logout'));

        $response->assertRedirect(route('login'));
    }

    /**
     * The member that validForm() logs in as.
     */
    private function member(): User
    {
        return User::factory()->create([
            'email' => 'ada@example.com',
            'password' => 'Analytical1!',
        ]);
    }

    /**
     * Login details that match member().
     *
     * @return array<string, string>
     */
    private function validForm(): array
    {
        return [
            'email' => 'ada@example.com',
            'password' => 'Analytical1!',
        ];
    }
}
