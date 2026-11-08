<?php

namespace Tests\Unit\Enums;

use App\Enums\Role;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RoleTest extends TestCase
{
    #[DataProvider('labels')]
    public function test_labels_each_role_for_display(Role $role, string $label): void
    {
        $this->assertSame($label, $role->label());
    }

    /**
     * @return array<string, array{Role, string}>
     */
    public static function labels(): array
    {
        return [
            'user' => [Role::User, 'User'],
            'admin' => [Role::Admin, 'Admin'],
            'super admin' => [Role::SuperAdmin, 'Super Admin'],
        ];
    }
}
