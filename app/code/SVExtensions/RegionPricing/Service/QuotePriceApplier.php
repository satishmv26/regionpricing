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
            $this->logger->info('apply skipped: module disabled');
            return false;
        }

        $this->logger->info('apply entered', [
            'item_id' => $quoteItem->getId(),
            'sku' => $quoteItem->getSku(),
            'product_type' => $quoteItem->getProductType(),
            'has_children' => $quoteItem->getChildren() ? count($quoteItem->getChildren()) : 0,
        ]);

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

        $this->logger->info('apply completed', ['applied' => $applied]);

        return $applied;
    }

    /**
     * Apply regional effective price to a single quote item.
     */
    private function applyToItem(Item $quoteItem): bool
    {
        $product = $this->resolveProduct($quoteItem);
        if (!$product instanceof Product) {
            $this->logger->info('applyToItem skipped: no product', [
                'item_id' => $quoteItem->getId(),
                'sku' => $quoteItem->getSku(),
            ]);
            return false;
        }

        $productId = (int)$product->getId();
        if ($productId <= 0) {
            $this->logger->info('applyToItem skipped: invalid product ID', [
                'item_id' => $quoteItem->getId(),
                'sku' => $quoteItem->getSku(),
            ]);
            return false;
        }

        $this->logger->info('applyToItem entered', [
            'item_id' => $quoteItem->getId(),
            'sku' => $quoteItem->getSku(),
            'product_type' => $quoteItem->getProductType(),
            'resolved_product_id' => $productId,
            'resolved_product_sku' => $product->getSku(),
        ]);

        /*
         * Dynamic-price bundles: price is sum of selection prices.
         * Do not set a custom price on the bundle quote item.
         * Selections' individual regional prices are handled
         * by PricePlugin during totals collection.
         */
        if ($this->isDynamicBundle($product)) {
            $this->logger->info('applyToItem skipped: dynamic bundle');
            return false;
        }

        $effectivePrice = $this->regionalEffectivePriceResolver->resolve($product);
        $this->logger->info('applyToItem resolve result', [
            'effective_price' => $effectivePrice,
            'product_id' => $productId,
        ]);
        if ($effectivePrice === null) {
            $this->logger->info('applyToItem skipped: resolve returned null', [
                'product_id' => $productId,
            ]);
            return false;
        }

        /*
         * Re-apply catalog rule on the correct product(s) when the
         * resolved product for regional base pricing differs from
         * the product(s) used for catalog rule conditions.
         *
         * Two cases:
         *
         * 1) The quote item's actual product differs from the resolved
         *    product (e.g. simple child of configurable where resolveProduct
         *    fell back to the parent). Apply catalog rule on the child.
         *
         * 2) The quote item is a parent (configurable/grouped) with
         *    children. Apply catalog rule on each child's product and
         *    take the minimum effective price. This ensures the parent's
         *    custom price (which is what the cart displays) reflects
         *    the child's catalog rule conditions.
         *
         * In both cases, resolve() may have applied catalog rule on
         * the parent, which might not match the child's conditions
         * (SKU, category, etc.).
         */
        $children = $quoteItem->getChildren();
        if (!empty($children)) {
            $this->logger->info('applyToItem case 2: parent with children', [
                'child_count' => count($children),
            ]);
            $baseRegionalPrice = $this->regionalEffectivePriceResolver
                ->getRegionalBasePrice($product);
            $this->logger->info('applyToItem case 2: base regional price', [
                'base_regional_price' => $baseRegionalPrice,
                'product_id' => $productId,
            ]);
            $minChildPrice = null;
            foreach ($children as $i => $child) {
                $childProduct = $child->getProduct();
                $this->logger->info('applyToItem case 2: child', [
                    'index' => $i,
                    'child_item_id' => $child->getId(),
                    'child_sku' => $child->getSku(),
                    'child_product_id' => $childProduct instanceof Product ? (int)$childProduct->getId() : 'invalid',
                    'child_product_instanceof' => $childProduct instanceof Product ? 'yes' : 'no',
                ]);
                if ($childProduct instanceof Product
                    && (int)$childProduct->getId() > 0
                ) {
                    /*
                     * Use the child's own effective price (regional base
                     * + catalog rule on this child product). Fall back to
                     * parent's regional base with catalog rule on the
                     * child only when the child lacks a direct regional
                     * price.
                     */
                    $childPrice = $this->regionalEffectivePriceResolver
                        ->resolve($childProduct);
                    if ($childPrice === null && $baseRegionalPrice !== null) {
                        $childPrice = $this->regionalEffectivePriceResolver
                            ->applyCatalogueRule($childProduct, $baseRegionalPrice);
                    }
                    $this->logger->info('applyToItem case 2: child effective price', [
                        'child_price' => $childPrice,
                        'child_product_id' => (int)$childProduct->getId(),
                    ]);
                    if ($childPrice !== null
                        && ($minChildPrice === null || $childPrice < $minChildPrice)
                    ) {
                        $minChildPrice = $childPrice;
                    }
                } else {
                    $this->logger->info('applyToItem case 2: child product invalid, skipping', [
                        'child_item_id' => $child->getId(),
                    ]);
                }
            }
            if ($minChildPrice !== null) {
                $this->logger->info('applyToItem case 2: applying min child price', [
                    'before' => $effectivePrice,
                    'min_child' => $minChildPrice,
                    'after' => min($effectivePrice, $minChildPrice),
                ]);
                $effectivePrice = min($effectivePrice, $minChildPrice);
            } else {
                $this->logger->info('applyToItem case 2: no valid child effective price found');
            }
        } else {
            $actualProduct = $quoteItem->getProduct();
            $actualProductId = $actualProduct instanceof Product ? (int)$actualProduct->getId() : 0;
            $this->logger->info('applyToItem case 1: single product', [
                'actual_product_id' => $actualProductId,
                'resolved_product_id' => $productId,
                'ids_differ' => $actualProduct instanceof Product && $actualProductId > 0 && $actualProductId !== $productId ? 'yes' : 'no',
            ]);
            if ($actualProduct instanceof Product
                && (int)$actualProduct->getId() > 0
                && (int)$actualProduct->getId() !== $productId
            ) {
                /*
                 * The resolved product differs from the quote item's
                 * actual product (resolveProduct fell back to parent).
                 * Try the actual product's own effective price first,
                 * then fall back to parent's base with catalog rule
                 * on the actual product.
                 */
                $childEffective = $this->regionalEffectivePriceResolver
                    ->resolve($actualProduct);
                if ($childEffective === null) {
                    $baseRegionalPrice = $this->regionalEffectivePriceResolver
                        ->getRegionalBasePrice($product);
                    $this->logger->info('applyToItem case 1: fallback to parent base', [
                        'base_regional_price' => $baseRegionalPrice,
                    ]);
                    if ($baseRegionalPrice !== null) {
                        $childEffective = $this->regionalEffectivePriceResolver
                            ->applyCatalogueRule($actualProduct, $baseRegionalPrice);
                    } else {
                        $this->logger->info('applyToItem case 1: no base regional price available');
                    }
                }
                if ($childEffective !== null) {
                    $effectivePrice = $childEffective;
                }
                $this->logger->info('applyToItem case 1: result', [
                    'effective_price' => $effectivePrice,
                ]);
            }
        }

        $nativeFinalPrice = (float)$product->getFinalPrice();
        $this->logger->info('applyToItem: native final price', ['native' => $nativeFinalPrice]);
        $effectivePrice = min($effectivePrice, $nativeFinalPrice);
        $this->logger->info('applyToItem: final effective price after min', ['effective' => $effectivePrice]);

        $quoteItem->setCustomPrice($effectivePrice);
        $quoteItem->setOriginalCustomPrice($effectivePrice);
        $product->setIsSuperMode(true);

        $this->logger->info('applyToItem completed', [
            'item_id' => $quoteItem->getId(),
            'custom_price' => $effectivePrice,
        ]);

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
