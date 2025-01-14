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
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository as MagentoOrderRepository;

/**
 * Magento Order Repository
 */
class OrderRepository
{
    /**
     * @var Order
     */
    protected Order $order;

    /**
     * @var ObjectManagerInterface
     */
    protected ObjectManagerInterface $objectManager;

    /**
     * @var PaylabsLogger
     */
    protected PaylabsLogger $logger;

    /**
     * @var MagentoOrderRepository
     */
    protected MagentoOrderRepository $magentoOrderRepository;

    /**
     * @var Transaction
     */
    protected Transaction $transaction;

    /**
     * @var MessageManagerInterface
     */
    protected MessageManagerInterface $messageManager;

    /**
     * @var SearchCriteriaBuilder
     */
    protected SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var ModuleConfig
     */
    protected ModuleConfig $moduleConfig;

    /**
     * Order Repository Constructor
     *
     * @param Order $order
     * @param ObjectManagerInterface $objectManager
     * @param MagentoOrderRepository $magentoOrderRepository
     * @param PaylabsLogger $logger
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ModuleConfig $moduleConfig
     */
    public function __construct(
        Order $order,
        ObjectManagerInterface $objectManager,
        MagentoOrderRepository $magentoOrderRepository,
        PaylabsLogger $logger,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ModuleConfig $moduleConfig,
    ) {
        $this->order = $order;
        $this->objectManager = $objectManager;
        $this->magentoOrderRepository = $magentoOrderRepository;
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
    public function getOrderById(int $orderId): ?OrderInterface
    {
        try {
            return $this->magentoOrderRepository->get($orderId);
        } catch (InputException $e) {
            $this->logger->logErrorException("OrderRepository.class->getOrderById: Invalid input for fetching order with ID: {$orderId}.", $e);
        } catch (NoSuchEntityException $e) {
            $this->logger->logErrorException("OrderRepository.class->getOrderById: Order with ID {$orderId} does not exist.", $e);
        } catch (\Exception $e) {
            $this->logger->logErrorException("OrderRepository.class->getOrderById: Unknown Error.", $e);
        }
        return null;
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
            $savedOrder = $this->magentoOrderRepository->save($order);
            $this->logger->logDebug(
                "OrderRepository.class->saveOrder(): Order with ID {$order->getIncrementId()} has been saved successfully. Data: OrderStatus: {$order->getStatus()}, OrderState: {$order->getState()}, PaylabsLinkId: {$order->getExtOrderId()}"
            );
            return $savedOrder;
        } catch (\Exception $e) {
            $this->logger->logErrorException("OrderRepository.class->saveOrder(): Unable to save the order.", $e);
            return null;
        }
    }

    /**
     * Set additional payment information to order object
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
     * Get order object by Paylabs Link Id
     *
     * @param string $linkId
     * @return OrderInterface|null
     */
    public function getOrderByLinkId(string $linkId): null|OrderInterface
    {
        $businessId = $this->moduleConfig->getMerchantId();
        // Build search criteria
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('ext_order_id', $businessId . '-' . $linkId)
            ->create();

        // Retrieve orders
        $orderList = $this->magentoOrderRepository->getList($searchCriteria);

        // Return the first order if it exists
        $items = $orderList->getItems();
        if (!empty($items)) {
            return reset($items); // Return the first matching order
        }

        return null; // No order found
    }

    /**
     * Function to set State and Status order, also can add comment in the status history
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
     * Check is order contain virtual product
     *
     * @param $incrementId
     * @return bool
     */
    public function isContainVirtualProduct($incrementId): bool
    {
        $isVirtual = false;
        $items = $this->getOrderById($incrementId)->getAllItems();
        foreach ($items as $item) {
            //look for virtual products
            if ($item->getProduct()->getIsVirtual()) {
                $isVirtual = true;
            }
        }
        return  $isVirtual;
    }
}
