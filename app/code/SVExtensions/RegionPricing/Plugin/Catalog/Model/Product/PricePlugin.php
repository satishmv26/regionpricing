<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Plugin\Catalog\Model\Product;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use SVExtensions\RegionPricing\Helper\Logger;
use SVExtensions\RegionPricing\Model\PriceResolver;

class PricePlugin
{
    public function __construct(
        private readonly PriceResolver $priceResolver,
        private readonly Logger $logger,
        private readonly State $appState
    ) {
    }

    /**
     * Replace native base price with regional base price.
     *
     * This method must NOT apply:
     * - Catalog Rule
     * - Special Price
     * - Tier Price
     * - Cart Rule
     *
     * It only replaces Magento base price.
     */
    public function afterGetPrice(
        Product $product,
        ?float $result
    ): ?float {
        if (!$this->isFrontendArea()) {
            return $result;
        }

        try {
            $regionalPrice = $this->priceResolver->resolvePrice(
                (int)$product->getId()
            );

            if ($regionalPrice === null) {
                return $result;
            }

            return (float)$regionalPrice;

        } catch (\Throwable $exception) {
            $this->logger->warning(
                'Regional base price resolution failed.',
                [
                    'product_id' => (int)$product->getId(),
                    'error' => $exception->getMessage()
                ]
            );

            return $result;
        }
    }

    private function isFrontendArea(): bool
    {
        try {
            return $this->appState->getAreaCode()
                === Area::AREA_FRONTEND;
        } catch (\Throwable $exception) {
            return false;
        }
    }
}