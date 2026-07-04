<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Ui\DataProvider\Region\Form;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use SVExtensions\RegionPricing\Model\ResourceModel\Region\CollectionFactory;

class DataProvider extends AbstractDataProvider
{
    private const DATA_PERSISTOR_KEY = 'sv_region';

    private array $loadedData = [];

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        private readonly DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData(): array
    {
        if ($this->loadedData) {
            return $this->loadedData;
        }

        foreach ($this->collection->getItems() as $region) {
            $this->loadedData[(int)$region->getId()] = $region->getData();
        }

        $data = $this->dataPersistor->get(self::DATA_PERSISTOR_KEY);
        if (!empty($data)) {
            $region = $this->collection->getNewEmptyItem();
            $region->setData($data);
            $this->loadedData[(int)$region->getId()] = $region->getData();
            $this->dataPersistor->clear(self::DATA_PERSISTOR_KEY);
        }

        return $this->loadedData;
    }
}
