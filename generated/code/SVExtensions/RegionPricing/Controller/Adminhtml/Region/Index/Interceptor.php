<?php
namespace SVExtensions\RegionPricing\Controller\Adminhtml\Region\Index;

/**
 * Interceptor class for @see \SVExtensions\RegionPricing\Controller\Adminhtml\Region\Index
 */
class Interceptor extends \SVExtensions\RegionPricing\Controller\Adminhtml\Region\Index implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\View\Result\PageFactory $resultPageFactory, \SVExtensions\RegionPricing\Api\RegionRepositoryInterface $regionRepository)
    {
        $this->___init();
        parent::__construct($context, $resultPageFactory, $regionRepository);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'execute');
        return $pluginInfo ? $this->___callPlugins('execute', func_get_args(), $pluginInfo) : parent::execute();
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(\Magento\Framework\App\RequestInterface $request)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'dispatch');
        return $pluginInfo ? $this->___callPlugins('dispatch', func_get_args(), $pluginInfo) : parent::dispatch($request);
    }
}
