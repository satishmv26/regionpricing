<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Model;

use Magento\Framework\DataObject;
use SVExtensions\RegionPricing\Api\Data\RegionPriceInfoInterface;

class RegionPriceInfo extends DataObject implements RegionPriceInfoInterface
{
    public function getSku(): string
    {
        return (string)$this->getData('sku');
    }

    public function setSku(string $sku)
    {
        return $this->setData('sku', $sku);
    }

    public function getPrice(): ?float
    {
        $price = $this->getData('price');
        return $price !== null ? (float)$price : null;
    }

    public function setPrice(?float $price)
    {
        return $this->setData('price', $price);
    }

    public function getCurrency(): ?string
    {
        return $this->getData('currency');
    }

    public function setCurrency(?string $currency)
    {
        return $this->setData('currency', $currency);
    }

    public function isFallback(): bool
    {
        return (bool)$this->getData('is_fallback');
    }

    public function setIsFallback(bool $isFallback)
    {
        return $this->setData('is_fallback', $isFallback);
    }
}
