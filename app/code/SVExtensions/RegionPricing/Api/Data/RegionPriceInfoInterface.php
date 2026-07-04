<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Api\Data;

interface RegionPriceInfoInterface
{
    /**
     * @return string
     */
    public function getSku(): string;

    /**
     * @param string $sku
     * @return $this
     */
    public function setSku(string $sku);

    /**
     * @return float|null
     */
    public function getPrice(): ?float;

    /**
     * @param float|null $price
     * @return $this
     */
    public function setPrice(?float $price);

    /**
     * @return string|null
     */
    public function getCurrency(): ?string;

    /**
     * @param string|null $currency
     * @return $this
     */
    public function setCurrency(?string $currency);

    /**
     * @return bool
     */
    public function isFallback(): bool;

    /**
     * @param bool $isFallback
     * @return $this
     */
    public function setIsFallback(bool $isFallback);
}
