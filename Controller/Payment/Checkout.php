<?php

namespace Paylabs\Payment\Controller\Payment;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

class Checkout extends AbstractAction implements HttpPostActionInterface, CsrfAwareActionInterface
{

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null; // Return null jika CSRF tidak diperlukan
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true; // Validasi CSRF diaktifkan
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $resultJson = $this->_resultJsonFactory->create();

        try {
            $order = $this->_checkoutSession->getLastRealOrder();
            $this->logger->logDebug("[PAYLABS] Order ID : " . $order->getId());
            if (!$order || !$order->getId()) {
                return $resultJson->setData([
                    'status_code' => 400,
                    'status' => 'error',
                    'message' => 'Cart is inactive or no active quote found.'
                ]);
            }

            $orderId = $order->getIncrementId();
            $grandTotal = $order->getGrandTotal();
            $grandTotalFormatted = number_format($grandTotal, 2, '.', '');
            $customerEmail = $order->getCustomerEmail();
            $customerName = $order->getCustomerName();

            $this->logger->logDebug("[PAYLABS] Order ID : " . $orderId);
            $this->logger->logDebug("[PAYLABS] Order Total: " . $grandTotalFormatted);
            $this->logger->logDebug("[PAYLABS] Cust Name: " . $customerName);

            $finishUrl = $this->storeManager->getStore()->getUrl('paylabs/payment/finish', ['state' => $orderId]);
            $callbackUrl = $this->storeManager->getStore()->getUrl('paylabs/payment/callback');
            $callbackUrl = rtrim($callbackUrl, '/');
            $h5Data = $this->paylabsService->setH5($grandTotalFormatted, "00000000", "#" . $orderId . "-" . $customerName, $finishUrl, $customerName, null, $callbackUrl);
            $responseApi = $this->paylabsService->request();

            $this->logger->logDebug("[PAYLABS] resp api : " . json_encode($responseApi, true));

            if (!isset($responseApi['url'])) {
                throw new \Exception('Invalid response from Paylabs API');
            }

            return $resultJson->setData([
                'status_code' => 201,
                'status' => 'success',
                'message' => 'Payment link successfully created',
                'payment_url' => $responseApi['url']
            ]);
        } catch (\Exception $e) {
            $this->logger->logDebug("Error in Checkout Controller: " . $e->getMessage());
            return $resultJson->setData([
                'status_code' => 500,
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
