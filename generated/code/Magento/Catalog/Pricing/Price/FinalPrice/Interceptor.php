<?php
namespace Magento\Catalog\Pricing\Price\FinalPrice;

/**
 * Interceptor class for @see \Magento\Catalog\Pricing\Price\FinalPrice
 */
class Interceptor extends \Magento\Catalog\Pricing\Price\FinalPrice implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\Pricing\SaleableInterface $saleableItem, $quantity, \Magento\Framework\Pricing\Adjustment\CalculatorInterface $calculator, \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency)
    {
        $this->___init();
        parent::__construct($saleableItem, $quantity, $calculator, $priceCurrency);
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
