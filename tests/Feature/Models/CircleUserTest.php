<?php

namespace Tests\Feature\Models;

use App\Enums\CircleRole;
use App\Models\CircleUser;
use Tests\TestCase;

class CircleUserTest extends TestCase
{
    public function test_casts_the_role_to_the_circle_role_enum(): void
    {
        $membership = new CircleUser;
        $membership->role = 'admin';

        $this->assertSame(CircleRole::Admin, $membership->role);
    }
}
