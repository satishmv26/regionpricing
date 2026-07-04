<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Service;

use Magento\Catalog\Model\Product;
use Magento\Quote\Model\Quote\Item;
use SVExtensions\RegionPricing\Helper\Logger;
use SVExtensions\RegionPricing\Model\Config;
use SVExtensions\RegionPricing\Model\RegionalEffectivePriceResolver;

class QuotePriceApplier
{
    public function __construct(
        private readonly Config $config,
        private readonly RegionalEffectivePriceResolver $regionalEffectivePriceResolver,
        private readonly Logger $logger
    ) {
    }

    /**
     * Apply regional effective price to a quote item and its children.
     *
     * For configurable/grouped products the event fires only for the
     * parent item, but Magento totals use the child item's price.
     * This method recurses into children to ensure both are priced.
     *
     * For child items whose product has no direct regional price,
     * falls back to the parent item's product resolution
     * (e.g. regional prices set on the configurable parent).
     *
     * Uses RegionalEffectivePriceResolver (regional base + Catalog Rule)
     * and compares with native final price for Tier/Special consistency.
     *
     * Returns true when regional price was applied to at least one item.
     */
    public function apply(Item $quoteItem): bool
    {
        if (!$this->config->isEnabled()) {
            return false;
        }

        $applied = $this->applyToItem($quoteItem);

        /*
         * Recurse into child items — the observer event fires only
         * for the parent, but totals use the child's price.
         */
        $children = $quoteItem->getChildren();
        if ($children) {
            foreach ($children as $child) {
                if ($this->applyToItem($child)) {
                    $applied = true;
                }
            }
        }

        return $applied;
    }

    /**
     * Apply regional effective price to a single quote item.
     */
    private function applyToItem(Item $quoteItem): bool
    {
        $product = $this->resolveProduct($quoteItem);
        if (!$product instanceof Product) {
            return false;
        }

        $productId = (int)$product->getId();
        if ($productId <= 0) {
            return false;
        }

        /*
         * Dynamic-price bundles: price is sum of selection prices.
         * Do not set a custom price on the bundle quote item.
         * Selections' individual regional prices are handled
         * by PricePlugin during totals collection.
         */
        if ($this->isDynamicBundle($product)) {
            return false;
        }

        $effectivePrice = $this->regionalEffectivePriceResolver->resolve($product);
        if ($effectivePrice === null) {
            return false;
        }

        $nativeFinalPrice = (float)$product->getFinalPrice();
        $effectivePrice = min($effectivePrice, $nativeFinalPrice);

        $quoteItem->setCustomPrice($effectivePrice);
        $quoteItem->setOriginalCustomPrice($effectivePrice);
        $product->setIsSuperMode(true);

        return true;
    }

    /**
     * Get the product to resolve price from.
     *
     * If the quote item's product has no direct regional price and
     * the item is a child (e.g. simple child of a configurable),
     * falls back to the parent item's product which may carry the
     * regional price.
     */
    private function resolveProduct(Item $quoteItem): ?Product
    {
        $product = $quoteItem->getProduct();
        if (!$product instanceof Product) {
            return null;
        }

        if ((int)$product->getId() <= 0) {
            return null;
        }

        /*
         * If the product has no regional price and the item has
         * a parent, try the parent's product (regional prices
         * may only be set on the configurable/grouped parent).
         */
        if ($this->regionalEffectivePriceResolver->resolve($product) === null) {
            $parentItem = $quoteItem->getParentItem();
            if ($parentItem instanceof Item) {
                $parentProduct = $parentItem->getProduct();
                if ($parentProduct instanceof Product
                    && (int)$parentProduct->getId() > 0
                    && $this->regionalEffectivePriceResolver->resolve($parentProduct) !== null
                ) {
                    return $parentProduct;
                }
            }
        }

        return $product;
    }

    private function isDynamicBundle(Product $product): bool
    {
        return $product->getTypeId() === 'bundle'
            && (int)$product->getPriceType() === 0;
    }
}
