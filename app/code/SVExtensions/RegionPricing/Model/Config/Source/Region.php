<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use SVExtensions\RegionPricing\Api\RegionRepositoryInterface;
use SVExtensions\RegionPricing\Api\Data\RegionInterface;

class Region implements OptionSourceInterface
{
    /**
     * @param RegionRepositoryInterface $regionRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        private readonly RegionRepositoryInterface $regionRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
    }

    /**
     * Return Region Options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];

        $searchCriteria = $this->searchCriteriaBuilder->create();

        $regions = $this->regionRepository
            ->getList($searchCriteria)
            ->getItems();

        foreach ($regions as $region) {

            /** @var RegionInterface $region */

            if ($region->getStatus() != RegionInterface::STATUS_ENABLED) {
                continue;
            }

            $options[] = [
                'value' => $region->getRegionId(),
                'label' => sprintf(
                    '%s (%s)',
                    $region->getName(),
                    $region->getCode()
                )
            ];
        }

        return $options;
    }
}