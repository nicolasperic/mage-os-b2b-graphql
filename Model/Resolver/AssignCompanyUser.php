<?php
/**
 * This file is part of a proposed GraphQL companion for the Orangecat B2B suite.
 */

declare(strict_types=1);

namespace Orangecat\CompanyGraphQl\Model\Resolver;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Orangecat\Company\Api\CompanyManagementInterface;
use Orangecat\Company\Api\CompanyRepositoryInterface;
use Orangecat\CompanyGraphQl\Model\CompanyFormatter;

/**
 * Mutation: add an existing customer to the admin's company, or change the role
 * of a customer already in it (Orangecat's assignCustomer upserts the link).
 *
 * The company is always the acting admin's, and a customer who already belongs
 * to a *different* company is rejected — so this can't poach users.
 */
class AssignCompanyUser implements ResolverInterface
{
    use AuthenticatedCustomerTrait;

    public function __construct(
        private readonly CompanyManagementInterface $companyManagement,
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
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

        if (!$this->companyManagement->isCompanyAdmin($adminId)) {
            throw new GraphQlAuthorizationException(
                __('Only a company administrator can manage users.')
            );
        }

        $targetId = (int)($args['customer_id'] ?? 0);
        $roleId = (int)($args['role_id'] ?? 0);
        if ($targetId <= 0 || $roleId <= 0) {
            throw new GraphQlInputException(__('A valid customer_id and role_id are required.'));
        }

        // The target must be a real customer.
        try {
            $this->customerRepository->getById($targetId);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlInputException(__('No customer with id %1 exists.', $targetId));
        }

        $adminCompanyId = (int)$this->companyManagement->getCompanyIdByCustomerId($adminId);
        $targetCompanyId = $this->companyManagement->getCompanyIdByCustomerId($targetId);

        if ($targetCompanyId !== null && (int)$targetCompanyId !== $adminCompanyId) {
            throw new GraphQlInputException(__('That customer already belongs to another company.'));
        }

        try {
            $this->companyManagement->assignCustomer($adminCompanyId, $targetId, $roleId);
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }

        $company = $this->companyRepository->get($adminCompanyId);

        return $this->companyFormatter->format($company, $adminId);
    }
}
