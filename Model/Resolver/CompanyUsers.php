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
use Orangecat\Company\Api\Data\RoleInterface;
use Orangecat\Company\Model\ResourceModel\CompanyCustomer\CollectionFactory;

/**
 * Resolves the `users` field on Company to the company's member roster.
 *
 * Reads the parent-resolved company id, then joins the Orangecat company↔customer
 * links with core customer records for names and emails.
 */
class CompanyUsers implements ResolverInterface
{
    public function __construct(
        private readonly CollectionFactory $companyCustomerCollectionFactory,
        private readonly CustomerRepositoryInterface $customerRepository
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

        $links = $this->companyCustomerCollectionFactory->create()
            ->addFieldToFilter('company_id', $companyId);

        $users = [];
        foreach ($links as $link) {
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
