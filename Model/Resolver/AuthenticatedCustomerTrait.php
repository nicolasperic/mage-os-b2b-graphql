<?php
/**
 * This file is part of a proposed GraphQL companion for the Orangecat B2B suite.
 */

declare(strict_types=1);

namespace Orangecat\CompanyGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;

/**
 * Shared helper for resolvers that require an authenticated customer.
 */
trait AuthenticatedCustomerTrait
{
    /**
     * Return the authenticated customer id, or fail for anonymous requests.
     *
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @return int The authenticated customer id.
     * @throws GraphQlAuthorizationException
     */
    private function requireCustomerId($context): int
    {
        if (!$context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(
                __('The current customer is not authorized. Please log in.')
            );
        }

        return (int)$context->getUserId();
    }
}
