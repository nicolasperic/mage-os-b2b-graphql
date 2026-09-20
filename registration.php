<?php
/**
 * Orangecat_CompanyGraphQl — GraphQL coverage for Orangecat B2B Company.
 *
 * Proposed as a companion module / contribution to the Orangecat B2B suite
 * (https://github.com/olivertar). Adds a storefront GraphQL `company` query
 * on top of the existing Orangecat_Company service contracts.
 */

declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Orangecat_CompanyGraphQl',
    __DIR__
);
