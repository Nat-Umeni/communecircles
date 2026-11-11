<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
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

    /**
     * A Resource as a page receives it: resolved during a request, so it (and any Resource inside it) sees whoever
     * is signed in with actingAs(), as it would for a real page.
     *
     * @return array<string, mixed>
     */
    protected function asSent(JsonResource $resource): array
    {
        // Binding the request is what wires up $request->user() in a real request.
        $this->app->instance('request', Request::create('/'));

        return $resource->toResponse(request())->getData(true);
    }
}
