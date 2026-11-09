<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Pages render through the Vite-built root view; tests shouldn't need a build or dev server.
        $this->withoutVite();

        // Outside services like GeoNames are faked with Http::fake(); a request nothing fakes fails the test.
        Http::preventStrayRequests();
    }
}
