<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Test\Unit\Model;

use Magento\Catalog\Model\Product;
use Magento\CatalogRule\Model\Rule as CatalogRule;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SVExtensions\RegionPricing\Helper\Logger;
use SVExtensions\RegionPricing\Model\Config;
use SVExtensions\RegionPricing\Model\PriceResolver;
use SVExtensions\RegionPricing\Model\RegionalEffectivePriceResolver;

class RegionalEffectivePriceResolverTest extends TestCase
{
    private RegionalEffectivePriceResolver $resolver;

    private MockObject|Config $configMock;
    private MockObject|PriceResolver $priceResolverMock;
    private MockObject|CatalogRule $catalogRuleMock;
    private MockObject|Logger $loggerMock;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->priceResolverMock = $this->createMock(PriceResolver::class);
        $this->catalogRuleMock = $this->createMock(CatalogRule::class);
        $this->loggerMock = $this->createMock(Logger::class);

        $this->resolver = new RegionalEffectivePriceResolver(
            $this->configMock,
            $this->priceResolverMock,
            $this->catalogRuleMock,
            $this->loggerMock
        );
    }

    public function testReturnsNullWhenModuleDisabled(): void
    {
        $this->configMock->method('isEnabled')->willReturn(false);

        $product = $this->createProduct(1, 'simple');

        self::assertNull($this->resolver->resolve($product));
    }

    public function testReturnsNullWhenProductIdIsZero(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);

        $product = $this->createProduct(0, 'simple');

        self::assertNull($this->resolver->resolve($product));
    }

    public function testReturnsNullForDynamicBundle(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);

        $product = $this->createProduct(1, 'bundle', ['getPriceType' => 0]);

        self::assertNull($this->resolver->resolve($product));
    }

    public function testReturnsNullWhenNoRegionalPrice(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $this->priceResolverMock->method('resolvePrice')->with(1)->willReturn(null);

        $product = $this->createProduct(1, 'simple');

        self::assertNull($this->resolver->resolve($product));
    }

    public function testReturnsRegionalBasePriceWhenNoCatalogRule(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $this->priceResolverMock->method('resolvePrice')->with(1)->willReturn(45.0);
        $this->catalogRuleMock->method('calcProductPriceRule')->with($this->anything(), 45.0)
            ->willReturn(false);

        $product = $this->createProduct(1, 'simple');

        self::assertSame(45.0, $this->resolver->resolve($product));
    }

    public function testAppliesCatalogRuleOnRegionalPrice(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $this->priceResolverMock->method('resolvePrice')->with(1)->willReturn(45.0);
        $this->catalogRuleMock->method('calcProductPriceRule')->with($this->anything(), 45.0)
            ->willReturn(40.0);

        $product = $this->createProduct(1, 'simple');

        self::assertSame(40.0, $this->resolver->resolve($product));
    }

    public function testMemoizationReturnsCachedValue(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $this->priceResolverMock->method('resolvePrice')->with(1)->willReturn(45.0);
        $this->catalogRuleMock->method('calcProductPriceRule')->with($this->anything(), 45.0)
            ->willReturn(40.0);

        $product = $this->createProduct(1, 'simple');

        $this->resolver->resolve($product);

        $this->priceResolverMock->expects(self::never())->method('resolvePrices');
        $this->catalogRuleMock->expects(self::never())->method('calcProductPriceRule');

        self::assertSame(40.0, $this->resolver->resolve($product));
    }

    public function testResolvesMinChildEffectivePriceForConfigurable(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $this->priceResolverMock->method('resolvePrice')->with(99)->willReturn(null);

        $children = [
            $this->createProduct(10, 'simple'),
            $this->createProduct(11, 'simple'),
        ];
        $this->priceResolverMock->method('resolvePrices')
            ->with([10, 11])
            ->willReturn([10 => 30.0, 11 => 50.0]);

        $this->catalogRuleMock->method('calcProductPriceRule')
            ->willReturnCallback(function ($product, $price) {
                return $price === 30.0 ? 25.0 : ($price === 50.0 ? 45.0 : $price);
            });

        $parent = $this->createProduct(99, 'configurable');
        $typeInstanceMock = $this->createMock(ConfigurableType::class);
        $typeInstanceMock->method('getUsedProducts')->with($parent)->willReturn($children);
        $parent->method('getTypeInstance')->willReturn($typeInstanceMock);

        self::assertSame(25.0, $this->resolver->resolve($parent));
    }

    public function testResolvesMinChildEffectivePriceForGrouped(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $this->priceResolverMock->method('resolvePrice')->with(99)->willReturn(null);

        $children = [
            $this->createProduct(20, 'simple'),
            $this->createProduct(21, 'simple'),
        ];
        $this->priceResolverMock->method('resolvePrices')
            ->with([20, 21])
            ->willReturn([20 => 15.0, 21 => null]);

        $this->catalogRuleMock->method('calcProductPriceRule')
            ->with($this->anything(), 15.0)
            ->willReturn(12.0);

        $parent = $this->createProduct(99, 'grouped');
        $typeInstanceMock = $this->createMock(GroupedType::class);
        $typeInstanceMock->method('getAssociatedProducts')->with($parent)->willReturn($children);
        $parent->method('getTypeInstance')->willReturn($typeInstanceMock);

        self::assertSame(12.0, $this->resolver->resolve($parent));
    }

    public function testReturnsNullWhenConfigurableHasNoChildrenWithRegionalPrice(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $this->priceResolverMock->method('resolvePrice')->with(99)->willReturn(null);

        $children = [$this->createProduct(10, 'simple')];
        $this->priceResolverMock->method('resolvePrices')->with([10])->willReturn([10 => null]);

        $parent = $this->createProduct(99, 'configurable');
        $typeInstanceMock = $this->createMock(ConfigurableType::class);
        $typeInstanceMock->method('getUsedProducts')->with($parent)->willReturn($children);
        $parent->method('getTypeInstance')->willReturn($typeInstanceMock);

        self::assertNull($this->resolver->resolve($parent));
    }

    public function testSkipsCatalogRuleForDynamicBundle(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);

        $product = $this->createProduct(1, 'bundle', ['getPriceType' => 0]);

        $this->priceResolverMock->expects(self::never())->method('resolvePrice');
        $this->catalogRuleMock->expects(self::never())->method('calcProductPriceRule');

        self::assertNull($this->resolver->resolve($product));
    }

    public function testGetRegionalBasePriceReturnsPriceWithoutCatalogRule(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $this->priceResolverMock->method('resolvePrice')->with(1)->willReturn(45.0);

        $product = $this->createProduct(1, 'simple');

        self::assertSame(45.0, $this->resolver->getRegionalBasePrice($product));
    }

    public function testGetRegionalBasePriceReturnsNullForDynamicBundle(): void
    {
        $product = $this->createProduct(1, 'bundle', ['getPriceType' => 0]);

        self::assertNull($this->resolver->getRegionalBasePrice($product));
    }

    public function testApplyCatalogueRuleAppliesRuleAndReturnsDiscountedPrice(): void
    {
        $this->catalogRuleMock->method('calcProductPriceRule')
            ->with($this->anything(), 50.0)
            ->willReturn(40.0);

        $product = $this->createProduct(1, 'simple');

        self::assertSame(40.0, $this->resolver->applyCatalogueRule($product, 50.0));
    }

    public function testApplyCatalogueRuleReturnsBasePriceWhenNoRule(): void
    {
        $this->catalogRuleMock->method('calcProductPriceRule')
            ->with($this->anything(), 50.0)
            ->willReturn(false);

        $product = $this->createProduct(1, 'simple');

        self::assertSame(50.0, $this->resolver->applyCatalogueRule($product, 50.0));
    }

    private function createProduct(int $id, string $typeId): MockObject|Product
    {
        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn($id);
        $product->method('getTypeId')->willReturn($typeId);
        return $product;
    }
}
