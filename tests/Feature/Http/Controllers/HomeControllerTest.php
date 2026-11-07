<?php

namespace Tests\Feature\Http\Controllers;

use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    public function test_renders_the_home_page_with_its_content_from_the_server(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Home')
            ->has('hero.title')
            ->has('circles')
            ->has('communityRules.href'));
        $this->assertContains($response->inertiaProps('activeFeed'), array_column($response->inertiaProps('feeds'), 'value'));
    }
}
