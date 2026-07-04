<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Test\Unit\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SVExtensions\RegionPricing\Api\ProductRegionPriceRepositoryInterface;
use SVExtensions\RegionPricing\Api\RegionRepositoryInterface;
use SVExtensions\RegionPricing\Model\Config;
use SVExtensions\RegionPricing\Model\PriceResolver;
use SVExtensions\RegionPricing\Model\RegionProvider;

class PriceResolverTest extends TestCase
{
    private PriceResolver $resolver;
    private MockObject|Config $configMock;
    private MockObject|RegionProvider $regionProviderMock;
    private MockObject|ProductRegionPriceRepositoryInterface $priceRepoMock;
    private MockObject|ProductRepositoryInterface $productRepoMock;
    private MockObject|RegionRepositoryInterface $regionRepoMock;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->regionProviderMock = $this->createMock(RegionProvider::class);
        $this->priceRepoMock = $this->createMock(ProductRegionPriceRepositoryInterface::class);
        $this->productRepoMock = $this->createMock(ProductRepositoryInterface::class);
        $this->regionRepoMock = $this->createMock(RegionRepositoryInterface::class);

        $this->resolver = new PriceResolver(
            $this->configMock,
            $this->regionProviderMock,
            $this->priceRepoMock,
            $this->productRepoMock,
            $this->regionRepoMock
        );
    }

    protected function tearDown(): void
    {
        $this->resolver->clearCache();
    }

    public function testResolvePriceReturnsNullWhenDisabled(): void
    {
        $this->configMock->method('isEnabled')->willReturn(false);
        self::assertNull($this->resolver->resolvePrice(1));
    }

    public function testResolvePriceReturnsNullWhenNoRegion(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $this->regionProviderMock->method('getCurrentRegionId')->willReturn(null);
        self::assertNull($this->resolver->resolvePrice(1));
    }

    public function testResolvePriceReturnsFloatFromRepository(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $this->regionProviderMock->method('getCurrentRegionId')->willReturn(5);
        $this->priceRepoMock->method('getPrice')->with(1, 5)->willReturn(45.0);

        self::assertSame(45.0, $this->resolver->resolvePrice(1));
    }

    public function testResolvePriceReturnsNullWhenNoPriceInRepo(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $this->regionProviderMock->method('getCurrentRegionId')->willReturn(5);
        $this->priceRepoMock->method('getPrice')->with(1, 5)->willReturn(null);

        self::assertNull($this->resolver->resolvePrice(1));
    }

    public function testResolvePriceCachesResult(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $this->regionProviderMock->method('getCurrentRegionId')->willReturn(5);
        $this->priceRepoMock->expects(self::once())->method('getPrice')
            ->with(1, 5)->willReturn(45.0);

        $this->resolver->resolvePrice(1);
        $this->resolver->resolvePrice(1);
        $this->resolver->resolvePrice(1);
    }

    public function testResolvePricesBatchSingleQuery(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $this->regionProviderMock->method('getCurrentRegionId')->willReturn(5);
        $this->priceRepoMock->expects(self::once())->method('getPricesByProductIds')
            ->with([1, 2], 5)->willReturn([1 => 10.0, 2 => 20.0]);

        $result = $this->resolver->resolvePrices([1, 2]);
        self::assertSame([1 => 10.0, 2 => 20.0], $result);
    }

    public function testResolvePricesReturnsNullsWhenDisabled(): void
    {
        $this->configMock->method('isEnabled')->willReturn(false);
        $result = $this->resolver->resolvePrices([1, 2]);
        self::assertSame([1 => null, 2 => null], $result);
    }

    public function testResolvePriceBySkuReturnsNullForUnknownSku(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $this->productRepoMock->method('get')->with('unknown')
            ->willThrowException(new NoSuchEntityException());

        self::assertNull($this->resolver->resolvePriceBySku('unknown'));
    }

    public function testResolvePriceBySkuReturnsPriceForValidSku(): void
    {
        $productMock = $this->createMock(\Magento\Catalog\Model\Product::class);
        $productMock->method('getId')->willReturn(1);

        $this->configMock->method('isEnabled')->willReturn(true);
        $this->regionProviderMock->method('getCurrentRegionId')->willReturn(5);
        $this->productRepoMock->method('get')->with('test-sku')->willReturn($productMock);
        $this->priceRepoMock->method('getPrice')->with(1, 5)->willReturn(30.0);

        self::assertSame(30.0, $this->resolver->resolvePriceBySku('test-sku'));
    }
}
