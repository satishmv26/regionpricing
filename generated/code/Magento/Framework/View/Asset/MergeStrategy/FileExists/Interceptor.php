<?php
namespace Magento\Framework\View\Asset\MergeStrategy\FileExists;

/**
 * Interceptor class for @see \Magento\Framework\View\Asset\MergeStrategy\FileExists
 */
class Interceptor extends \Magento\Framework\View\Asset\MergeStrategy\FileExists implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\View\Asset\MergeStrategyInterface $strategy, \Magento\Framework\Filesystem $filesystem)
    {
        $this->___init();
        parent::__construct($strategy, $filesystem);
    }

    /**
     * {@inheritdoc}
     */
    public function merge(array $assetsToMerge, \Magento\Framework\View\Asset\LocalInterface $resultAsset)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'merge');
        return $pluginInfo ? $this->___callPlugins('merge', func_get_args(), $pluginInfo) : parent::merge($assetsToMerge, $resultAsset);
    }
}
