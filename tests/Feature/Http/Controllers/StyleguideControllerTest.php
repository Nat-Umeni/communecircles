<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\StyleguideController;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class StyleguideControllerTest extends TestCase
{
    public function test_returns_404_outside_the_local_environment(): void
    {
        $response = $this->get('/styleguide');

        $response->assertNotFound();
    }

    public function test_folds_foreground_colours_into_their_base_colour_with_resolved_hex_values(): void
    {
        // The real route only exists locally, so register one for this test.
        Route::middleware('web')->get('/_test/styleguide', StyleguideController::class);

        $response = $this->get('/_test/styleguide');

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Styleguide')
            ->where('themeColours', function ($colours): bool {
                $primary = $colours->firstWhere('name', 'primary');

                return ! $colours->contains('name', 'primary-foreground')
                    && $primary['foreground'] === 'primary-foreground'
                    && preg_match('/^#[0-9A-F]{6}$/i', $primary['light']['hex']) === 1
                    && preg_match('/^#[0-9A-F]{6}$/i', $primary['dark']['hex']) === 1;
            })
            ->has('icons'));
    }
}
