<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote\Item;
use SVExtensions\RegionPricing\Helper\Logger;
use SVExtensions\RegionPricing\Model\Config;
use SVExtensions\RegionPricing\Service\QuotePriceApplier;

class ApplyRegionalQuotePrice implements ObserverInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly QuotePriceApplier $quotePriceApplier,
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

            $this->logger->info('ApplyRegionalQuotePrice::execute triggered', [
                'quote_item_id' => $quoteItem instanceof Item ? $quoteItem->getId() : 'n/a',
                'quote_item_type' => $quoteItem instanceof Item ? $quoteItem->getProductType() : 'n/a',
            ]);

            if (!$quoteItem instanceof Item) {
                return;
            }

            $this->quotePriceApplier->apply($quoteItem);

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