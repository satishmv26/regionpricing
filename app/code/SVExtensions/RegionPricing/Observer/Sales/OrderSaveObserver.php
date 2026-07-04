<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Observer\Sales;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use SVExtensions\RegionPricing\Helper\Logger;

/**
 * Ensure order items retain the purchase price after placement.
 * Magento already freezes quote prices into sales_order_item at placement.
 * This observer logs the final prices for audit trail.
 */
class OrderSaveObserver implements ObserverInterface
{
    public function __construct(
        private readonly Logger $logger
    ) {
    }

    public function execute(Observer $observer): void
    {
        $order = $observer->getEvent()->getOrder();
        if (!$order) {
            return;
        }

        $items = [];
        foreach ($order->getAllVisibleItems() as $item) {
            $items[] = [
                'sku' => $item->getSku(),
                'price' => (float)$item->getPrice(),
                'qty' => (float)$item->getQtyOrdered(),
                'row_total' => (float)$item->getRowTotal(),
                'base_price' => (float)$item->getBasePrice(),
            ];
        }

        $this->logger->audit('Order placed with regional pricing', [
            'order_id' => $order->getIncrementId(),
            'order_entity_id' => $order->getId(),
            'customer_id' => $order->getCustomerId(),
            'grand_total' => (float)$order->getGrandTotal(),
            'item_count' => count($items),
            'items' => $items,
        ]);
    }
}
