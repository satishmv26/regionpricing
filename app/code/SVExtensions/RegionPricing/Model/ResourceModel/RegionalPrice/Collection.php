<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Model\ResourceModel\RegionalPrice;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use SVExtensions\RegionPricing\Model\RegionalPrice;
use SVExtensions\RegionPricing\Model\ResourceModel\RegionalPrice as RegionalPriceResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'entity_id';

    protected function _construct(): void
    {
        $this->_init(RegionalPrice::class, RegionalPriceResource::class);
    }
}
