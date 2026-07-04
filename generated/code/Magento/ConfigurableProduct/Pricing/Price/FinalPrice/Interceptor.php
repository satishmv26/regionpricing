<?php
namespace Magento\ConfigurableProduct\Pricing\Price\FinalPrice;

/**
 * Interceptor class for @see \Magento\ConfigurableProduct\Pricing\Price\FinalPrice
 */
class Interceptor extends \Magento\ConfigurableProduct\Pricing\Price\FinalPrice implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\Pricing\SaleableInterface $saleableItem, $quantity, \Magento\Framework\Pricing\Adjustment\CalculatorInterface $calculator, \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency, \Magento\ConfigurableProduct\Pricing\Price\PriceResolverInterface $priceResolver)
    {
        $this->___init();
        parent::__construct($saleableItem, $quantity, $calculator, $priceCurrency, $priceResolver);
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
