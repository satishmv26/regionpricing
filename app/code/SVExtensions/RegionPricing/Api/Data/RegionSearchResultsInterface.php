<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Region Search Results Interface
 */
interface RegionSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get Region List.
     *
     * @return \SVExtensions\RegionPricing\Api\Data\RegionInterface[]
     */
    public function getItems();

    /**
     * Set Region List.
     *
     * @param \SVExtensions\RegionPricing\Api\Data\RegionInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}