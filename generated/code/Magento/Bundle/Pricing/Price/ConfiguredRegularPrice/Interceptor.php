<?php
namespace Magento\Bundle\Pricing\Price\ConfiguredRegularPrice;

/**
 * Interceptor class for @see \Magento\Bundle\Pricing\Price\ConfiguredRegularPrice
 */
class Interceptor extends \Magento\Bundle\Pricing\Price\ConfiguredRegularPrice implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Catalog\Model\Product $saleableItem, $quantity, \Magento\Bundle\Pricing\Adjustment\BundleCalculatorInterface $calculator, \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency, ?\Magento\Catalog\Model\Product\Configuration\Item\ItemInterface $item = null, ?\Magento\Framework\Serialize\Serializer\Json $serializer = null, ?\Magento\Catalog\Pricing\Price\ConfiguredPriceSelection $configuredPriceSelection = null, ?\Magento\Bundle\Pricing\Price\DiscountCalculator $discountCalculator = null)
    {
        $this->___init();
        parent::__construct($saleableItem, $quantity, $calculator, $priceCurrency, $item, $serializer, $configuredPriceSelection, $discountCalculator);
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
