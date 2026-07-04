<?php
namespace Magento\EncryptionKey\Console\Command\ReEncryptDataCommand;

/**
 * Interceptor class for @see \Magento\EncryptionKey\Console\Command\ReEncryptDataCommand
 */
class Interceptor extends \Magento\EncryptionKey\Console\Command\ReEncryptDataCommand implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\EncryptionKey\Model\Data\ReEncryptorList $reEncryptorList)
    {
        $this->___init();
        parent::__construct($reEncryptorList);
    }

    /**
     * {@inheritdoc}
     */
    public function run(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output): int
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'run');
        return $pluginInfo ? $this->___callPlugins('run', func_get_args(), $pluginInfo) : parent::run($input, $output);
    }
}
