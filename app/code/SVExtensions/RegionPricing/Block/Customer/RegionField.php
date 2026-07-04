<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Block\Customer;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use SVExtensions\RegionPricing\Api\RegionRepositoryInterface;
use SVExtensions\RegionPricing\Api\Data\RegionInterface;
use SVExtensions\RegionPricing\Model\Config;

class RegionField extends Template
{
    private ?array $activeRegions = null;

    public function __construct(
        Context $context,
        private readonly RegionRepositoryInterface $regionRepository,
        private readonly CustomerSession $customerSession,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getRegions(): array
    {
        if ($this->activeRegions === null) {
            $searchCriteria = $this->searchCriteriaBuilder->create();
            $regions = $this->regionRepository->getList($searchCriteria)->getItems();
            $this->activeRegions = array_filter(
                $regions,
                fn(RegionInterface $r) => (int)$r->getStatus() === RegionInterface::STATUS_ENABLED
            );
        }
        return $this->activeRegions;
    }

    public function getCustomerRegionId(): ?int
    {
        if ($this->customerSession->isLoggedIn()) {
            $value = $this->customerSession->getCustomer()->getData('sv_region_id');
            return $value ? (int)$value : null;
        }
        return null;
    }

    public function isEnabled(): bool
    {
        return $this->config->isEnabled();
    }

    public function getFieldName(): string
    {
        return 'sv_region_id';
    }

    public function getFieldId(): string
    {
        return 'sv_region_id';
    }
}
