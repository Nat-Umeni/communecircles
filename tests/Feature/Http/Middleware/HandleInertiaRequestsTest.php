<?php

namespace Tests\Feature\Http\Middleware;

use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class HandleInertiaRequestsTest extends TestCase
{
    public function test_shares_the_current_locale_and_the_available_languages(): void
    {
        $response = $this->get(route('home'));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('locale', 'en')
            ->where('locales', fn ($locales): bool => $locales->contains('value', 'en')));
    }

    public function test_shares_no_user_with_a_guest(): void
    {
        $response = $this->get(route('home'));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('auth.user', null));
    }

    public function test_shares_only_the_name_email_and_verification_of_the_signed_in_member(): void
    {
        $member = User::factory()->make([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
        ]);

        $response = $this->actingAs($member)->get(route('home'));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('auth.user', [
                'first_name' => 'Ada',
                'last_name' => 'Lovelace',
                'email' => 'ada@example.com',
                'email_verified' => true,
            ]));
    }

    public function test_shares_that_a_member_has_not_confirmed_their_email_yet(): void
    {
        $member = User::factory()->unverified()->make();

        $response = $this->actingAs($member)->get(route('home'));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('auth.user.email_verified', false));
    }
}
