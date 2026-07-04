<?php
namespace Magento\OrderCancellation\Model\CancelOrder;

/**
 * Interceptor class for @see \Magento\OrderCancellation\Model\CancelOrder
 */
class Interceptor extends \Magento\OrderCancellation\Model\CancelOrder implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Sales\Model\RefundInvoice $refundInvoice, \Magento\Sales\Model\RefundOrder $refundOrder, \Magento\Sales\Api\OrderRepositoryInterface $orderRepository, \Magento\Framework\Escaper $escaper, \Magento\Sales\Model\Order\Email\Sender\OrderCommentSender $sender)
    {
        $this->___init();
        parent::__construct($refundInvoice, $refundOrder, $orderRepository, $escaper, $sender);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(\Magento\Sales\Model\Order $order, string $reason): \Magento\Sales\Model\Order
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'execute');
        return $pluginInfo ? $this->___callPlugins('execute', func_get_args(), $pluginInfo) : parent::execute($order, $reason);
    }
}
