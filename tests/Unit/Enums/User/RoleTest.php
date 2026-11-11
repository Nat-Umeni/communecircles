<?php

namespace Tests\Unit\Enums\User;

use App\Enums\User\Role;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\ReadsFrontendTypes;

class RoleTest extends TestCase
{
    use ReadsFrontendTypes;

    public function test_the_frontend_type_has_the_same_values(): void
    {
        $this->assertSame(array_column(Role::cases(), 'value'), $this->frontendEnumValues('user/role.ts'));
    }

    #[DataProvider('labels')]
    public function test_labels_each_role_for_display(Role $role, string $label): void
    {
        $this->assertSame($label, $role->label());
    }

    #[DataProvider('internalRoles')]
    public function test_only_admins_and_super_admins_are_internal(Role $role, bool $isInternal): void
    {
        $this->assertSame($isInternal, $role->internal());
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

    /**
     * @return array<string, array{Role, bool}>
     */
    public static function internalRoles(): array
    {
        return [
            'user' => [Role::User, false],
            'admin' => [Role::Admin, true],
            'super admin' => [Role::SuperAdmin, true],
        ];
    }
}
