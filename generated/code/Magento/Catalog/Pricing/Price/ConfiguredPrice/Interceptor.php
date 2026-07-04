<?php
namespace Magento\Catalog\Pricing\Price\ConfiguredPrice;

/**
 * Interceptor class for @see \Magento\Catalog\Pricing\Price\ConfiguredPrice
 */
class Interceptor extends \Magento\Catalog\Pricing\Price\ConfiguredPrice implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Catalog\Model\Product $saleableItem, $quantity, \Magento\Framework\Pricing\Adjustment\CalculatorInterface $calculator, \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency, ?\Magento\Catalog\Model\Product\Configuration\Item\ItemInterface $item = null, ?\Magento\Catalog\Pricing\Price\ConfiguredOptions $configuredOptions = null)
    {
        $this->___init();
        parent::__construct($saleableItem, $quantity, $calculator, $priceCurrency, $item, $configuredOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getValue');
        return $pluginInfo ? $this->___callPlugins('getValue', func_get_args(), $pluginInfo) : parent::getValue();
    }
}
