<?php

namespace Paylabs\Payment\Model\Order;

use Paylabs\Payment\Logger\PaylabsLogger;
use Paylabs\Payment\Model\Config\Payment\ModuleConfig;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Magento Order Repository
 */
class OrderRepository
{
    protected ObjectManagerInterface $objectManager;
    protected PaylabsLogger $logger;
    protected OrderRepositoryInterface $orderRepositoryInterface;
    protected Transaction $transaction;
    protected MessageManagerInterface $messageManager;
    protected SearchCriteriaBuilder $searchCriteriaBuilder;
    protected ModuleConfig $moduleConfig;

    public function __construct(
        ObjectManagerInterface $objectManager,
        OrderRepositoryInterface $orderRepositoryInterface,
        PaylabsLogger $logger,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ModuleConfig $moduleConfig
    ) {
        $this->objectManager = $objectManager;
        $this->orderRepositoryInterface = $orderRepositoryInterface;
        $this->logger = $logger;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->moduleConfig = $moduleConfig;
    }

    /**
     * Fetch order by ID.
     *
     * @param int $orderId
     * @return OrderInterface|null
     */
    public function getOrderById(string $orderId): ?OrderInterface
    {
        try {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('increment_id', $orderId) // Use 'increment_id' for string-based order IDs
                ->create();

            $orderList = $this->orderRepositoryInterface->getList($searchCriteria);

            $items = $orderList->getItems();
            if (!empty($items)) {
                return reset($items); // Return the first matching order
            }

            $this->logger->logDebug("Order with Increment ID {$orderId} not found.");
        } catch (\Exception $e) {
            $this->logger->logErrorException("Error fetching order with Increment ID: {$orderId}", $e);
        }

        return null; // Return null if not found
    }

    /**
     * Save the given order.
     *
     * @param OrderInterface $order
     * @return OrderInterface|null
     */
    public function saveOrder(OrderInterface $order): ?OrderInterface
    {
        try {
            $savedOrder = $this->orderRepositoryInterface->save($order);
            $this->logger->logDebug("Order with ID {$order->getIncrementId()} has been saved successfully.");
            return $savedOrder;
        } catch (\Exception $e) {
            $this->logger->logErrorException("Unable to save the order.", $e);
        }
        return null;
    }

    /**
     * Set additional payment information to order object.
     *
     * @param OrderInterface $order
     * @param string $key
     * @param string $value
     * @return void
     */
    public function setAdditionalPaymentInfo(OrderInterface $order, string $key, string $value): void
    {
        $payment = $order->getPayment();
        $payment->setAdditionalInformation($key, $value);
    }

    /**
     * Get order object by Paylabs Link ID.
     *
     * @param string $linkId
     * @return OrderInterface|null
     */
    public function getOrderByLinkId(string $linkId): ?OrderInterface
    {
        $businessId = $this->moduleConfig->getMerchantId();
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('ext_order_id', $businessId . '-' . $linkId)
            ->create();

        $orderList = $this->orderRepositoryInterface->getList($searchCriteria);

        $items = $orderList->getItems();
        if (!empty($items)) {
            return reset($items);
        }

        $this->logger->logDebug("No order found with Link ID: {$linkId}");
        return null;
    }

    /**
     * Set state and status of the order.
     *
     * @param OrderInterface $order
     * @param string $statusState
     * @param string $notes
     * @param bool $isCustomerNotified
     * @return void
     */
    public function setStateAndStatus(OrderInterface $order, string $statusState, string $notes = '', bool $isCustomerNotified = false): void
    {
        $order->setState($statusState)->setStatus($statusState);
        $order->addStatusToHistory($statusState, $notes, $isCustomerNotified);
    }

    /**
     * Check if order contains virtual products.
     *
     * @param int $orderId
     * @return bool
     */
    public function isContainVirtualProduct(int $orderId): bool
    {
        $order = $this->getOrderById($orderId);
        if (!$order) {
            $this->logger->logDebug("Order with ID {$orderId} not found.");
            return false;
        }

        foreach ($order->getAllItems() as $item) {
            if ($item->getProduct() && $item->getProduct()->getIsVirtual()) {
                return true;
            }
        }
        return false;
    }
}
