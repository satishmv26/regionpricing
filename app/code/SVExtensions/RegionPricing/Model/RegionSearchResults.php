<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Model;

use Magento\Framework\Api\SearchResults;
use SVExtensions\RegionPricing\Api\Data\RegionSearchResultsInterface;

class RegionSearchResults extends SearchResults implements RegionSearchResultsInterface
{
}