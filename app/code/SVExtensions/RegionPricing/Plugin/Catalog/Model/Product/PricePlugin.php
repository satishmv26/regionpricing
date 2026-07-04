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
     *
     * Parent product types whose price is derived from children
     * are skipped to prevent breaking price calculations:
     *   - configurable (JS option price differences)
     *   - bundle with dynamic pricing (sum of selection prices)
     *   - grouped (minimum associated product price)
     */
    public function afterGetPrice(
        Product $product,
        ?float $result
    ): ?float {
        if (!$this->isEnabledArea()) {
            return $result;
        }

        if ($this->shouldSkipType($product)) {
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

    /**
     * Product types whose base price should not be replaced.
     */
    private function shouldSkipType(Product $product): bool
    {
        $typeId = $product->getTypeId();

        if ($typeId === 'configurable') {
            return true;
        }

        if ($typeId === 'grouped') {
            return true;
        }

        if ($typeId === 'bundle' && (int)$product->getPriceType() === 0) {
            return true;
        }

        return false;
    }

    private function isEnabledArea(): bool
    {
        try {
            return in_array(
                $this->appState->getAreaCode(),
                [
                    Area::AREA_FRONTEND,
                    Area::AREA_WEBAPI_REST,
                    'graphql',
                ],
                true
            );
        } catch (\Throwable $exception) {
            return false;
        }
    }
}