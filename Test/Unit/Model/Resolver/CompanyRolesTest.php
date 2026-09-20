<?php

declare(strict_types=1);

namespace Orangecat\CompanyGraphQl\Test\Unit\Model\Resolver;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Orangecat\Company\Api\Data\RoleInterface;
use Orangecat\Company\Api\Data\RoleSearchResultsInterface;
use Orangecat\Company\Api\RoleRepositoryInterface;
use Orangecat\CompanyGraphQl\Model\Resolver\CompanyRoles;
use PHPUnit\Framework\TestCase;

/**
 * Unit coverage for the CompanyRoles resolver.
 */
class CompanyRolesTest extends TestCase
{
    public function testMapsRolesAndFlagsTheAdminRole(): void
    {
        $admin = $this->role(1, 'Administrator', '["all"]');
        $buyer = $this->role(3, 'Buyer', '["place_order"]');

        $results = $this->createMock(RoleSearchResultsInterface::class);
        $results->method('getItems')->willReturn([$admin, $buyer]);

        $repository = $this->createMock(RoleRepositoryInterface::class);
        $repository->method('getList')->willReturn($results);

        $builder = $this->createMock(SearchCriteriaBuilder::class);
        $builder->method('create')->willReturn($this->createMock(SearchCriteria::class));

        $resolver = new CompanyRoles($repository, $builder);
        $out = $resolver->resolve(
            $this->createMock(Field::class),
            null,
            $this->createMock(ResolveInfo::class),
            ['id' => 7]
        );

        $this->assertCount(2, $out);
        $this->assertSame(
            ['id' => 1, 'name' => 'Administrator', 'permissions' => '["all"]', 'is_admin_role' => true],
            $out[0]
        );
        $this->assertFalse($out[1]['is_admin_role']);
    }

    /**
     * @return RoleInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private function role(int $id, string $name, string $permissions)
    {
        $role = $this->createMock(RoleInterface::class);
        $role->method('getRoleId')->willReturn($id);
        $role->method('getRoleName')->willReturn($name);
        $role->method('getPermissions')->willReturn($permissions);

        return $role;
    }
}
