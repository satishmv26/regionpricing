<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Test\Unit\Model;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\ResourceConnection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SVExtensions\RegionPricing\Api\RegionRepositoryInterface;
use SVExtensions\RegionPricing\Helper\Logger;
use SVExtensions\RegionPricing\Model\Config;
use SVExtensions\RegionPricing\Model\RegionProvider;

class RegionProviderTest extends TestCase
{
    private RegionProvider $regionProvider;
    private MockObject|Config $configMock;
    private MockObject|RegionRepositoryInterface $regionRepoMock;
    private MockObject|CustomerSession $customerSessionMock;
    private MockObject|SearchCriteriaBuilder $searchCriteriaBuilderMock;
    private MockObject|Logger $loggerMock;
    private MockObject|ResourceConnection $resourceConnectionMock;
    private MockObject|EavConfig $eavConfigMock;
    private MockObject|HttpContext $httpContextMock;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->regionRepoMock = $this->createMock(RegionRepositoryInterface::class);
        $this->customerSessionMock = $this->createMock(CustomerSession::class);
        $this->searchCriteriaBuilderMock = $this->createMock(SearchCriteriaBuilder::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->resourceConnectionMock = $this->createMock(ResourceConnection::class);
        $this->eavConfigMock = $this->createMock(EavConfig::class);
        $this->httpContextMock = $this->createMock(HttpContext::class);

        $this->regionProvider = new RegionProvider(
            $this->configMock,
            $this->regionRepoMock,
            $this->customerSessionMock,
            $this->searchCriteriaBuilderMock,
            $this->loggerMock,
            $this->resourceConnectionMock,
            $this->eavConfigMock,
            $this->httpContextMock
        );
    }

    public function testReturnsNullWhenDisabled(): void
    {
        $this->configMock->method('isEnabled')->willReturn(false);
        self::assertNull($this->regionProvider->getCurrentRegionId());
    }

    public function testReturnsRegionIdFromHttpContext(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $this->httpContextMock->method('getValue')
            ->with(RegionProvider::CONTEXT_REGION_ID)
            ->willReturn('7');

        self::assertSame(7, $this->regionProvider->getCurrentRegionId());
    }

    public function testReturnsNullWhenHttpContextIsEmptyAndNotLoggedIn(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $this->httpContextMock->method('getValue')
            ->with(RegionProvider::CONTEXT_REGION_ID)
            ->willReturn('0');
        $this->customerSessionMock->method('isLoggedIn')->willReturn(false);

        self::assertNull($this->regionProvider->getCurrentRegionId());
    }

    public function testFallsBackToCustomerAttributeWhenHttpContextIsEmpty(): void
    {
        $customerMock = $this->createMock(Customer::class);
        $customerMock->method('getData')
            ->with(RegionProvider::ATTRIBUTE_CODE)
            ->willReturn('12');

        $this->configMock->method('isEnabled')->willReturn(true);
        $this->httpContextMock->method('getValue')
            ->with(RegionProvider::CONTEXT_REGION_ID)
            ->willReturn('0');
        $this->customerSessionMock->method('isLoggedIn')->willReturn(true);
        $this->customerSessionMock->method('getCustomer')->willReturn($customerMock);

        self::assertSame(12, $this->regionProvider->getCurrentRegionId());
    }

    public function testReturnsNullWhenCustomerAttributeIsNotSet(): void
    {
        $customerMock = $this->createMock(Customer::class);
        $customerMock->method('getData')
            ->with(RegionProvider::ATTRIBUTE_CODE)
            ->willReturn(null);

        $this->configMock->method('isEnabled')->willReturn(true);
        $this->httpContextMock->method('getValue')
            ->with(RegionProvider::CONTEXT_REGION_ID)
            ->willReturn('0');
        $this->customerSessionMock->method('isLoggedIn')->willReturn(true);
        $this->customerSessionMock->method('getCustomer')->willReturn($customerMock);

        self::assertNull($this->regionProvider->getCurrentRegionId());
    }
}
