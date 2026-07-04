<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Observer;

use Magento\Framework\App\Http\Context;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;

class CustomerLogout implements ObserverInterface
{
    public function __construct(
        private readonly CookieManagerInterface $cookieManager,
        private readonly CookieMetadataFactory $cookieMetadataFactory,
        private readonly Context $httpContext
    ) {
    }

    public function execute(Observer $observer): void
    {
        $metadata = $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setPath('/');

        $this->cookieManager->deleteCookie(
            CustomerLogin::COOKIE_NAME,
            $metadata
        );

        $this->httpContext->setValue(
            CustomerLogin::CONTEXT_REGION_ID,
            0,
            0
        );
    }
}