<?php
namespace Magento\Authorization\Model\ResourceModel\Rules;

/**
 * Interceptor class for @see \Magento\Authorization\Model\ResourceModel\Rules
 */
class Interceptor extends \Magento\Authorization\Model\ResourceModel\Rules implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\Model\ResourceModel\Db\Context $context, \Magento\Framework\Acl\Builder $aclBuilder, \Psr\Log\LoggerInterface $logger, \Magento\Framework\Acl\RootResource $rootResource, \Magento\Framework\Acl\Data\CacheInterface $aclDataCache, $connectionName = null)
    {
        $this->___init();
        parent::__construct($context, $aclBuilder, $logger, $rootResource, $aclDataCache, $connectionName);
    }

    /**
     * {@inheritdoc}
     */
    public function saveRel(\Magento\Authorization\Model\Rules $rule)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'saveRel');
        return $pluginInfo ? $this->___callPlugins('saveRel', func_get_args(), $pluginInfo) : parent::saveRel($rule);
    }
}
