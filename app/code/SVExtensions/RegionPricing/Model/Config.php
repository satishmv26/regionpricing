<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const XML_PATH_ENABLED =
        'sv_region_pricing/general/enabled';

    private const XML_PATH_DEFAULT_REGION =
        'sv_region_pricing/general/default_region';

    private const XML_PATH_ENABLE_LOGGING =
        'sv_region_pricing/general/enable_logging';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Module Enabled
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Default Region
     */
    public function getDefaultRegion(?int $storeId = null): ?int
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_DEFAULT_REGION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $value !== null ? (int)$value : null;
    }

    /**
     * Enable Logging
     */
    public function isLoggingEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE_LOGGING,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}