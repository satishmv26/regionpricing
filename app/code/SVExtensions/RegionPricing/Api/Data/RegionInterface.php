<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Api\Data;

interface RegionInterface
{
    public const REGION_ID = 'region_id';
    public const NAME = 'name';
    public const CODE = 'code';
    public const CURRENCY_CODE = 'currency_code';
    public const STATUS = 'status';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    public const STATUS_DISABLED = 0;
    public const STATUS_ENABLED = 1;

    /**
     * Get region ID
     * @return int|null
     */
    public function getRegionId(): ?int;

    /**
     * Set region ID
     * @param int $regionId
     * @return void
     */
    public function setRegionId(int $regionId);

    /**
     * Get region name
     * @return string
     */
    public function getName(): string;

    /**
     * Set region name
     * @param string $name
     * @return void
     */
    public function setName(string $name);

    /**
     * Get region code
     * @return string
     */
    public function getCode(): string;

    /**
     * Set region code
     * @param string $code
     * @return void
     */
    public function setCode(string $code);

    /**
     * Get currency code
     * @return string
     */
    public function getCurrencyCode(): string;

    /**
     * Set currency code
     * @param string $currencyCode
     * @return void
     */
    public function setCurrencyCode(string $currencyCode);

    /**
     * Get status
     * @return int
     */
    public function getStatus(): int;

    /**
     * Set status
     * @param int $status
     * @return void
     */
    public function setStatus(int $status);

    /**
     * Get created at
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Get updated at
     * @return string|null
     */
    public function getUpdatedAt(): ?string;
}