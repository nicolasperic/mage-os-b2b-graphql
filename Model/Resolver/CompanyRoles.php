<?php
/**
 * This file is part of a proposed GraphQL companion for the Orangecat B2B suite.
 */

declare(strict_types=1);

namespace Orangecat\CompanyGraphQl\Model\Resolver;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Orangecat\Company\Api\Data\RoleInterface;
use Orangecat\Company\Api\RoleRepositoryInterface;

/**
 * Resolves the `roles` field on Company to the roles that can be assigned to
 * members. Roles are global in Orangecat_Company (not per-company), so this
 * returns the full role list; it's exposed under Company for a natural
 * "which roles can I assign here?" flow alongside assignCompanyUser.
 */
class CompanyRoles implements ResolverInterface
{
    public function __construct(
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
    }

    /**
     * @inheritDoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ) {
        $roles = $this->roleRepository
            ->getList($this->searchCriteriaBuilder->create())
            ->getItems();

        $result = [];
        foreach ($roles as $role) {
            $result[] = [
                'id' => (int)$role->getRoleId(),
                'name' => $role->getRoleName(),
                'permissions' => $role->getPermissions(),
                'is_admin_role' => (int)$role->getRoleId() === RoleInterface::ADMIN_ROLE_ID,
            ];
        }

        return $result;
    }
}
