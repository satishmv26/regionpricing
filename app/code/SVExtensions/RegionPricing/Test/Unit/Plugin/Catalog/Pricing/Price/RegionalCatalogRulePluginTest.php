<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Test\Unit\Plugin\Catalog\Pricing\Price;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SVExtensions\RegionPricing\Helper\Logger;
use SVExtensions\RegionPricing\Model\Config;
use SVExtensions\RegionPricing\Model\RegionalEffectivePriceResolver;
use SVExtensions\RegionPricing\Plugin\Catalog\Pricing\Price\RegionalCatalogRulePlugin;

class RegionalCatalogRulePluginTest extends TestCase
{
    private RegionalCatalogRulePlugin $plugin;
    private MockObject|Config $configMock;
    private MockObject|RegionalEffectivePriceResolver $resolverMock;
    private MockObject|Logger $loggerMock;
    private MockObject|State $appStateMock;
    private MockObject|FinalPrice $subjectMock;
    private MockObject|Product $productMock;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->resolverMock = $this->createMock(RegionalEffectivePriceResolver::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->appStateMock = $this->createMock(State::class);
        $this->productMock = $this->createMock(Product::class);
        $this->subjectMock = $this->createMock(FinalPrice::class);

        $this->subjectMock->method('getProduct')->willReturn($this->productMock);
        $this->productMock->method('getId')->willReturn(1);

        $this->plugin = new RegionalCatalogRulePlugin(
            $this->configMock,
            $this->resolverMock,
            $this->loggerMock,
            $this->appStateMock
        );
    }

    public function testReturnsNativeResultWhenDisabled(): void
    {
        $this->configMock->method('isEnabled')->willReturn(false);

        self::assertSame(50.0, $this->plugin->afterGetValue($this->subjectMock, 50.0));
    }

    public function testReturnsNativeResultInAdminArea(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $this->appStateMock->method('getAreaCode')->willReturn(Area::AREA_ADMINHTML);

        self::assertSame(50.0, $this->plugin->afterGetValue($this->subjectMock, 50.0));
    }

    public function testReturnsNativeResultInFrontend(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $this->appStateMock->method('getAreaCode')->willReturn(Area::AREA_FRONTEND);

        $this->resolverMock->method('resolve')->with($this->productMock)->willReturn(45.0);
        $this->productMock->method('getTypeId')->willReturn('simple');

        self::assertSame(45.0, $this->plugin->afterGetValue($this->subjectMock, 50.0));
    }

    public function testReturnsNativeWhenNoRegionalPrice(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $this->appStateMock->method('getAreaCode')->willReturn(Area::AREA_FRONTEND);
        $this->resolverMock->method('resolve')->willReturn(null);

        self::assertSame(50.0, $this->plugin->afterGetValue($this->subjectMock, 50.0));
    }

    public function testAppliesMinWithRegionalAndNativeFinal(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $this->appStateMock->method('getAreaCode')->willReturn(Area::AREA_FRONTEND);
        $this->resolverMock->method('resolve')->willReturn(45.0);
        $this->productMock->method('getTypeId')->willReturn('simple');

        self::assertSame(40.0, $this->plugin->afterGetValue($this->subjectMock, 40.0));
    }

    public function testSkipsMinForConfigurableParent(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $this->appStateMock->method('getAreaCode')->willReturn(Area::AREA_FRONTEND);
        $this->resolverMock->method('resolve')->willReturn(45.0);
        $this->productMock->method('getTypeId')->willReturn('configurable');

        self::assertSame(45.0, $this->plugin->afterGetValue($this->subjectMock, 0.0));
    }

    public function testSkipsMinForGroupedParent(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $this->appStateMock->method('getAreaCode')->willReturn(Area::AREA_FRONTEND);
        $this->resolverMock->method('resolve')->willReturn(45.0);
        $this->productMock->method('getTypeId')->willReturn('grouped');

        self::assertSame(45.0, $this->plugin->afterGetValue($this->subjectMock, 0.0));
    }

    public function testSkipsMinForDynamicBundle(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $this->appStateMock->method('getAreaCode')->willReturn(Area::AREA_FRONTEND);
        $this->resolverMock->method('resolve')->willReturn(45.0);
        $this->productMock->method('getTypeId')->willReturn('bundle');

        self::assertSame(45.0, $this->plugin->afterGetValue($this->subjectMock, 0.0));
    }

    public function testLogsWarningOnException(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $this->appStateMock->method('getAreaCode')->willReturn(Area::AREA_FRONTEND);
        $this->resolverMock->method('resolve')->willThrowException(new \RuntimeException('fail'));

        $this->loggerMock->expects(self::once())->method('warning');

        self::assertSame(50.0, $this->plugin->afterGetValue($this->subjectMock, 50.0));
    }
}
