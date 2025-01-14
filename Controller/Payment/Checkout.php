<?php

namespace Paylabs\Payment\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
// use Paylabs\Payment\Model\Service\PaylabsService;
use Paylabs\Payment\Model\PaylabsService;

class Checkout extends Action implements HttpPostActionInterface, CsrfAwareActionInterface
{
    protected $resultJsonFactory;
    protected $checkoutSession;
    protected $storeManager;
    protected $logger;
    protected $paylabsService;

    public function __construct(
        JsonFactory $resultJsonFactory,
        CheckoutSession $checkoutSession,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        PaylabsService $paylabsService,
        \Magento\Framework\App\Action\Context $context
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->paylabsService = $paylabsService;
    }

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
        $resultJson = $this->resultJsonFactory->create();

        try {
            $order = $this->checkoutSession->getLastRealOrder();

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

            // Log debugging
            $this->logger->info("[PAYLABS] Order ID : " . $orderId);
            $this->logger->info("[PAYLABS] Order Total: " . $grandTotalFormatted);
            $this->logger->info("[PAYLABS] Cust Name: " . $customerName);

            // Call Paylabs API
            $finishUrl = $this->storeManager->getStore()->getUrl('paylabs/payment/finish', ['state' => $orderId]);
            $callbackUrl = $this->storeManager->getStore()->getUrl('paylabs/payment/callback', ['_nosid' => true, '_direct' => false]);
            $callbackUrl = rtrim($callbackUrl, '/');
            $h5Data = $this->paylabsService->setH5($grandTotalFormatted, "00000000", "#" . $orderId . "-" . $customerName, $finishUrl, $customerName, null, $callbackUrl);
            $responseApi = $this->paylabsService->request();

            $this->logger->info(json_encode($responseApi, true));

            if (!isset($responseApi['url'])) {
                throw new \Exception('Invalid response from Paylabs API');
            }

            // Return success response
            return $resultJson->setData([
                'status_code' => 201,
                'status' => 'success',
                'message' => 'Payment link successfully created',
                'payment_url' => $responseApi['url']
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Error in Checkout Controller: " . $e->getMessage());
            return $resultJson->setData([
                'status_code' => 500,
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
