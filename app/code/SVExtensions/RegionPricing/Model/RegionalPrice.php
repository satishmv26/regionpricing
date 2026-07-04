<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Model;

use Magento\Framework\Model\AbstractModel;
use SVExtensions\RegionPricing\Api\Data\RegionalPriceInterface;
use SVExtensions\RegionPricing\Model\ResourceModel\RegionalPrice as RegionalPriceResource;

class RegionalPrice extends AbstractModel implements RegionalPriceInterface
{
    protected function _construct(): void
    {
        $this->_init(RegionalPriceResource::class);
    }

    public function getEntityId(): ?int
    {
        $value = $this->getData(self::ENTITY_ID);
        return $value !== null ? (int)$value : null;
    }

    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    public function getProductId(): int
    {
        return (int)$this->getData(self::PRODUCT_ID);
    }

    public function setProductId(int $productId)
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }

    public function getRegionId(): int
    {
        return (int)$this->getData(self::REGION_ID);
    }

    public function setRegionId(int $regionId)
    {
        return $this->setData(self::REGION_ID, $regionId);
    }

    public function getPrice(): float
    {
        return (float)$this->getData(self::PRICE);
    }

    public function setPrice(float $price)
    {
        return $this->setData(self::PRICE, $price);
    }

    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }
}
