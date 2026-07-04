<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Test\Unit\Plugin\Catalog\Model\Product;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SVExtensions\RegionPricing\Helper\Logger;
use SVExtensions\RegionPricing\Model\PriceResolver;
use SVExtensions\RegionPricing\Plugin\Catalog\Model\Product\PricePlugin;

class PricePluginTest extends TestCase
{
    private PricePlugin $plugin;
    private MockObject|PriceResolver $priceResolverMock;
    private MockObject|Logger $loggerMock;
    private MockObject|State $appStateMock;

    protected function setUp(): void
    {
        $this->priceResolverMock = $this->createMock(PriceResolver::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->appStateMock = $this->createMock(State::class);

        $this->plugin = new PricePlugin(
            $this->priceResolverMock,
            $this->loggerMock,
            $this->appStateMock
        );
    }

    public function testReturnsNativePriceInAdminArea(): void
    {
        $this->appStateMock->method('getAreaCode')->willReturn(Area::AREA_ADMINHTML);
        $product = $this->createMock(Product::class);

        self::assertSame(50.0, $this->plugin->afterGetPrice($product, 50.0));
    }

    public function testReturnsNativePriceForConfigurableParent(): void
    {
        $this->appStateMock->method('getAreaCode')->willReturn(Area::AREA_FRONTEND);
        $product = $this->createMock(Product::class);
        $product->method('getTypeId')->willReturn('configurable');

        self::assertSame(50.0, $this->plugin->afterGetPrice($product, 50.0));
    }

    public function testReturnsNativePriceForGroupedParent(): void
    {
        $this->appStateMock->method('getAreaCode')->willReturn(Area::AREA_FRONTEND);
        $product = $this->createMock(Product::class);
        $product->method('getTypeId')->willReturn('grouped');

        self::assertSame(50.0, $this->plugin->afterGetPrice($product, 50.0));
    }

    public function testReturnsNativePriceForDynamicBundle(): void
    {
        $this->appStateMock->method('getAreaCode')->willReturn(Area::AREA_FRONTEND);
        $product = $this->createMock(Product::class);
        $product->method('getTypeId')->willReturn('bundle');

        self::assertSame(50.0, $this->plugin->afterGetPrice($product, 50.0));
    }

    public function testReplacesPriceWithRegionalPrice(): void
    {
        $this->appStateMock->method('getAreaCode')->willReturn(Area::AREA_FRONTEND);
        $product = $this->createMock(Product::class);
        $product->method('getTypeId')->willReturn('simple');
        $product->method('getId')->willReturn(1);

        $this->priceResolverMock->method('resolvePrice')->with(1)->willReturn(40.0);

        self::assertSame(40.0, $this->plugin->afterGetPrice($product, 50.0));
    }

    public function testReturnsNativePriceWhenNoRegionalPrice(): void
    {
        $this->appStateMock->method('getAreaCode')->willReturn(Area::AREA_FRONTEND);
        $product = $this->createMock(Product::class);
        $product->method('getTypeId')->willReturn('simple');
        $product->method('getId')->willReturn(1);

        $this->priceResolverMock->method('resolvePrice')->with(1)->willReturn(null);

        self::assertSame(50.0, $this->plugin->afterGetPrice($product, 50.0));
    }

    public function testWorksInWebapiRestArea(): void
    {
        $this->appStateMock->method('getAreaCode')->willReturn(Area::AREA_WEBAPI_REST);
        $product = $this->createMock(Product::class);
        $product->method('getTypeId')->willReturn('simple');
        $product->method('getId')->willReturn(1);

        $this->priceResolverMock->method('resolvePrice')->with(1)->willReturn(40.0);

        self::assertSame(40.0, $this->plugin->afterGetPrice($product, 50.0));
    }

    public function testWorksInGraphqlArea(): void
    {
        $this->appStateMock->method('getAreaCode')->willReturn('graphql');
        $product = $this->createMock(Product::class);
        $product->method('getTypeId')->willReturn('simple');
        $product->method('getId')->willReturn(1);

        $this->priceResolverMock->method('resolvePrice')->with(1)->willReturn(40.0);

        self::assertSame(40.0, $this->plugin->afterGetPrice($product, 50.0));
    }

    public function testReturnsNativePriceOnException(): void
    {
        $this->appStateMock->method('getAreaCode')->willReturn(Area::AREA_FRONTEND);
        $product = $this->createMock(Product::class);
        $product->method('getTypeId')->willReturn('simple');
        $product->method('getId')->willReturn(1);

        $this->priceResolverMock->method('resolvePrice')
            ->willThrowException(new \RuntimeException('DB failure'));

        $this->loggerMock->expects(self::once())->method('warning');

        self::assertSame(50.0, $this->plugin->afterGetPrice($product, 50.0));
    }

    public function testReturnsNativePriceWhenAreaNotAvailable(): void
    {
        $this->appStateMock->method('getAreaCode')
            ->willThrowException(new \RuntimeException('Area not set'));
        $product = $this->createMock(Product::class);

        self::assertSame(50.0, $this->plugin->afterGetPrice($product, 50.0));
    }
}
