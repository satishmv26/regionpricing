<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Plugin\Catalog\Pricing\Price;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\CatalogRule\Model\Rule as CatalogRule;
use SVExtensions\RegionPricing\Helper\Logger;
use SVExtensions\RegionPricing\Model\Config;
use SVExtensions\RegionPricing\Model\PriceResolver;

class RegionalCatalogRulePlugin
{
    public function __construct(
        private readonly Config $config,
        private readonly PriceResolver $priceResolver,
        private readonly CatalogRule $catalogRule,
        private readonly Logger $logger
    ) {
    }

    /**
     * Apply Catalog Rule on top of Regional Base Price.
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

        try {
            $product = $subject->getProduct();

            if (!$product instanceof Product) {
                return $result;
            }

            $productId = (int)$product->getId();

            if ($productId <= 0) {
                return $result;
            }

            $regionalPrice = $this->priceResolver->resolvePrice(
                $productId
            );

            /*
             * No regional price:
             * preserve complete native Magento final price.
             */
            if ($regionalPrice === null) {
                return $result;
            }

            $regionalPrice = (float)$regionalPrice;

            /*
             * Calculate Catalog Rule using Regional Price
             * as the calculation base.
             */
            $catalogRulePrice = $this->catalogRule
                ->calcProductPriceRule(
                    $product,
                    $regionalPrice
                );

            /*
             * No applicable Catalog Rule.
             *
             * Do not blindly return regional price here because
             * native final price may contain Special/Tier pricing.
             */
            if (
                $catalogRulePrice === null
                || $catalogRulePrice === false
            ) {
                return min(
                    $regionalPrice,
                    $result
                );
            }

            $catalogRulePrice = (float)$catalogRulePrice;

            /*
             * For current phase:
             * choose lowest applicable final price.
             *
             * Regional Catalog Rule result vs native final price.
             */
            return min(
                $catalogRulePrice,
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
}