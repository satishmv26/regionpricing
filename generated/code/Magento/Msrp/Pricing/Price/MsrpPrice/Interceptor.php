<?php
namespace Magento\Msrp\Pricing\Price\MsrpPrice;

/**
 * Interceptor class for @see \Magento\Msrp\Pricing\Price\MsrpPrice
 */
class Interceptor extends \Magento\Msrp\Pricing\Price\MsrpPrice implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Catalog\Model\Product $saleableItem, $quantity, \Magento\Framework\Pricing\Adjustment\CalculatorInterface $calculator, \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency, \Magento\Msrp\Helper\Data $msrpData, \Magento\Msrp\Model\Config $config)
    {
        $this->___init();
        parent::__construct($saleableItem, $quantity, $calculator, $priceCurrency, $msrpData, $config);
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
