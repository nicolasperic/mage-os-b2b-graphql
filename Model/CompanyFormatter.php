<?php
/**
 * This file is part of a proposed GraphQL companion for the Orangecat B2B suite.
 */

declare(strict_types=1);

namespace Orangecat\CompanyGraphQl\Model;

use Orangecat\Company\Api\CompanyManagementInterface;
use Orangecat\Company\Api\Data\CompanyInterface;

/**
 * Maps an Orangecat CompanyInterface to the flat array shape the `Company`
 * GraphQL type expects, including the acting customer's role/admin flags.
 *
 * Centralised so every resolver (queries and mutations) returns an identical
 * company shape.
 */
class CompanyFormatter
{
    public function __construct(
        private readonly CompanyManagementInterface $companyManagement
    ) {
    }

    /**
     * @param CompanyInterface $company
     * @param int $customerId The authenticated customer, for role/admin context.
     * @return array
     */
    public function format(CompanyInterface $company, int $customerId): array
    {
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
