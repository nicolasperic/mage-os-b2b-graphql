# Changelog

All notable changes to this project are documented here. The format is based on
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project
adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- `company` query and `customer { company }` — the authenticated customer's B2B
  company (name, legal name, email, VAT/tax id, address, admin flag, role id).
- `company { users }` — the company member roster. Admins see all members;
  other members see only themselves.
- `company { roles }` — roles assignable to members (id, name, permissions,
  admin flag).
- Mutations `updateCompany`, `assignCompanyUser`, `removeCompanyUser` for company
  administrators, delegating to Orangecat's `CompanyManagementInterface` and
  `CompanyRepositoryInterface`.
- Unit tests for all resolvers and mutations.
- CI: `composer validate`, PHP lint, and the Magento2 coding standard.

[Unreleased]: https://github.com/nicolasperic/mage-os-b2b-graphql/commits/main
