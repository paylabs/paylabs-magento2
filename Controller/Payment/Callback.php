<?php

namespace Paylabs\Payment\Controller\Payment;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;

/**
 * Callback class to handle Paylabs payment callback notifications.
 *
 * @package Paylabs\Payment\Controller\Payment
 */
class Callback extends AbstractAction implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * Create a CSRF validation exception.
     *
     * @param RequestInterface $request The incoming request.
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * Validate the request for CSRF.
     *
     * @param RequestInterface $request The incoming request.
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Execute the callback action.
     *
     * @return ResultInterface JSON response containing the status of the callback processing.
     */
    public function execute(): ResultInterface
    {
        $resultJson = $this->_resultJsonFactory->create();

        try {
            // Validate request method.
            if (!$this->getRequest()->isPost()) {
                throw new LocalizedException(__('Invalid request method'));
            }

            $signature = $this->getHeadersData('X-SIGNATURE');
            $dateTime = $this->getHeadersData('X-TIMESTAMP');
            if (!isset($signature) || !isset($dateTime)) {
                $errorResponse = [
                    'status_code' => 500,
                    'status' => 'error',
                    'message' => 'callback not valid'
                ];
                return $resultJson->setData($errorResponse);
            }

            $rawBody = $this->getRequest()->getContent();
            $this->logger->logDebug('[PAYLABS] Signature : ' . $signature);
            $this->logger->logDebug('[PAYLABS] Date Time : ' . $dateTime);
            $this->logger->logDebug('[PAYLABS] Raw Body: ' . $rawBody);

            $parseBody = json_decode($rawBody, true);
            $verify = $this->paylabsService->verifySign("/paylabs/payment/callback", $rawBody, $signature, $dateTime);
            if ($verify !== true) {
                $errorResponse = [
                    'status_code' => 500,
                    'status' => 'error',
                    'message' => 'signature not valid'
                ];
                return $resultJson->setData($errorResponse);
            }

            if ($parseBody['errCode'] == "0" && $parseBody['status'] == "02") {
                $this->processPayment($parseBody);

                $this->logger->logDebug('[PAYLABS] Verify Callback: ' . $verify);
                $resp = $this->paylabsService->responseCallback("/paylabs/payment/callback/");
                foreach ($resp['headers'] as $headerName => $headerValue) {
                    $this->_response->setHeader($headerName, $headerValue, true);
                }

                return $resultJson->setData($resp['body']);
            }
        } catch (\Exception $e) {
            $errorResponse = [
                'status_code' => 500,
                'status' => 'error',
                'message' => 'An error occurred while processing the payment ' . $e->getMessage()
            ];
            $this->logger->logDebug('Error: ' . $e->getMessage());
            return $resultJson->setData($errorResponse);
        }
    }

    private function getHeadersData($key)
    {
        $headersData = $this->getRequest()->getHeaders($key);
        if ($headersData) {
            // Mendapatkan nilai dari header
            $value = $headersData->getFieldValue();
            return $value;
        }
        return false;
    }

    private function processPayment(array $data): void
    {
        if (isset($data['productName'])) {
            $split = explode("-", $data['productName']);
            $orderId = str_replace("#", "", $split[0]);
            $order = $this->orderRepository->getOrderById($orderId);
            if ($order->getId()) {
                $paymentMethod = strtoupper($data['paymentType']);
                $this->orderRepository->setStateAndStatus(
                    $order,
                    Order::STATE_PROCESSING,
                    "<strong style='color: green;'>Payment Successfully!</strong><br>" .
                        "- Payment Method: {$paymentMethod}<br> - Order Id : {$data['platformTradeNo']}",
                    true
                );
                $this->orderRepository->setAdditionalPaymentInfo($order, 'paylabs_trx_id', $orderId);
                $this->orderRepository->saveOrder($order);

                $this->invoiceRepository->createInvoice($order, $data);
            }
            $this->logger->logDebug(json_encode($order));

            if (isset($order)) {
            } else {
                throw new LocalizedException(__('Order not found ' . $orderId));
            }
        }
    }
}
