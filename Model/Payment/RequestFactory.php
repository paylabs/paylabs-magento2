<?php

namespace Paylabs\Payment\Model\Payment;

use Paylabs\Payment\Model\Config\Payment\ModuleConfig;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class RequestFactory
 * Factory class for creating the payload to send to the Paylabs API for payment creation.
 *
 * @package Paylabs\Payment\Model\Payment
 */
class RequestFactory
{
    private StoreManagerInterface $storeManager;

    public function __construct(StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
    }

    /**
     * Creates the payload array for sending to the Paylabs API.
     *
     * This method prepares an array with the necessary details of the order, such as the order ID, amount,
     * customer information, and payment redirect URL. The data is formatted as required by the Paylabs API for
     * creating a payment link.
     *
     * @param Order $order The order object containing details about the purchase.
     * @return array The prepared payload to be sent to the Paylabs API.
     * @throws NoSuchEntityException
     */
    public function createPayload(Order $order): array
    {
        $finishUrl = $this->storeManager->getStore()->getUrl() . 'paylabs/payment/finish?state=' . $order->getRealOrderId();
        return [
            'title' => $order->getRealOrderId(),
            'type' => "SINGLE",
            'amount' => (string)round($order->getGrandTotal()), // The amount for the payment
            'step' => 2, // Payment step, could be a value required by the Paylabs API
            'redirect_url' => $this->bypassPaylabsUrlValidation($finishUrl),
            'sender_name' => $order->getCustomerName(), // Customer's full name
            'sender_email' => $order->getCustomerEmail(), // Customer's email address
        ];
    }

    private function bypassPaylabsUrlValidation(string $url): string
    {
        return 'https://xaxxis.github.io/handle-redirection/?url=' . $url;
    }
}
