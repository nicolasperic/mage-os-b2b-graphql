<?php
/**
 * This file is part of a proposed GraphQL companion for the Orangecat B2B suite.
 */

declare(strict_types=1);

namespace Orangecat\CompanyGraphQl\Model\Resolver;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Orangecat\Company\Api\CompanyManagementInterface;
use Orangecat\Company\Api\Data\RoleInterface;
use Orangecat\Company\Model\ResourceModel\CompanyCustomer\CollectionFactory;

/**
 * Resolves the `users` field on Company to the company's member roster.
 *
 * Access is scoped: company administrators see the full roster, while other
 * members see only their own entry. This keeps the team directory private to
 * admins without failing the query for regular members.
 */
class CompanyUsers implements ResolverInterface
{
    public function __construct(
        private readonly CollectionFactory $companyCustomerCollectionFactory,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly CompanyManagementInterface $companyManagement
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
        $companyId = isset($value['id']) ? (int)$value['id'] : 0;
        if (!$companyId) {
            return [];
        }

        $actingCustomerId = (int)$context->getUserId();

        $collection = $this->companyCustomerCollectionFactory->create()
            ->addFieldToFilter('company_id', $companyId);

        // Non-admins may only see themselves in the roster.
        if (!$this->companyManagement->isCompanyAdmin($actingCustomerId)) {
            $collection->addFieldToFilter('customer_id', $actingCustomerId);
        }

        $users = [];
        foreach ($collection as $link) {
            $customerId = (int)$link->getData('customer_id');
            $roleId = (int)$link->getData('role_id');

            $firstname = null;
            $lastname = null;
            $email = null;
            try {
                $customer = $this->customerRepository->getById($customerId);
                $firstname = $customer->getFirstname();
                $lastname = $customer->getLastname();
                $email = $customer->getEmail();
            } catch (NoSuchEntityException $e) {
                // Orphaned link (customer deleted) — still report the id.
            }

            $users[] = [
                'customer_id' => $customerId,
                'firstname' => $firstname,
                'lastname' => $lastname,
                'email' => $email,
                'role_id' => $roleId,
                'is_company_admin' => $roleId === RoleInterface::ADMIN_ROLE_ID,
            ];
        }

        return $users;
    }
}
