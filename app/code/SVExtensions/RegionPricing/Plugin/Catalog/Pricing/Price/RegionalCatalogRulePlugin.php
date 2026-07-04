<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Plugin\Catalog\Pricing\Price;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use SVExtensions\RegionPricing\Helper\Logger;
use SVExtensions\RegionPricing\Model\Config;
use SVExtensions\RegionPricing\Model\RegionalEffectivePriceResolver;

class RegionalCatalogRulePlugin
{
    public function __construct(
        private readonly Config $config,
        private readonly RegionalEffectivePriceResolver $regionalEffectivePriceResolver,
        private readonly Logger $logger,
        private readonly State $appState
    ) {
    }

    /**
     * Apply Catalog Rule on top of Regional Base Price.
     *
     * Delegates effective price calculation to
     * RegionalEffectivePriceResolver (single source of truth).
     *
     * Then chooses the lower of:
     *   effective regional price (regional base + Catalog Rule)
     *   vs
     *   native final price (which may include Tier/Special pricing)
     *
     * Configurable parent: native result is 0 (parent has no price).
     * Use the child-based effective regional price directly.
     *
     * Example:
     *
     * Magento Base Price: 50
     * Regional Price:     45
     * Catalog Rule:       -2
     *
     * Regular Price:      45
     * Final Price:        43
     */
    public function afterGetValue(
        FinalPrice $subject,
        float $result
    ): float {
        if (!$this->config->isEnabled()) {
            return $result;
        }

        if ($this->isAdminArea()) {
            return $result;
        }

        try {
            $product = $subject->getProduct();

            if (!$product instanceof Product) {
                return $result;
            }

            $productId = (int)$product->getId();

            if ($productId <= 0) {
                return $result;
            }

            $effectiveRegionalPrice = $this->regionalEffectivePriceResolver
                ->resolve($product);

            /*
             * No regional price:
             * preserve complete native Magento final price.
             */
            if ($effectiveRegionalPrice === null) {
                return $result;
            }

            /*
             * Parent product types whose native final price is 0
             * (getPrice() = 0) — use effective regional price directly.
             *
             * Configurable & grouped: price from children.
             * Dynamic bundle: price sum of selections.
             * Fixed-priced bundles have their own price; skip here.
             */
            $skipMin = $product->getTypeId() === 'configurable'
                || $product->getTypeId() === 'grouped'
                || (
                    $product->getTypeId() === 'bundle'
                    && (int)$product->getPriceType() === 0
                );

            if ($skipMin) {
                return $effectiveRegionalPrice;
            }

            /*
             * Choose lowest applicable final price.
             *
             * Regional effective price (regional base + Catalog Rule)
             * vs native final price (may include Tier/Special pricing).
             */
            return min(
                $effectiveRegionalPrice,
                $result
            );

        } catch (\Throwable $exception) {
            $this->logger->warning(
                'Regional Catalog Rule calculation failed.',
                [
                    'error' => $exception->getMessage()
                ]
            );

            return $result;
        }
    }

    private function isAdminArea(): bool
    {
        try {
            return $this->appState->getAreaCode()
                === Area::AREA_ADMINHTML;
        } catch (\Throwable $exception) {
            return false;
        }
    }
}