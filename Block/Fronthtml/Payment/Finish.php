<?php

namespace Paylabs\Payment\Block\Fronthtml\Payment;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order;

/**
 * Class Finish
 *
 * Block class responsible for handling the "Finish" page for payments in the Paylabs Payment module.
 * Provides methods to access order details, customer session, and generate order view URLs.
 *
 * @package Paylabs\Payment\Block\Fronthtml\Payment
 */
class Finish extends Template
{
    /**
     * @var CustomerSession
     * Provides access to the current customer's session data.
     */
    protected CustomerSession $customerSession;

    /**
     * @var UrlInterface
     * Magento's URL builder for generating URLs.
     */
    protected UrlInterface $urlBuilder;

    /**
     * Finish constructor.
     *
     * @param Context $context The context object providing various Magento framework utilities.
     * @param CustomerSession $customerSession The customer session for accessing logged-in customer data.
     * @param UrlInterface $urlBuilder The URL builder for generating URLs.
     * @param array $data Optional additional data to initialize the block.
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        UrlInterface $urlBuilder,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->customerSession = $customerSession;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Get Order Data
     *
     * Retrieves the current order data passed to the block.
     *
     * @return Order|null The current order object or null if not set.
     */
    public function getOrder(): ?Order
    {
        return $this->getData('order');
    }

    /**
     * Get the customer session
     *
     * Provides access to the customer session, allowing retrieval of customer-related data.
     *
     * @return CustomerSession The customer session object.
     */
    public function getCustomerSession(): CustomerSession
    {
        return $this->customerSession;
    }

    /**
     * Get the order view URL
     *
     * Generates the URL for viewing a specific order in the customer's order history.
     *
     * @param int|string $orderId The ID of the order.
     * @return string The generated order view URL.
     */
    public function getOrderViewUrl(int|string $orderId): string
    {
        return $this->urlBuilder->getUrl('sales/order/view', ['order_id' => $orderId]);
    }
}
