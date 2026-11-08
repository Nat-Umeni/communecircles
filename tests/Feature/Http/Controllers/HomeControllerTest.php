<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    public function test_renders_the_home_page_with_the_circles(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Home')
            ->has('circles.0', fn (AssertableJson $circle) => $circle
                ->whereType('name', 'string')
                ->whereType('description', 'string')
                ->whereType('channels', 'array')));
    }
}
