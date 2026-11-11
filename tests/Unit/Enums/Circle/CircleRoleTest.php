<?php

namespace Tests\Unit\Enums\Circle;

use App\Enums\Circle\CircleRole;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\ReadsFrontendTypes;

class CircleRoleTest extends TestCase
{
    use ReadsFrontendTypes;

    public function test_the_frontend_type_has_the_same_values(): void
    {
        $this->assertSame(array_column(CircleRole::cases(), 'value'), $this->frontendEnumValues('circle/circle-role.ts'));
    }

    #[DataProvider('labels')]
    public function test_labels_each_role_for_display(CircleRole $role, string $label): void
    {
        $this->assertSame($label, $role->label());
    }

    #[DataProvider('managerRoles')]
    public function test_only_owners_and_admins_have_a_manager_role(CircleRole $role, bool $isManager): void
    {
        $this->assertSame($isManager, $role->hasManagerRole());
    }

    /**
     * @return array<string, array{CircleRole, string}>
     */
    public static function labels(): array
    {
        return [
            'owner' => [CircleRole::Owner, 'Owner'],
            'admin' => [CircleRole::Admin, 'Admin'],
            'member' => [CircleRole::Member, 'Member'],
        ];
    }

    /**
     * @return array<string, array{CircleRole, bool}>
     */
    public static function managerRoles(): array
    {
        return [
            'owner' => [CircleRole::Owner, true],
            'admin' => [CircleRole::Admin, true],
            'member' => [CircleRole::Member, false],
        ];
    }
}
