<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Model\ResourceModel\Region;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use SVExtensions\RegionPricing\Model\Region;
use SVExtensions\RegionPricing\Model\ResourceModel\Region as RegionResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'region_id';

    protected function _construct(): void
    {
        $this->_init(Region::class, RegionResource::class);
    }
}
