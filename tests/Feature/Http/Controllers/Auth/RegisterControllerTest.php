<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegisterControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_renders_the_register_page(): void
    {
        $response = $this->get(route('auth.register.create'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page->component('auth/Register'));
    }

    public function test_valid_form_creates_the_account_logs_the_member_in_and_redirects_home(): void
    {
        $response = $this->post(route('auth.register.store'), $this->validForm());

        $response->assertRedirect(route('home'));

        $this->assertDatabaseHas(User::class, [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
        ]);
        $user = User::sole();
        $this->assertTrue(Hash::check('Analytical1!', $user->password));

        $this->assertAuthenticatedAs($user);
    }

    public function test_rejects_an_empty_form_with_a_required_message_for_each_field(): void
    {
        $response = $this->from(route('auth.register.create'))->post(route('auth.register.store'), []);

        $response->assertRedirect(route('auth.register.create'));
        $response->assertInvalid([
            'first_name' => 'The first name field is required.',
            'last_name' => 'The last name field is required.',
            'email' => 'The email field is required.',
            'password' => 'The password field is required.',
        ]);

        $this->assertDatabaseEmpty(User::class);
        $this->assertGuest();
    }

    /**
     * @param  array<string, mixed>  $override
     */
    #[DataProvider('invalidFields')]
    public function test_rejects_an_invalid_field_with_its_message(array $override, string $field, string $message): void
    {
        $response = $this->from(route('auth.register.create'))
            ->post(route('auth.register.store'), [...$this->validForm(), ...$override]);

        $response->assertRedirect(route('auth.register.create'));
        $response->assertInvalid([$field => $message]);

        $this->assertDatabaseEmpty(User::class);
        $this->assertGuest();
    }

    /**
     * Each case breaks one rule of an otherwise valid form.
     *
     * @return array<string, array{array<string, mixed>, string, string}>
     */
    public static function invalidFields(): array
    {
        $tooLongEmail = 'ada@'.implode('.', [str_repeat('a', 63), str_repeat('b', 63), str_repeat('c', 63), str_repeat('d', 63)]).'.com';
        $tooLongPassword = str_repeat('a', 255).'1!';

        return [
            'first name that is not text' => [['first_name' => ['Ada']], 'first_name', 'The first name field must be a string.'],
            'first name shorter than 2 characters' => [['first_name' => 'A'], 'first_name', 'The first name field must be at least 2 characters.'],
            'first name longer than 255 characters' => [['first_name' => str_repeat('a', 256)], 'first_name', 'The first name field must not be greater than 255 characters.'],
            'last name that is not text' => [['last_name' => ['Lovelace']], 'last_name', 'The last name field must be a string.'],
            'last name shorter than 2 characters' => [['last_name' => 'L'], 'last_name', 'The last name field must be at least 2 characters.'],
            'last name longer than 255 characters' => [['last_name' => str_repeat('a', 256)], 'last_name', 'The last name field must not be greater than 255 characters.'],
            'email that is not text' => [['email' => ['ada@example.com']], 'email', 'The email field must be a string.'],
            'email with a dotless domain' => [['email' => 'ada@example'], 'email', 'The email field must be a valid email address.'],
            'email with capital letters' => [['email' => 'Ada@example.com'], 'email', 'The email field must be lowercase.'],
            'email longer than 255 characters' => [['email' => $tooLongEmail], 'email', 'The email field must not be greater than 255 characters.'],
            'password confirmation that does not match' => [['password_confirmation' => 'Different1!'], 'password', 'The password field confirmation does not match.'],
            'password shorter than 8 characters' => [['password' => 'Ab1!', 'password_confirmation' => 'Ab1!'], 'password', 'The password field must be at least 8 characters.'],
            'password longer than 256 characters' => [['password' => $tooLongPassword, 'password_confirmation' => $tooLongPassword], 'password', 'The password field must not be greater than 256 characters.'],
            'password without a letter' => [['password' => '12345678!', 'password_confirmation' => '12345678!'], 'password', 'The password field must contain at least one letter.'],
            'password without a number' => [['password' => 'Analytical!', 'password_confirmation' => 'Analytical!'], 'password', 'The password field must contain at least one number.'],
            'password without a symbol' => [['password' => 'Analytical1', 'password_confirmation' => 'Analytical1'], 'password', 'The password field must contain at least one symbol.'],
        ];
    }

    public function test_rejects_an_email_that_is_already_registered(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        $response = $this->from(route('auth.register.create'))->post(route('auth.register.store'), $this->validForm());

        $response->assertRedirect(route('auth.register.create'));
        $response->assertInvalid(['email' => 'The email has already been taken.']);

        $this->assertDatabaseCount(User::class, 1);
        $this->assertGuest();
    }

    public function test_redirects_a_signed_in_member_away_from_the_register_page(): void
    {
        $member = User::factory()->create();

        $response = $this->actingAs($member)->get(route('auth.register.create'));

        $response->assertRedirect(route('home'));
    }

    public function test_does_not_register_another_account_for_a_signed_in_member(): void
    {
        $member = User::factory()->create();

        $response = $this->actingAs($member)->post(route('auth.register.store'), $this->validForm());

        $response->assertRedirect(route('home'));

        $this->assertDatabaseCount(User::class, 1);

        $this->assertAuthenticatedAs($member);
    }

    public function test_does_not_let_the_form_verify_the_email_or_set_the_remember_token(): void
    {
        $this->post(route('auth.register.store'), [
            ...$this->validForm(),
            'email_verified_at' => '2026-01-01 00:00:00',
            'remember_token' => 'injected',
        ]);

        $user = User::sole();
        $this->assertNull($user->email_verified_at);
        $this->assertNull($user->remember_token);
    }

    /**
     * A registration form that passes every rule.
     *
     * @return array<string, string>
     */
    private function validForm(): array
    {
        return [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'password' => 'Analytical1!',
            'password_confirmation' => 'Analytical1!',
        ];
    }
}
