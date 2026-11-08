<?php

namespace Tests\Feature\Http\Middleware;

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
}
