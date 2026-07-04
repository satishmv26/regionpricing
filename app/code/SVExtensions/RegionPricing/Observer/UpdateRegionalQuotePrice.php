<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use SVExtensions\RegionPricing\Helper\Logger;
use SVExtensions\RegionPricing\Service\QuotePriceApplier;

class UpdateRegionalQuotePrice implements ObserverInterface
{
    public function __construct(
        private readonly QuotePriceApplier $quotePriceApplier,
        private readonly Logger $logger
    ) {
    }

    public function execute(Observer $observer): void
    {
        try {
            $cart = $observer->getEvent()->getCart();
            if (!$cart) {
                return;
            }

            $quote = $cart->getQuote();
            if (!$quote) {
                return;
            }

            foreach ($quote->getAllVisibleItems() as $quoteItem) {
                $this->quotePriceApplier->apply($quoteItem);
            }
        } catch (\Throwable $exception) {
            $this->logger->warning(
                'Regional quote price update failed.',
                ['error' => $exception->getMessage()]
            );
        }
    }
}
