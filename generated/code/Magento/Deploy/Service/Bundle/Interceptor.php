<?php
namespace Magento\Deploy\Service\Bundle;

/**
 * Interceptor class for @see \Magento\Deploy\Service\Bundle
 */
class Interceptor extends \Magento\Deploy\Service\Bundle implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\Filesystem $filesystem, \Magento\Deploy\Package\BundleInterfaceFactory $bundleFactory, \Magento\Deploy\Config\BundleConfig $bundleConfig, \Magento\Framework\App\Utility\Files $files, ?\Magento\Framework\Filesystem\Io\File $file = null)
    {
        $this->___init();
        parent::__construct($filesystem, $bundleFactory, $bundleConfig, $files, $file);
    }

    /**
     * {@inheritdoc}
     */
    public function deploy($area, $theme, $locale)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'deploy');
        return $pluginInfo ? $this->___callPlugins('deploy', func_get_args(), $pluginInfo) : parent::deploy($area, $theme, $locale);
    }
}
