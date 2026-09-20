<?php
/**
 * This file is part of a proposed GraphQL companion for the Orangecat B2B suite.
 */

declare(strict_types=1);

namespace Orangecat\CompanyGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Orangecat\Company\Api\CompanyManagementInterface;
use Orangecat\Company\Api\CompanyRepositoryInterface;
use Orangecat\CompanyGraphQl\Model\CompanyFormatter;

/**
 * Mutation: remove a user from the admin's company.
 *
 * Delegates the admin + same-company check to Orangecat's validateManageUser,
 * and refuses to let an admin remove themselves.
 */
class RemoveCompanyUser implements ResolverInterface
{
    use AuthenticatedCustomerTrait;

    public function __construct(
        private readonly CompanyManagementInterface $companyManagement,
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly CompanyFormatter $companyFormatter
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
        $adminId = $this->requireCustomerId($context);
        $targetId = (int)($args['customer_id'] ?? 0);

        if ($targetId <= 0) {
            throw new GraphQlInputException(__('A valid customer_id is required.'));
        }
        if ($targetId === $adminId) {
            throw new GraphQlInputException(__('You cannot remove yourself from the company.'));
        }

        try {
            // Enforces: acting user is an admin, and target is in the same company.
            $this->companyManagement->validateManageUser($adminId, $targetId);
        } catch (LocalizedException $e) {
            throw new GraphQlAuthorizationException(__($e->getMessage()));
        }

        $this->companyManagement->removeCustomer($targetId);

        $companyId = (int)$this->companyManagement->getCompanyIdByCustomerId($adminId);
        $company = $this->companyRepository->get($companyId);

        return $this->companyFormatter->format($company, $adminId);
    }
}
