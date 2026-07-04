<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class RegionalPrice extends AbstractDb
{
    public const TABLE_NAME = 'sv_product_region_price';

    protected function _construct(): void
    {
        $this->_init(self::TABLE_NAME, 'entity_id');
    }
}
