<?php

declare(strict_types=1);

namespace Orangecat\CompanyGraphQl\Test\Unit\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Orangecat\Company\Api\CompanyManagementInterface;
use Orangecat\Company\Api\CompanyRepositoryInterface;
use Orangecat\Company\Api\Data\CompanyInterface;
use Orangecat\CompanyGraphQl\Model\CompanyFormatter;
use Orangecat\CompanyGraphQl\Model\Resolver\Company;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit coverage for the Company resolver.
 *
 * Mocks Orangecat's service contracts so the resolver's auth handling and field
 * mapping are verified without a database.
 */
class CompanyTest extends TestCase
{
    /** @var CompanyManagementInterface&MockObject */
    private $companyManagement;

    /** @var CompanyRepositoryInterface&MockObject */
    private $companyRepository;

    private Company $resolver;

    protected function setUp(): void
    {
        $this->companyManagement = $this->createMock(CompanyManagementInterface::class);
        $this->companyRepository = $this->createMock(CompanyRepositoryInterface::class);
        $this->resolver = new Company(
            $this->companyManagement,
            $this->companyRepository,
            new CompanyFormatter($this->companyManagement)
        );
    }

    public function testThrowsWhenNotAuthenticated(): void
    {
        $this->expectException(GraphQlAuthorizationException::class);
        $this->resolver->resolve(
            $this->createMock(Field::class),
            $this->contextWithCustomer(false, 0),
            $this->createMock(ResolveInfo::class)
        );
    }

    public function testReturnsNullWhenCustomerHasNoCompany(): void
    {
        $this->companyManagement->method('getCompanyIdByCustomerId')->with(42)->willReturn(null);

        $result = $this->resolver->resolve(
            $this->createMock(Field::class),
            $this->contextWithCustomer(true, 42),
            $this->createMock(ResolveInfo::class)
        );

        $this->assertNull($result);
    }

    public function testMapsCompanyFieldsForMember(): void
    {
        $this->companyManagement->method('getCompanyIdByCustomerId')->with(42)->willReturn(7);
        $this->companyManagement->method('isCompanyAdmin')->with(42)->willReturn(true);
        $this->companyManagement->method('getRoleIdByCustomerId')->with(42)->willReturn(1);

        $company = $this->createMock(CompanyInterface::class);
        $company->method('getId')->willReturn(7);
        $company->method('getName')->willReturn('Costello Industries');
        $company->method('getNameLegal')->willReturn('Costello Industries LLC');
        $company->method('getEmail')->willReturn('purchasing@costello.example');
        $company->method('getTaxId')->willReturn('US-VAT-99881');
        $company->method('getAddress')->willReturn('6146 Honey Bluff Parkway');
        $company->method('getCity')->willReturn('Calder');
        $company->method('getRegion')->willReturn('Michigan');
        $company->method('getPostalcode')->willReturn('49628-7978');
        $company->method('getCountry')->willReturn('US');
        $company->method('getTelephone')->willReturn('(555) 229-3326');
        $this->companyRepository->method('get')->with(7)->willReturn($company);

        $result = $this->resolver->resolve(
            $this->createMock(Field::class),
            $this->contextWithCustomer(true, 42),
            $this->createMock(ResolveInfo::class)
        );

        $this->assertSame(7, $result['id']);
        $this->assertSame('Costello Industries', $result['name']);
        $this->assertSame('Costello Industries LLC', $result['legal_name']);
        $this->assertSame('US-VAT-99881', $result['vat_tax_id']);
        $this->assertTrue($result['is_company_admin']);
        $this->assertSame(1, $result['role_id']);
    }

    /**
     * @return ContextInterface&MockObject
     */
    private function contextWithCustomer(bool $isCustomer, int $customerId)
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
