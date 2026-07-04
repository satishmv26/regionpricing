<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Plugin\Catalog\Model\Product;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use SVExtensions\RegionPricing\Model\PriceResolver;

/**
 * Preload regional prices for product collections to avoid N+1 queries.
 */
class CollectionPlugin
{
    private bool $loaded = false;

    public function __construct(
        private readonly PriceResolver $priceResolver
    ) {
    }

    /**
     * After collection loads, preload regional prices for all products.
     */
    public function afterLoad(Collection $subject, Collection $result): Collection
    {
        if ($this->loaded) {
            return $result;
        }

        $ids = $result->getAllIds();
        if (empty($ids)) {
            return $result;
        }

        $this->priceResolver->resolvePrices($ids);
        $this->loaded = true;

        return $result;
    }
}
