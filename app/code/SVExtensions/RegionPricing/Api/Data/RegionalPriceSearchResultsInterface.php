<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface RegionalPriceSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return \SVExtensions\RegionPricing\Api\Data\RegionalPriceInterface[]
     */
    public function getItems();

    /**
     * @param \SVExtensions\RegionPricing\Api\Data\RegionalPriceInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
