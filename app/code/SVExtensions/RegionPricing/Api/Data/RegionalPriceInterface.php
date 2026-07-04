<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Api\Data;

interface RegionalPriceInterface
{
    public const ENTITY_ID = 'entity_id';
    public const PRODUCT_ID = 'product_id';
    public const REGION_ID = 'region_id';
    public const PRICE = 'price';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    public function getEntityId(): ?int;
    public function setEntityId(int $entityId);
    public function getProductId(): int;
    public function setProductId(int $productId);
    public function getRegionId(): int;
    public function setRegionId(int $regionId);
    public function getPrice(): float;
    public function setPrice(float $price);
    public function getCreatedAt(): ?string;
    public function getUpdatedAt(): ?string;
}
