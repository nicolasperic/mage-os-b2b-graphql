# mage-os-b2b-graphql (`Orangecat_CompanyGraphQl`)

GraphQL coverage for the [Orangecat B2B suite](https://github.com/olivertar/m2_b2bsdk)'s
`Orangecat_Company` module. It adds a storefront **`company` GraphQL query** so
headless/PWA frontends — and AI agents (e.g. via [mage-os-mcp](https://github.com/nicolasperic/mage-os-mcp)) —
can read a customer's B2B company over GraphQL, which the suite otherwise exposes
only through admin/REST.

> Proposed as a companion module / contribution to the Orangecat B2B suite.
> Built entirely on Orangecat's public service contracts — no changes to their
> data layer.

## Why

The Orangecat suite is a genuinely open-source (OSL-3.0) B2B foundation for
Magento / Mage-OS, but it has **no GraphQL** yet. Modern Mage-OS storefronts,
PWA Studio / Hyvä-headless, and AI agents are all GraphQL-first. This module
closes that gap for the Company entity, mirroring the shape of Adobe Commerce's
B2B `company` query where practical.

## What it adds

```graphql
{
  company {                 # the authenticated customer's company
    id
    name
    legal_name
    email
    vat_tax_id
    address city region postcode country_code telephone
    is_company_admin        # is the current customer an admin of this company?
    role_id                 # the current customer's role within the company
    users {                 # team roster
      customer_id firstname lastname email role_id is_company_admin
    }
  }
}

# also exposed on the customer object:
{ customer { firstname company { name } } }
```

Both entry points require an authenticated customer (send an
`Authorization: Bearer <customer token>` header). An authenticated customer who
belongs to no company gets `company: null`; an unauthenticated request is
rejected with an authorization error.

## Requirements

- Magento 2 / Mage-OS with the Orangecat B2B suite installed
  (`orangecat/module-company`)
- PHP >= 8.1

## Install

```bash
composer require nicolasperic/module-company-graphql
bin/magento module:enable Orangecat_CompanyGraphQl
bin/magento setup:upgrade
bin/magento cache:flush
```

## Testing

Unit tests live under `Test/Unit`. They mock Orangecat's service contracts, so
they exercise the resolver logic in isolation. Run them from a Magento project
that has this module installed:

```bash
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist \
  vendor/nicolasperic/module-company-graphql/Test/Unit
```

CI (GitHub Actions) runs `composer validate` and PHP lint on every push/PR.

## License

[OSL-3.0](LICENSE) — matching the Orangecat suite it extends.
