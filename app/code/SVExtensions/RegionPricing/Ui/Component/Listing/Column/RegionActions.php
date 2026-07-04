<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class RegionActions extends Column
{
    private const URL_PATH_EDIT = 'svregion/region/edit';
    private const URL_PATH_DELETE = 'svregion/region/delete';

    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item['region_id'])) {
                continue;
            }

            $item[$this->getData('name')] = [
                'edit' => [
                    'href' => $this->urlBuilder->getUrl(self::URL_PATH_EDIT, ['region_id' => $item['region_id']]),
                    'label' => __('Edit'),
                ],
                'delete' => [
                    'href' => $this->urlBuilder->getUrl(self::URL_PATH_DELETE, ['region_id' => $item['region_id']]),
                    'label' => __('Delete'),
                    'confirm' => [
                        'title' => __('Delete %1', $item['name'] ?? ''),
                        'message' => __('Are you sure you want to delete this region?'),
                    ],
                ],
            ];
        }

        return $dataSource;
    }
}
