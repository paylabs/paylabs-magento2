<?php

namespace Paylabs\Payment\Service;

use Paylabs\Payment\Gateway\Http\RequestFactory;
use Paylabs\Payment\Gateway\Http\Client;
use Paylabs\Payment\Model\Config\Payment\ModuleConfig;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ConverterException;

/**
 * Class PaylabsService
 * Provides services for interacting with the Paylabs API, such as creating a bill or other transactions.
 *
 * @package Paylabs\Payment\Service
 */
class PaylabsService1
{
    /**
     * @var RequestFactory
     */
    private RequestFactory $requestFactory;

    /**
     * @var ModuleConfig
     */
    private ModuleConfig $moduleConfig;

    /**
     * @var Client
     */
    private Client $client;

    /**
     * Configuration properties
     */
    private string $mid;
    private string $version;
    private string $privateKey;
    private string $publicKey;
    private string $server;
    private ?array $requestBody = null;

    /**
     * PaylabsService constructor.
     *
     * @param RequestFactory $requestFactory Factory for creating HTTP requests.
     * @param Client $client HTTP client for making requests to the Paylabs API.
     * @param ModuleConfig $moduleConfig Configuration for the module.
     */
    public function __construct(
        RequestFactory $requestFactory,
        Client $client,
        ModuleConfig $moduleConfig
    ) {
        $this->requestFactory = $requestFactory;
        $this->client = $client;
        $this->moduleConfig = $moduleConfig;

        // Load configuration from ModuleConfig
        $this->mid = $moduleConfig->getMid();
        $this->version = $moduleConfig->getVersion();
        $this->privateKey = $moduleConfig->getPrivateKey();
        $this->publicKey = $moduleConfig->getPublicKey();
        $this->server = $moduleConfig->getServer();
    }

    /**
     * Configure request body for H5 payment.
     *
     * @param float $amount
     * @param string $phone
     * @param string $product
     * @param string $redirectUrl
     * @param string|null $payer
     * @return self
     */
    public function setH5(float $amount, string $phone, string $product, string $redirectUrl, ?string $payer = null): self
    {
        $this->requestBody = [
            'mid' => $this->mid,
            'version' => $this->version,
            'privateKey' => $this->privateKey,
            'product' => $product,
            'amount' => $amount,
            'payerPhone' => $phone,
            'payerName' => $payer ?? 'Anonymous',
            'redirectUrl' => $redirectUrl,
        ];

        return $this;
    }

    /**
     * Send request to Paylabs API.
     *
     * @return array|null
     * @throws LocalizedException
     */
    public function request(): ?array
    {
        if (!$this->requestBody) {
            throw new LocalizedException(__('Request body is not set.'));
        }

        // API Endpoint for H5
        $apiEndpoint = $this->server === 'PROD' ? 'https://prod.paylabs/api/v1/pwf/h5' : 'https://sit.paylabs/api/v1/pwf/h5';

        // Create transfer object
        $transfer = $this->requestFactory->create(
            method: 'POST',
            apiEndpoint: $apiEndpoint,
            request: $this->requestBody
        );

        try {
            // Send request to API
            $response = $this->client->placeRequest($transfer);

            // Validate response status
            if (isset($response['status']) && $response['status'] !== 'SUCCESS') {
                throw new LocalizedException(
                    __('API request failed with status: %1. Message: %2', $response['status'], $response['message'] ?? 'Unknown error')
                );
            }

            return $response;
        } catch (ClientException | ConverterException $e) {
            throw new LocalizedException(
                __('An error occurred during API communication: %1', $e->getMessage())
            );
        }
    }
}
