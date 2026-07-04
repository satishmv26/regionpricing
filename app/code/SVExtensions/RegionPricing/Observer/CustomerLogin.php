<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Observer;

use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\Http\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use SVExtensions\RegionPricing\Model\Config;

class CustomerLogin implements ObserverInterface
{
    public const COOKIE_NAME = 'sv_region_id';
    public const CONTEXT_REGION_ID = 'sv_region_id';

    public function __construct(
        private readonly Config $config,
        private readonly ResourceConnection $resourceConnection,
        private readonly EavConfig $eavConfig,
        private readonly CookieManagerInterface $cookieManager,
        private readonly CookieMetadataFactory $cookieMetadataFactory,
        private readonly Context $httpContext
    ) {
    }

    public function execute(Observer $observer): void
    {
        if (!$this->config->isEnabled()) {
            $this->clearRegionContext();
            return;
        }

        $customer = $observer->getEvent()->getCustomer();

        $customerId = (int)$customer->getId();

        if ($customerId <= 0) {
            $this->clearRegionContext();
            return;
        }

        $regionId = $this->getCustomerRegionId($customerId);

        if ($regionId === null) {
            $this->clearRegionContext();
            return;
        }

        /*
         * 1. Store Region cookie for next requests.
         */
        $metadata = $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setPath('/')
            ->setDurationOneYear();

        $this->cookieManager->setPublicCookie(
            self::COOKIE_NAME,
            (string)$regionId,
            $metadata
        );

        /*
         * 2. IMPORTANT:
         * Update Magento HttpContext in current login request.
         *
         * This allows Magento to generate the correct
         * X-Magento-Vary context.
         */
        $this->httpContext->setValue(
            self::CONTEXT_REGION_ID,
            $regionId,
            0
        );
    }

    private function getCustomerRegionId(int $customerId): ?int
    {
        $attribute = $this->eavConfig->getAttribute(
            'customer',
            'sv_region_id'
        );

        $attributeId = (int)$attribute->getAttributeId();

        if ($attributeId <= 0) {
            return null;
        }

        $connection = $this->resourceConnection->getConnection();

        $tableName = $this->resourceConnection->getTableName(
            'customer_entity_int'
        );

        $select = $connection->select()
            ->from(
                $tableName,
                ['value']
            )
            ->where('entity_id = ?', $customerId)
            ->where('attribute_id = ?', $attributeId)
            ->limit(1);

        $value = $connection->fetchOne($select);

        if (
            $value === false ||
            $value === null ||
            $value === ''
        ) {
            return null;
        }

        $regionId = (int)$value;

        return $regionId > 0
            ? $regionId
            : null;
    }

    private function clearRegionContext(): void
    {
        $metadata = $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setPath('/');

        $this->cookieManager->deleteCookie(
            self::COOKIE_NAME,
            $metadata
        );

        $this->httpContext->setValue(
            self::CONTEXT_REGION_ID,
            0,
            0
        );
    }
}