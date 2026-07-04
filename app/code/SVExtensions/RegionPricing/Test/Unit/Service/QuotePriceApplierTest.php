<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Test\Unit\Service;

use Magento\Catalog\Model\Product;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SVExtensions\RegionPricing\Helper\Logger;
use SVExtensions\RegionPricing\Model\Config;
use SVExtensions\RegionPricing\Model\RegionalEffectivePriceResolver;
use SVExtensions\RegionPricing\Service\QuotePriceApplier;

class QuotePriceApplierTest extends TestCase
{
    private QuotePriceApplier $applier;
    private MockObject|Config $configMock;
    private MockObject|RegionalEffectivePriceResolver $resolverMock;
    private MockObject|Logger $loggerMock;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->resolverMock = $this->createMock(RegionalEffectivePriceResolver::class);
        $this->loggerMock = $this->createMock(Logger::class);

        $this->applier = new QuotePriceApplier(
            $this->configMock,
            $this->resolverMock,
            $this->loggerMock
        );
    }

    private function createItem(array $realMethods = ['getProduct', 'getChildren', 'setCustomPrice', 'getParentItem'], array $magicMethods = []): MockObject|Item
    {
        $builder = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods($realMethods);

        if ($magicMethods) {
            $builder->addMethods($magicMethods);
        }

        return $builder->getMock();
    }

    public function testReturnsFalseWhenDisabled(): void
    {
        $this->configMock->method('isEnabled')->willReturn(false);
        $item = $this->createItem();

        self::assertFalse($this->applier->apply($item));
    }

    public function testReturnsFalseWhenProductIsNull(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $item = $this->createItem();
        $item->method('getProduct')->willReturn(null);

        self::assertFalse($this->applier->apply($item));
    }

    public function testReturnsFalseForDynamicBundle(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(1);
        $product->method('getTypeId')->willReturn('bundle');

        $item = $this->createItem();
        $item->method('getProduct')->willReturn($product);
        $item->method('getChildren')->willReturn(null);

        $this->configMock->method('isEnabled')->willReturn(true);

        self::assertFalse($this->applier->apply($item));
    }

    public function testReturnsFalseWhenNoRegionalPrice(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(1);
        $product->method('getTypeId')->willReturn('simple');

        $item = $this->createItem();
        $item->method('getProduct')->willReturn($product);
        $item->method('getChildren')->willReturn(null);

        $this->configMock->method('isEnabled')->willReturn(true);
        $this->resolverMock->method('resolve')->with($product)->willReturn(null);

        self::assertFalse($this->applier->apply($item));
    }

    public function testSetsCustomPriceOnSimpleProduct(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(1);
        $product->method('getTypeId')->willReturn('simple');
        $product->method('getFinalPrice')->willReturn(50.0);

        $item = $this->createItem(
            ['getProduct', 'getChildren', 'setCustomPrice', 'getParentItem'],
            ['setOriginalCustomPrice']
        );
        $item->method('getProduct')->willReturn($product);
        $item->method('getChildren')->willReturn(null);

        $this->configMock->method('isEnabled')->willReturn(true);
        $this->resolverMock->method('resolve')->with($product)->willReturn(45.0);

        $item->expects(self::once())->method('setCustomPrice')->with(45.0);
        $item->expects(self::once())->method('setOriginalCustomPrice')->with(45.0);

        self::assertTrue($this->applier->apply($item));
    }

    public function testAppliesMinWithNativeFinalPrice(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(1);
        $product->method('getTypeId')->willReturn('simple');
        $product->method('getFinalPrice')->willReturn(40.0);

        $item = $this->createItem(
            ['getProduct', 'getChildren', 'setCustomPrice', 'getParentItem'],
            ['setOriginalCustomPrice']
        );
        $item->method('getProduct')->willReturn($product);
        $item->method('getChildren')->willReturn(null);

        $this->configMock->method('isEnabled')->willReturn(true);
        $this->resolverMock->method('resolve')->with($product)->willReturn(45.0);

        $item->expects(self::once())->method('setCustomPrice')->with(40.0);
        $item->expects(self::once())->method('setOriginalCustomPrice')->with(40.0);

        self::assertTrue($this->applier->apply($item));
    }

    public function testRecursesIntoChildren(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(1);
        $product->method('getTypeId')->willReturn('configurable');
        $product->method('getFinalPrice')->willReturn(50.0);

        $childProduct = $this->createMock(Product::class);
        $childProduct->method('getId')->willReturn(10);
        $childProduct->method('getTypeId')->willReturn('simple');
        $childProduct->method('getFinalPrice')->willReturn(50.0);

        $childItem = $this->createItem(
            ['getProduct', 'getChildren', 'setCustomPrice', 'getParentItem'],
            ['setOriginalCustomPrice']
        );
        $childItem->method('getProduct')->willReturn($childProduct);

        $item = $this->createItem();
        $item->method('getProduct')->willReturn($product);
        $item->method('getChildren')->willReturn([$childItem]);

        $this->configMock->method('isEnabled')->willReturn(true);

        $this->resolverMock->method('resolve')
            ->willReturnCallback(function ($p) {
                $id = (int)$p->getId();
                return $id === 1 ? 45.0 : ($id === 10 ? 35.0 : null);
            });

        $childItem->expects(self::once())->method('setCustomPrice');
        $childItem->expects(self::once())->method('setOriginalCustomPrice');

        $result = $this->applier->apply($item);
        self::assertTrue($result);
    }

    public function testChildItemWithoutRegionalPriceFallsBackToParent(): void
    {
        $parentProduct = $this->createMock(Product::class);
        $parentProduct->method('getId')->willReturn(1);
        $parentProduct->method('getTypeId')->willReturn('configurable');
        $parentProduct->method('getFinalPrice')->willReturn(50.0);

        $childProduct = $this->createMock(Product::class);
        $childProduct->method('getId')->willReturn(10);
        $childProduct->method('getTypeId')->willReturn('simple');

        $parentItem = $this->createItem();
        $parentItem->method('getProduct')->willReturn($parentProduct);
        $parentItem->method('getChildren')->willReturn([]);

        $this->configMock->method('isEnabled')->willReturn(true);

        $this->resolverMock->method('resolve')
            ->willReturnCallback(function ($p) use ($parentProduct, $childProduct) {
                if ($p === $parentProduct) {
                    return 45.0;
                }
                if ($p === $childProduct) {
                    return null;
                }
                return null;
            });

        /*
         * Create child item that falls back to parent product
         * when its own product has no regional price.
         */
        $childItem = $this->createItem(
            ['getProduct', 'getChildren', 'setCustomPrice', 'getParentItem'],
            ['setOriginalCustomPrice']
        );
        $childItem->method('getProduct')->willReturn($childProduct);
        $childItem->method('getParentItem')->willReturn($parentItem);

        $childItem->expects(self::once())->method('setCustomPrice')->with(45.0);
        $childItem->expects(self::once())->method('setOriginalCustomPrice')->with(45.0);

        /* Re-configure parent to include child */
        $parentItem = $this->createItem();
        $parentItem->method('getProduct')->willReturn($parentProduct);
        $parentItem->method('getChildren')->willReturn([$childItem]);

        $this->applier->apply($parentItem);
    }

    public function testReturnsTrueWhenChildrenHaveNoRegionalPrice(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(1);
        $product->method('getTypeId')->willReturn('configurable');
        $product->method('getFinalPrice')->willReturn(50.0);

        $childProduct = $this->createMock(Product::class);
        $childProduct->method('getId')->willReturn(10);
        $childProduct->method('getTypeId')->willReturn('simple');

        $childItem = $this->createItem();
        $childItem->method('getProduct')->willReturn($childProduct);
        $childItem->method('getParentItem')->willReturn(null);

        $item = $this->createItem();
        $item->method('getProduct')->willReturn($product);
        $item->method('getChildren')->willReturn([$childItem]);

        $this->configMock->method('isEnabled')->willReturn(true);

        $this->resolverMock->method('resolve')->willReturn(45.0);

        self::assertTrue($this->applier->apply($item));
    }
}
