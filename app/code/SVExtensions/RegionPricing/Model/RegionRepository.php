<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use SVExtensions\RegionPricing\Api\Data\RegionInterface;
use SVExtensions\RegionPricing\Api\Data\RegionSearchResultsInterface;
use SVExtensions\RegionPricing\Api\Data\RegionSearchResultsInterfaceFactory;
use SVExtensions\RegionPricing\Api\RegionRepositoryInterface;
use SVExtensions\RegionPricing\Model\ResourceModel\Region as RegionResource;
use SVExtensions\RegionPricing\Model\ResourceModel\Region\CollectionFactory;

class RegionRepository implements RegionRepositoryInterface
{
    public function __construct(
        private readonly RegionFactory $regionFactory,
        private readonly RegionResource $resource,
        private readonly CollectionFactory $collectionFactory,
        private readonly RegionSearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor
    ) {
    }

    /**
     * @inheritDoc
     */
    public function save(
        RegionInterface $region
    ): RegionInterface {
        try {
            /** @var Region $region */
            $region->setCode(
                strtoupper(trim($region->getCode()))
            );

            $this->resource->save($region);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Unable to save region.'),
                $exception
            );
        }

        return $region;
    }

    /**
     * @inheritDoc
     */
    public function getById(
        int $regionId
    ): RegionInterface {

        $region = $this->regionFactory->create();

        $this->resource->load(
            $region,
            $regionId
        );

        if (!$region->getId()) {
            throw new NoSuchEntityException(
                __('Region does not exist.')
            );
        }

        return $region;
    }

    /**
     * @inheritDoc
     */
    public function getByCode(
        string $code
    ): RegionInterface {

        $collection = $this->collectionFactory->create();

        $collection->addFieldToFilter(
            RegionInterface::CODE,
            strtoupper(trim($code))
        );

        $collection->addFieldToFilter(
            RegionInterface::STATUS,
            RegionInterface::STATUS_ENABLED
        );

        $collection->setPageSize(1);

        $region = $collection->getFirstItem();

        if (!$region->getId()) {
            throw new NoSuchEntityException(
                __('Region does not exist.')
            );
        }

        return $region;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria
    ): RegionSearchResultsInterface {

        $collection = $this->collectionFactory->create();

        $this->collectionProcessor->process(
            $searchCriteria,
            $collection
        );

        $searchResults = $this->searchResultsFactory->create();

        $searchResults->setSearchCriteria(
            $searchCriteria
        );

        $searchResults->setItems(
            $collection->getItems()
        );

        $searchResults->setTotalCount(
            $collection->getSize()
        );

        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function delete(
        RegionInterface $region
    ): bool {

        try {

            /** @var Region $region */

            $this->resource->delete($region);

        } catch (\Exception $exception) {

            throw new CouldNotDeleteException(
                __('Unable to delete region.'),
                $exception
            );
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById(
        int $regionId
    ): bool {

        return $this->delete(
            $this->getById($regionId)
        );
    }
}