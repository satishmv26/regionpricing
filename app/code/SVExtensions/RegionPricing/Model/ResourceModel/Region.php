<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Region extends AbstractDb
{
    public const TABLE_NAME = 'sv_region';

    protected function _construct(): void
    {
        $this->_init(self::TABLE_NAME, 'region_id');
    }
}
