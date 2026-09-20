<?php

declare(strict_types=1);

namespace Orangecat\CompanyGraphQl\Test\Unit\Model\Resolver;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Orangecat\Company\Api\CompanyManagementInterface;
use Orangecat\Company\Api\CompanyRepositoryInterface;
use Orangecat\Company\Api\Data\CompanyInterface;
use Orangecat\CompanyGraphQl\Model\CompanyFormatter;
use Orangecat\CompanyGraphQl\Model\Resolver\AssignCompanyUser;
use Orangecat\CompanyGraphQl\Model\Resolver\RemoveCompanyUser;
use Orangecat\CompanyGraphQl\Model\Resolver\UpdateCompany;
use PHPUnit\Framework\TestCase;

/**
 * Unit coverage for the company mutation resolvers: authorization, input
 * validation, and correct delegation to Orangecat's service contracts.
 */
class MutationsTest extends TestCase
{
    private CompanyManagementInterface $management;
    private CompanyRepositoryInterface $repository;
    private CompanyFormatter $formatter;

    protected function setUp(): void
    {
        $this->management = $this->createMock(CompanyManagementInterface::class);
        $this->repository = $this->createMock(CompanyRepositoryInterface::class);
        $this->formatter = new CompanyFormatter($this->management);
    }

    public function testUpdateCompanyRejectsNonAdmin(): void
    {
        $this->management->method('isCompanyAdmin')->willReturn(false);
        $resolver = new UpdateCompany($this->management, $this->repository, $this->formatter);

        $this->expectException(GraphQlAuthorizationException::class);
        $resolver->resolve($this->field(), $this->context(true, 1), $this->info(), null, ['input' => ['name' => 'X']]);
    }

    public function testUpdateCompanyRejectsEmptyInput(): void
    {
        $this->management->method('isCompanyAdmin')->willReturn(true);
        $resolver = new UpdateCompany($this->management, $this->repository, $this->formatter);

        $this->expectException(GraphQlInputException::class);
        $resolver->resolve($this->field(), $this->context(true, 1), $this->info(), null, ['input' => []]);
    }

    public function testUpdateCompanyAppliesOnlyProvidedFields(): void
    {
        $this->management->method('isCompanyAdmin')->willReturn(true);
        $this->management->method('getCompanyIdByCustomerId')->willReturn(7);
        $this->management->method('getRoleIdByCustomerId')->willReturn(1);

        $company = $this->createMock(CompanyInterface::class);
        $company->method('getId')->willReturn(7);
        // Only telephone is provided, so only setTelephone should be called.
        $company->expects($this->once())->method('setTelephone')->with('+1-555-0100');
        $company->expects($this->never())->method('setName');
        $this->repository->method('get')->willReturn($company);

        $resolver = new UpdateCompany($this->management, $this->repository, $this->formatter);
        $result = $resolver->resolve(
            $this->field(),
            $this->context(true, 1),
            $this->info(),
            null,
            ['input' => ['telephone' => '+1-555-0100']]
        );

        $this->assertSame(7, $result['id']);
    }

    public function testAssignRejectsCustomerInAnotherCompany(): void
    {
        $this->management->method('isCompanyAdmin')->willReturn(true);
        $this->management->method('getCompanyIdByCustomerId')->willReturnMap([
            [1, 7],   // admin -> company 7
            [99, 8],  // target already in company 8
        ]);
        $customerRepo = $this->createMock(CustomerRepositoryInterface::class);

        $resolver = new AssignCompanyUser($this->management, $this->repository, $customerRepo, $this->formatter);

        $this->expectException(GraphQlInputException::class);
        $resolver->resolve($this->field(), $this->context(true, 1), $this->info(), null, ['customer_id' => 99, 'role_id' => 3]);
    }

    public function testAssignCallsAssignCustomerWithAdminCompany(): void
    {
        $this->management->method('isCompanyAdmin')->willReturn(true);
        $this->management->method('getCompanyIdByCustomerId')->willReturnMap([
            [1, 7],
            [50, null], // new user, no company yet
        ]);
        $this->management->method('getRoleIdByCustomerId')->willReturn(1);
        $this->management->expects($this->once())
            ->method('assignCustomer')
            ->with(7, 50, 3);

        $customerRepo = $this->createMock(CustomerRepositoryInterface::class); // getById succeeds (no throw)
        $company = $this->createMock(CompanyInterface::class);
        $company->method('getId')->willReturn(7);
        $this->repository->method('get')->willReturn($company);

        $resolver = new AssignCompanyUser($this->management, $this->repository, $customerRepo, $this->formatter);
        $result = $resolver->resolve($this->field(), $this->context(true, 1), $this->info(), null, ['customer_id' => 50, 'role_id' => 3]);

        $this->assertSame(7, $result['id']);
    }

    public function testRemoveRejectsSelf(): void
    {
        $resolver = new RemoveCompanyUser($this->management, $this->repository, $this->formatter);

        $this->expectException(GraphQlInputException::class);
        $resolver->resolve($this->field(), $this->context(true, 5), $this->info(), null, ['customer_id' => 5]);
    }

    public function testRemoveDelegatesToRemoveCustomer(): void
    {
        $this->management->method('getCompanyIdByCustomerId')->willReturn(7);
        $this->management->method('getRoleIdByCustomerId')->willReturn(1);
        $this->management->expects($this->once())->method('validateManageUser')->with(1, 99);
        $this->management->expects($this->once())->method('removeCustomer')->with(99);

        $company = $this->createMock(CompanyInterface::class);
        $company->method('getId')->willReturn(7);
        $this->repository->method('get')->willReturn($company);

        $resolver = new RemoveCompanyUser($this->management, $this->repository, $this->formatter);
        $result = $resolver->resolve($this->field(), $this->context(true, 1), $this->info(), null, ['customer_id' => 99]);

        $this->assertSame(7, $result['id']);
    }

    private function field(): Field
    {
        return $this->createMock(Field::class);
    }

    private function info(): ResolveInfo
    {
        return $this->createMock(ResolveInfo::class);
    }

    private function context(bool $isCustomer, int $customerId): ContextInterface
    {
        $extension = new class ($isCustomer) {
            public function __construct(private bool $isCustomer)
            {
            }

            public function getIsCustomer(): bool
            {
                return $this->isCustomer;
            }
        };

        $context = $this->createMock(ContextInterface::class);
        $context->method('getExtensionAttributes')->willReturn($extension);
        $context->method('getUserId')->willReturn($customerId);

        return $context;
    }
}
