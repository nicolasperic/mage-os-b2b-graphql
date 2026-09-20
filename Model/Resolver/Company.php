<?php
/**
 * This file is part of a proposed GraphQL companion for the Orangecat B2B suite.
 */

declare(strict_types=1);

namespace Orangecat\CompanyGraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Orangecat\Company\Api\CompanyManagementInterface;
use Orangecat\Company\Api\CompanyRepositoryInterface;
use Orangecat\CompanyGraphQl\Model\CompanyFormatter;

/**
 * Resolves the `company` query to the authenticated customer's B2B company.
 */
class Company implements ResolverInterface
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
        $customerId = $this->requireCustomerId($context);
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

        return $this->companyFormatter->format($company, $customerId);
    }
}
