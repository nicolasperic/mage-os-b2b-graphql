<?php
/**
 * This file is part of a proposed GraphQL companion for the Orangecat B2B suite.
 */

declare(strict_types=1);

namespace Orangecat\CompanyGraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Orangecat\Company\Api\CompanyManagementInterface;
use Orangecat\Company\Api\CompanyRepositoryInterface;

/**
 * Resolves the `company` query to the authenticated customer's B2B company.
 *
 * Reuses the existing Orangecat_Company service contracts rather than touching
 * the data layer, so it stays valid as the module evolves.
 */
class Company implements ResolverInterface
{
    public function __construct(
        private readonly CompanyManagementInterface $companyManagement,
        private readonly CompanyRepositoryInterface $companyRepository
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
        if (!$context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(
                __('The current customer is not authorized. Please log in.')
            );
        }

        $customerId = (int)$context->getUserId();
        $companyId = $this->companyManagement->getCompanyIdByCustomerId($customerId);

        if (!$companyId) {
            // Authenticated, but not part of any company — a valid, empty answer.
            return null;
        }

        try {
            $company = $this->companyRepository->get($companyId);
        } catch (NoSuchEntityException $e) {
            return null;
        }

        return [
            'id' => (int)$company->getId(),
            'name' => $company->getName(),
            'legal_name' => $company->getNameLegal(),
            'email' => $company->getEmail(),
            'vat_tax_id' => $company->getTaxId(),
            'address' => $company->getAddress(),
            'city' => $company->getCity(),
            'region' => $company->getRegion(),
            'postcode' => $company->getPostalcode(),
            'country_code' => $company->getCountry(),
            'telephone' => $company->getTelephone(),
            'is_company_admin' => $this->companyManagement->isCompanyAdmin($customerId),
            'role_id' => (int)$this->companyManagement->getRoleIdByCustomerId($customerId),
        ];
    }
}
