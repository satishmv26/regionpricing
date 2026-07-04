<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Block\Adminhtml\Region\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton implements ButtonProviderInterface
{
    public function __construct(
        private readonly Context $context
    ) {
    }

    public function getButtonData(): array
    {
        $regionId = (int)$this->context->getRequest()->getParam('region_id');
        if (!$regionId) {
            return [];
        }

        return [
            'label' => __('Delete Region'),
            'class' => 'delete',
            'on_click' => sprintf(
                "deleteConfirm('%s', '%s')",
                __('Are you sure you want to delete this region?'),
                $this->context->getUrlBuilder()->getUrl('*/*/delete', ['region_id' => $regionId])
            ),
            'sort_order' => 20,
        ];
    }
}
