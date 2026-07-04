<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Model;

use Magento\Catalog\Model\Product;
use Magento\CatalogRule\Model\Rule as CatalogRule;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedType;
use SVExtensions\RegionPricing\Helper\Logger;

class RegionalEffectivePriceResolver
{
    /**
     * @var array<int, float|null>  productId => cached result
     */
    private array $resolveCache = [];

    public function __construct(
        private readonly Config $config,
        private readonly PriceResolver $priceResolver,
        private readonly CatalogRule $catalogRule,
        private readonly Logger $logger
    ) {
    }

    /**
     * Get the effective regional price for a product.
     *
     * Returns:
     * - Regional base price + Catalog Rule discount when applicable
     * - Regional base price when no Catalog Rule applies
     * - Minimum child effective regional price for configurable/grouped
     *   parents when parent has no direct regional price
     * - null when module is disabled, no valid region, no regional price
     *   exists, or the product type derives price from children
     *   (dynamic bundle)
     */
    public function resolve(Product $product): ?float
    {
        if (!$this->config->isEnabled()) {
            return null;
        }

        $productId = (int)$product->getId();
        if ($productId <= 0) {
            return null;
        }

        if (array_key_exists($productId, $this->resolveCache)) {
            return $this->resolveCache[$productId];
        }

        /*
         * Dynamic bundle: price is sum of selection prices.
         * Delegate to children via PricePlugin. Do not resolve
         * a parent-level regional price here.
         */
        if ($this->isDynamicBundle($product)) {
            return $this->resolveCache[$productId] = null;
        }

        $regionalPrice = $this->priceResolver->resolvePrice($productId);

        /*
         * Parent without its own regional price:
         * resolve minimum effective price from children.
         */
        if ($regionalPrice === null) {
            return $this->resolveCache[$productId] = $this->resolveFromChildren($product);
        }

        return $this->resolveCache[$productId] = $this->applyCatalogueRule(
            $product,
            (float)$regionalPrice
        );
    }

    /**
     * Try to resolve effective price from children for parent
     * product types whose price is derived from children.
     */
    private function resolveFromChildren(Product $product): ?float
    {
        $typeId = $product->getTypeId();

        if ($typeId === 'configurable') {
            return $this->resolveChildren(
                $product,
                'getUsedProducts',
                ConfigurableType::class
            );
        }

        if ($typeId === 'grouped') {
            return $this->resolveChildren(
                $product,
                'getAssociatedProducts',
                GroupedType::class
            );
        }

        return null;
    }

    /**
     * Get minimum effective regional price across child products.
     */
    private function resolveChildren(
        Product $product,
        string $method,
        string $expectedType
    ): ?float {
        $typeInstance = $product->getTypeInstance();
        if (!$typeInstance instanceof $expectedType) {
            return null;
        }

        $children = $typeInstance->$method($product);
        if (empty($children)) {
            return null;
        }

        $childIds = array_map(
            static fn(Product $p) => (int)$p->getId(),
            $children
        );
        $regionalPrices = $this->priceResolver->resolvePrices($childIds);

        $minPrice = null;
        foreach ($children as $child) {
            $childId = (int)$child->getId();
            $regionalPrice = $regionalPrices[$childId] ?? null;
            if ($regionalPrice === null) {
                continue;
            }

            $childPrice = $this->applyCatalogueRule(
                $child,
                (float)$regionalPrice
            );
            if ($minPrice === null || $childPrice < $minPrice) {
                $minPrice = $childPrice;
            }
        }

        return $minPrice;
    }

    private function isDynamicBundle(Product $product): bool
    {
        return $product->getTypeId() === 'bundle'
            && (int)$product->getPriceType() === 0;
    }

    private function applyCatalogueRule(Product $product, float $basePrice): float
    {
        $catalogRulePrice = $this->catalogRule->calcProductPriceRule(
            $product,
            $basePrice
        );

        if ($catalogRulePrice !== null && $catalogRulePrice !== false) {
            return (float)$catalogRulePrice;
        }

        return $basePrice;
    }
}
