<?php

namespace Paylabs\Payment\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;

class Callback extends Action
{
    protected $_resultJsonFactory;
    protected $logger;

    public function __construct(
        Context $context,
        LoggerInterface $logger,
        JsonFactory $resultJsonFactory
    ) {
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->logger = $logger;
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $resultJson = $this->_resultJsonFactory->create();

        try {
            // Logging Headers
            $headers = $this->getRequest()->getHeaders();
            $this->logger->debug('Request Headers: ' . print_r($headers, true));

            // Logging Body
            $rawBody = $this->getRequest()->getContent();
            $this->logger->debug('Request Body: ' . $rawBody);

            // Jika Body berupa JSON, deseralisasi menjadi array
            try {
                $data = $this->jsonSerializer->unserialize($rawBody);
                $this->logger->debug('Decoded Body: ' . print_r($data, true));
            } catch (\InvalidArgumentException $e) {
                $this->logger->error('Invalid JSON in request body: ' . $e->getMessage());
            }

            // Logic lanjutan seperti sebelumnya...
            // Example:
            $response = [
                'status_code' => 200,
                'status' => 'success',
                'message' => 'Payment processed successfully!'
            ];

            return $resultJson->setData($response);
        } catch (\Exception $e) {
            $errorResponse = [
                'status_code' => 500,
                'status' => 'error',
                'message' => 'An error occurred while processing the payment'
            ];
            return $resultJson->setData($errorResponse);
        }
    }
}
