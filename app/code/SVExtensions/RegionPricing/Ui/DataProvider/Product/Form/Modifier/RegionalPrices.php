<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\DynamicRows;
use Magento\Ui\Component\Form\Element\ActionDelete;
use Magento\Ui\Component\Form\Element\DataType\Number;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Element\Select;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Fieldset;
use SVExtensions\RegionPricing\Api\Data\RegionInterface;
use SVExtensions\RegionPricing\Api\ProductRegionPriceRepositoryInterface;
use SVExtensions\RegionPricing\Api\RegionRepositoryInterface;
use SVExtensions\RegionPricing\Helper\Logger;

class RegionalPrices extends \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier
{
    public const DATA_SCOPE = 'sv_regional_prices';
    public const GROUP_NAME = 'sv_regional_prices';
    public const GROUP_ORDER = 95;
    public const SORT_ORDER = 10;

    public function __construct(
        private readonly LocatorInterface $locator,
        private readonly ArrayManager $arrayManager,
        private readonly ProductRegionPriceRepositoryInterface $productRegionPriceRepository,
        private readonly RegionRepositoryInterface $regionRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly Logger $logger
    ) {
    }

    public function modifyMeta(array $meta): array
    {
        $this->logger->info('RegionalPrices modifyMeta called');
        
        // Try to find existing path, or add at root
        $path = $this->arrayManager->findPath(
            static::GROUP_NAME,
            $meta,
            null,
            'children'
        );
        
        if (!$path) {
            // Add at root level if not found
            $path = static::GROUP_NAME;
            $this->logger->info('Adding fieldset at root level', ['path' => $path]);
        } else {
            $this->logger->info('Found existing fieldset path', ['path' => $path]);
        }
        
        $meta = $this->arrayManager->set(
            $path,
            $meta,
            $this->getFieldsetConfig()
        );

        return $meta;
    }

    public function modifyData(array $data): array
    {
        $product = $this->locator->getProduct();
        $productId = (int)$product->getId();

        $this->logger->info('RegionalPrices modifyData called', ['productId' => $productId]);

        if (!$productId) {
            return $data;
        }

        $prices = $this->productRegionPriceRepository->getByProductId($productId);
        $this->logger->info('RegionalPrices - Found prices', ['count' => count($prices)]);
        
        $priceData = [];
        foreach ($prices as $price) {
            $priceData[] = [
                'region_id' => $price->getRegionId(),
                'price' => $price->getPrice(),
            ];
        }

        // Set data at product level with namespaced key
        if (!isset($data[$productId])) {
            $data[$productId] = [];
        }
        if (!isset($data[$productId]['product'])) {
            $data[$productId]['product'] = [];
        }
        if (!isset($data[$productId]['product'][self::DATA_SCOPE])) {
            $data[$productId]['product'][self::DATA_SCOPE] = [];
        }

        $data[$productId]['product'][self::DATA_SCOPE]['prices'] = $priceData;
        $this->logger->info('RegionalPrices modifyData complete', ['prices_count' => count($priceData)]);
        
        return $data;
    }

    private function getFieldsetConfig(): array
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Fieldset::NAME,
                        'label' => __('Regional Prices'),
                        'collapsible' => true,
                        'opened' => false,
                        'sortOrder' => self::GROUP_ORDER,
                        'dataScope' => 'data.product.' . self::DATA_SCOPE,
                        'visible' => true,
                        'disabled' => false,
                        'additionalClasses' => 'admin__fieldset-section',
                    ],
                ],
            ],
            'children' => [
                'prices' => $this->getDynamicRowsConfig(),
            ],
        ];
    }

private function getDynamicRowsConfig(): array
{
    return [
        'arguments' => [
            'data' => [
                'config' => [
                    'componentType' => DynamicRows::NAME,
                    'component' => 'Magento_Ui/js/dynamic-rows/dynamic-rows',
                    'template' => 'ui/dynamic-rows/templates/default',
                    'recordTemplate' => 'record',
                    'renderDefaultRecord' => false,
                    'columnsHeader' => true,
                    'addButton' => true,
                    'addButtonLabel' => __('Add Region Price'),
                    'dataScope' => '',
                    'dndConfig' => [
                        'enabled' => false,
                    ],
                    'sortOrder' => 10,
                    'deleteButtonLabel' => __('Remove'),
                ],
            ],
        ],
        'children' => [
            'record' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'componentType' => Container::NAME,
                            'component' => 'Magento_Ui/js/dynamic-rows/record',
                            'template' => 'ui/dynamic-rows/templates/record',
                            'isTemplate' => true,
                            'is_collection' => true,
                            'dataScope' => '',
                        ],
                    ],
                ],
                'children' => [
                    'region_id' => $this->getRegionSelectConfig(),
                    'price' => $this->getPriceInputConfig(),
                    'action_delete' => $this->getDeleteActionConfig(),
                ],
            ],
        ],
    ];
}

    private function getRegionSelectConfig(): array
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Region'),
                        'componentType' => Field::NAME,
                        'formElement' => Select::NAME,
                        'dataScope' => 'region_id',
                        'dataType' => Number::NAME,
                        'sortOrder' => 10,
                        'options' => $this->getActiveRegionOptions(),
                        'validation' => [
                            'required-entry' => true,
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getPriceInputConfig(): array
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Price'),
                        'componentType' => Field::NAME,
                        'formElement' => Input::NAME,
                        'dataScope' => 'price',
                        'dataType' => Number::NAME,
                        'sortOrder' => 20,
                        'validation' => [
                            'required-entry' => true,
                            'validate-number' => true,
                            'validate-greater-than-zero' => true,
                        ],
                        'addbefore' => __('$'),
                    ],
                ],
            ],
        ];
    }

    private function getDeleteActionConfig(): array
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => ActionDelete::NAME,
                        'dataType' => Text::NAME,
                        'label' => ' ',
                        'fit' => true,
                        'sortOrder' => 30,
                    ],
                ],
            ],
        ];
    }

    private function getActiveRegionOptions(): array
    {
        $options = [];
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $regions = $this->regionRepository->getList($searchCriteria)->getItems();

        foreach ($regions as $region) {
            if ((int)$region->getStatus() !== RegionInterface::STATUS_ENABLED) {
                continue;
            }
            $options[] = [
                'value' => $region->getRegionId(),
                'label' => sprintf('%s (%s)', $region->getName(), $region->getCode()),
            ];
        }

        return $options;
    }
}
