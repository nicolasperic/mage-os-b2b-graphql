<?php
/**
 * This file is part of a proposed GraphQL companion for the Orangecat B2B suite.
 */

declare(strict_types=1);

namespace Orangecat\CompanyGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Orangecat\Company\Api\CompanyManagementInterface;
use Orangecat\Company\Api\CompanyRepositoryInterface;
use Orangecat\CompanyGraphQl\Model\CompanyFormatter;

/**
 * Mutation: update the authenticated admin's own company profile.
 *
 * Only provided (non-null) fields are changed; the company is always the
 * acting admin's, so a caller can never edit another company.
 */
class UpdateCompany implements ResolverInterface
{
    use AuthenticatedCustomerTrait;

    private const FIELD_SETTERS = [
        'name' => 'setName',
        'legal_name' => 'setNameLegal',
        'email' => 'setEmail',
        'vat_tax_id' => 'setTaxId',
        'address' => 'setAddress',
        'city' => 'setCity',
        'region' => 'setRegion',
        'postcode' => 'setPostalcode',
        'country_code' => 'setCountry',
        'telephone' => 'setTelephone',
    ];

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
        $customerId = $this->requireCustomerId($context);

        if (!$this->companyManagement->isCompanyAdmin($customerId)) {
            throw new GraphQlAuthorizationException(
                __('Only a company administrator can update the company.')
            );
        }

        $input = $args['input'] ?? [];
        if (!is_array($input) || $input === []) {
            throw new GraphQlInputException(__('Provide at least one field to update.'));
        }

        $companyId = (int)$this->companyManagement->getCompanyIdByCustomerId($customerId);
        $company = $this->companyRepository->get($companyId);

        foreach (self::FIELD_SETTERS as $key => $setter) {
            if (array_key_exists($key, $input) && $input[$key] !== null) {
                $company->{$setter}($input[$key]);
            }
        }

        $this->companyRepository->save($company);

        // Re-read to reflect any normalisation the repository applied.
        $company = $this->companyRepository->get($companyId);

        return $this->companyFormatter->format($company, $customerId);
    }
}
