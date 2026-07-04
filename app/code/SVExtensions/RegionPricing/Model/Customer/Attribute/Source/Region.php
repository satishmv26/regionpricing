<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Model\Customer\Attribute\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Psr\Log\LoggerInterface;
use SVExtensions\RegionPricing\Api\RegionRepositoryInterface;
use SVExtensions\RegionPricing\Api\Data\RegionInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class Region extends AbstractSource
{
    private ?array $options = null;

    public function __construct(
        private readonly RegionRepositoryInterface $regionRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getAllOptions(): array
    {
        if ($this->options === null) {
            $this->options = [];
            try {
                $searchCriteria = $this->searchCriteriaBuilder->create();
                $regions = $this->regionRepository->getList($searchCriteria)->getItems();
                foreach ($regions as $region) {
                    if ((int)$region->getStatus() !== RegionInterface::STATUS_ENABLED) {
                        continue;
                    }
                    $this->options[] = [
                        'value' => $region->getRegionId(),
                        'label' => sprintf('%s (%s)', $region->getName(), $region->getCode()),
                    ];
                }
            } catch (\Throwable $e) {
                $this->logger->error('[SVRegionPricing] Failed to load customer region options: ' . $e->getMessage());
            }
        }
        return $this->options;
    }
}
