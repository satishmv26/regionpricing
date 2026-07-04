<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Plugin\Framework\App\Http;

use Magento\Framework\App\Http\Context;
use Magento\Framework\Stdlib\CookieManagerInterface;
use SVExtensions\RegionPricing\Model\Config;
use SVExtensions\RegionPricing\Model\RegionProvider;
use SVExtensions\RegionPricing\Observer\CustomerLogin;

class ContextPlugin
{
    private bool $initialized = false;

    public function __construct(
        private readonly CookieManagerInterface $cookieManager,
        private readonly Config $config
    ) {
    }

    public function beforeGetValue(
        Context $subject,
        ?string $name = null
    ): array {
        $this->initialize($subject);

        return [$name];
    }

    public function beforeGetVaryString(
        Context $subject
    ): void {
        $this->initialize($subject);
    }

    private function initialize(Context $context): void
    {
        if ($this->initialized) {
            return;
        }

        $this->initialized = true;

        $regionId = 0;

        if ($this->config->isEnabled()) {
            $regionId = (int)$this->cookieManager->getCookie(
                CustomerLogin::COOKIE_NAME,
                '0'
            );

            /*
             * No cookie: use configured default region for guests.
             *
             * This gives guests a consistent FPC variant
             * when a default region is configured.
             */
            if ($regionId <= 0) {
                $defaultRegion = $this->config->getDefaultRegion();
                if ($defaultRegion !== null && $defaultRegion > 0) {
                    $regionId = $defaultRegion;
                }
            }
        }

        /*
         * Set HttpContext with default = 0.
         *
         * When value != default, X-Magento-Vary includes this context,
         * creating region-specific FPC variants.
         *
         * When value == 0 (guest, no default): no variation.
         * When value == region ID: region-scoped variation.
         */
        $context->setValue(
            RegionProvider::CONTEXT_REGION_ID,
            $regionId,
            0
        );
    }
}