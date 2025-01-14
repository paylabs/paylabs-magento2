<?php

namespace Paylabs\Payment\Model\Order;


use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Api\Data\CreditmemoInterfaceFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;

class CreditMemoRepository
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var CreditmemoInterfaceFactory
     */
    private $creditmemoFactory;

    /**
     * @var CreditmemoManagementInterface
     */
    private $creditmemoManagement;

    /**
     * Constructor
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param CreditmemoInterfaceFactory $creditmemoFactory
     * @param CreditmemoManagementInterface $creditmemoManagement
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        CreditmemoInterfaceFactory $creditmemoFactory,
        CreditmemoManagementInterface $creditmemoManagement
    ) {
        $this->orderRepository = $orderRepository;
        $this->creditmemoFactory = $creditmemoFactory;
        $this->creditmemoManagement = $creditmemoManagement;
    }

    /**
     * Create a credit memo for an order
     *
     * @param int $orderId
     * @throws LocalizedException
     */
    public function createCreditMemo(Order $order): void
    {
        if (!$order->canCreditmemo()) {
            throw new LocalizedException(__('Credit memo cannot be created for this order.'));
        }

        // Prepare the invoice for the credit memo
        $invoice = $order->getInvoiceCollection()->getFirstItem();
        if (!$invoice || !$invoice->getId()) {
            throw new LocalizedException(__('No invoice found for this order.'));
        }

        // Create the credit memo
        $creditMemo = $this->creditmemoFactory->createByOrder($order, ['adjustment_positive' => 0, 'adjustment_negative' => 0]);
        $creditMemo->setInvoice($invoice);
        $creditMemo->setOfflineRequested(true); // Mark as offline refund (no payment gateway integration)

        // Save the credit memo
        $this->creditmemoManagement->refund($creditMemo);
    }
}
