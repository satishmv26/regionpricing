<?php
namespace SVExtensions\RegionPricing\Model\Resolver\ProductRegionPrice;

/**
 * Interceptor class for @see \SVExtensions\RegionPricing\Model\Resolver\ProductRegionPrice
 */
class Interceptor extends \SVExtensions\RegionPricing\Model\Resolver\ProductRegionPrice implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\SVExtensions\RegionPricing\Model\PriceResolver $priceResolver, \SVExtensions\RegionPricing\Model\RegionProvider $regionProvider, \SVExtensions\RegionPricing\Api\RegionRepositoryInterface $regionRepository)
    {
        $this->___init();
        parent::__construct($priceResolver, $regionProvider, $regionRepository);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(\Magento\Framework\GraphQl\Config\Element\Field $field, $context, \Magento\Framework\GraphQl\Schema\Type\ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'resolve');
        return $pluginInfo ? $this->___callPlugins('resolve', func_get_args(), $pluginInfo) : parent::resolve($field, $context, $info, $value, $args);
    }
}
