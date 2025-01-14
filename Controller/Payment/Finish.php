<?php

namespace Paylabs\Payment\Controller\Payment;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NotFoundException;

/**
 * Finish Controller
 *
 * Handles the finish step of the Paylabs Payment payment process.
 * Displays a confirmation page with order details after the payment is processed successfully.
 *
 * @package Paylabs\Payment\Controller\Payment
 */
class Finish extends AbstractAction implements HttpGetActionInterface, CsrfAwareActionInterface
{
    /**
     * Create an exception for CSRF validation failure.
     *
     * @param RequestInterface $request The incoming request.
     * @return InvalidRequestException|null Null as CSRF validation is bypassed.
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * Validate the request for CSRF.
     *
     * @param RequestInterface $request The incoming request.
     * @return bool|null Always returns true to skip CSRF validation.
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Execute the controller action to display the payment confirmation page.
     *
     * @return ResultInterface Result page or redirect response.
     * @throws NotFoundException If the requested page or data is not found.
     */
    public function execute(): ResultInterface
    {
        $orderId = $this->requestInterface->getParam('state');
        if (!$orderId) {
            $this->logger->logErrorException("Order ID missing in request.");
            return $this->redirectFactory->create()->setPath('checkout/cart');
        }

        try {
            // Fetch order by ID
            $order = $this->orderRepository->getOrderById($orderId);
            if (!$order || !$order->getId()) {
                throw new \Exception("Order not found for ID: {$orderId}");
            }

            // Create result page
            $resultPage = $this->pageFactory->create();
            $resultPage->getConfig()->getTitle()->set(__('Payment Order Confirmation'));

            // Find the layout block and set order data
            $finishBlock = $resultPage->getLayout()->getBlock('finish.page');
            if (!$finishBlock) {
                throw new \Exception("Block 'finish.page' not found in layout.");
            }

            $finishBlock->setData('order', $order);
            return $resultPage;
        } catch (\Exception $e) {
            $this->logger->critical("Error in Finish Controller: " . $e->getMessage());
            return $this->redirectFactory->create()->setPath('checkout/cart');
        }
    }
}
