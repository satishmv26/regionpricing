<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Observer;

use Magento\Catalog\Model\Product;
use Magento\CatalogRule\Model\Rule as CatalogRule;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote\Item;
use SVExtensions\RegionPricing\Helper\Logger;
use SVExtensions\RegionPricing\Model\Config;
use SVExtensions\RegionPricing\Model\PriceResolver;

class ApplyRegionalQuotePrice implements ObserverInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly PriceResolver $priceResolver,
        private readonly CatalogRule $catalogRule,
        private readonly Logger $logger
    ) {
    }

    public function execute(Observer $observer): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        try {
            $quoteItem = $observer->getEvent()->getQuoteItem();

            if (!$quoteItem instanceof Item) {
                return;
            }

            $product = $quoteItem->getProduct();

            if (!$product instanceof Product) {
                return;
            }

            $productId = (int)$product->getId();

            if ($productId <= 0) {
                return;
            }

            /*
             * Raw regional base price.
             *
             * Example:
             * Native Price   = 50
             * Regional Price = 45
             */
            $regionalPrice = $this->priceResolver->resolvePrice(
                $productId
            );

            /*
             * No regional price:
             * leave Magento quote pricing completely untouched.
             */
            if ($regionalPrice === null) {
                return;
            }

            $effectivePrice = (float)$regionalPrice;

            /*
             * Apply Catalog Rule using Regional Price as base.
             *
             * Example:
             * Regional Price = 45
             * Catalog Rule   = -2
             * Effective      = 43
             */
            $catalogRulePrice = $this->catalogRule
                ->calcProductPriceRule(
                    $product,
                    $effectivePrice
                );

            if (
                $catalogRulePrice !== null
                && $catalogRulePrice !== false
            ) {
                $effectivePrice = (float)$catalogRulePrice;
            }

            /*
             * Quote pricing basis:
             *
             * Regional Base
             *      ↓
             * Catalog Rule
             *      ↓
             * Effective Catalog Price
             *      ↓
             * Cart Price Rules / Coupon handled natively afterward
             */
            $quoteItem->setCustomPrice($effectivePrice);
            $quoteItem->setOriginalCustomPrice($effectivePrice);

            $product->setIsSuperMode(true);

        } catch (\Throwable $exception) {
            $this->logger->warning(
                'Regional quote price calculation failed.',
                [
                    'error' => $exception->getMessage()
                ]
            );
        }
    }
}