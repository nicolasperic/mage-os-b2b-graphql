# Contributing

Thanks for your interest in improving this module. It's a GraphQL layer for the
[Orangecat B2B suite](https://github.com/olivertar/m2_b2bsdk) and is intended as
a contribution to that community effort — so contributions that keep it aligned
with the suite's conventions are especially welcome.

## Ground rules

- Build on Orangecat's **public service contracts** (`Api/…` interfaces). Avoid
  reaching into its internal models or data layer where a contract exists.
- Keep GraphQL types close to the shape of Adobe Commerce's B2B schema where it
  makes sense, so the API feels familiar.
- Every resolver should have unit coverage (see `Test/Unit`).

## Development

This is a Magento 2 / Mage-OS module, so it runs inside a Magento project:

```bash
# from your Magento/Mage-OS root, with the Orangecat B2B suite installed
composer require nicolasperic/module-company-graphql   # or symlink into app/code
bin/magento module:enable Orangecat_CompanyGraphQl
bin/magento setup:upgrade
```

## Checks

Run these before opening a PR:

```bash
# Coding standard (from a checkout of this repo)
composer global require magento/magento-coding-standard
phpcs --standard=Magento2 --extensions=php Model registration.php Test

# Unit tests (from a Magento project that has this module installed)
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist \
  vendor/nicolasperic/module-company-graphql/Test/Unit
```

CI runs `composer validate`, PHP lint, and the Magento2 coding standard on every
push and pull request (errors fail the build; warnings are reported).

## Commit messages & PRs

- Keep commits focused and describe the *why*.
- Note any new GraphQL fields/mutations in `CHANGELOG.md`.
