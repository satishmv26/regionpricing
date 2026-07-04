<?php
namespace SVExtensions\RegionPricing\Model\Resolver\CurrentRegion;

/**
 * Interceptor class for @see \SVExtensions\RegionPricing\Model\Resolver\CurrentRegion
 */
class Interceptor extends \SVExtensions\RegionPricing\Model\Resolver\CurrentRegion implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\SVExtensions\RegionPricing\Api\RegionPricingFacadeInterface $regionPricingFacade)
    {
        $this->___init();
        parent::__construct($regionPricingFacade);
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
