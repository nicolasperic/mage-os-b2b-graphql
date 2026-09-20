<?php

declare(strict_types=1);

namespace Orangecat\CompanyGraphQl\Test\Unit\Model\Resolver;

use ArrayIterator;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\DataObject;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Orangecat\Company\Api\CompanyManagementInterface;
use Orangecat\Company\Model\ResourceModel\CompanyCustomer\Collection;
use Orangecat\Company\Model\ResourceModel\CompanyCustomer\CollectionFactory;
use Orangecat\CompanyGraphQl\Model\Resolver\CompanyUsers;
use PHPUnit\Framework\TestCase;

/**
 * Unit coverage for the CompanyUsers resolver, including the admin-only roster
 * gating.
 */
class CompanyUsersTest extends TestCase
{
    private CompanyManagementInterface $management;
    private CollectionFactory $collectionFactory;
    private Collection $collection;
    private CustomerRepositoryInterface $customerRepository;

    protected function setUp(): void
    {
        $this->management = $this->createMock(CompanyManagementInterface::class);
        $this->collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFieldToFilter', 'getIterator'])
            ->getMock();
        $this->collection->method('addFieldToFilter')->willReturnSelf();

        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $this->collectionFactory->method('create')->willReturn($this->collection);

        $this->customerRepository = $this->createMock(CustomerRepositoryInterface::class);
    }

    public function testAdminSeesFullRoster(): void
    {
        $this->management->method('isCompanyAdmin')->with(1)->willReturn(true);
        $this->collection->method('getIterator')->willReturn(new ArrayIterator([
            new DataObject(['customer_id' => 1, 'role_id' => 1]),
            new DataObject(['customer_id' => 2, 'role_id' => 3]),
        ]));
        $this->customerRepository->method('getById')->willReturnCallback(
            fn (int $id) => $this->customer($id)
        );

        $out = $this->resolve(1);

        $this->assertCount(2, $out);
        $this->assertTrue($out[0]['is_company_admin']);
        $this->assertFalse($out[1]['is_company_admin']);
    }

    public function testNonAdminSeesOnlySelf(): void
    {
        $this->management->method('isCompanyAdmin')->with(2)->willReturn(false);
        // The resolver must add a customer_id filter for non-admins.
        $this->collection->expects($this->exactly(2))->method('addFieldToFilter');
        $this->collection->method('getIterator')->willReturn(new ArrayIterator([
            new DataObject(['customer_id' => 2, 'role_id' => 3]),
        ]));
        $this->customerRepository->method('getById')->willReturnCallback(
            fn (int $id) => $this->customer($id)
        );

        $out = $this->resolve(2);

        $this->assertCount(1, $out);
        $this->assertSame(2, $out[0]['customer_id']);
    }

    private function resolve(int $actingCustomerId): array
    {
        $resolver = new CompanyUsers(
            $this->collectionFactory,
            $this->customerRepository,
            $this->management
        );

        $context = $this->createMock(ContextInterface::class);
        $context->method('getUserId')->willReturn($actingCustomerId);

        return $resolver->resolve(
            $this->createMock(Field::class),
            $context,
            $this->createMock(ResolveInfo::class),
            ['id' => 7]
        );
    }

    /**
     * @return CustomerInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private function customer(int $id)
    {
        $customer = $this->createMock(CustomerInterface::class);
        $customer->method('getFirstname')->willReturn('User' . $id);
        $customer->method('getLastname')->willReturn('Test');
        $customer->method('getEmail')->willReturn('user' . $id . '@example.com');

        return $customer;
    }
}
