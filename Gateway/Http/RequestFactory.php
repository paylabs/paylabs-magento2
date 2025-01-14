<?php

namespace Paylabs\Payment\Gateway\Http;

use Paylabs\Payment\Gateway\Config\Config;
use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class RequestFactory
 * Factory for creating TransferInterface objects for making HTTP requests.
 *
 * This class is responsible for building and configuring a `TransferInterface` object,
 * which contains all the necessary information to make an HTTP request, such as method, headers, URI, and body.
 * The factory fetches configuration values from the module's settings, such as the base URL, API key, and business ID.
 *
 * @package Paylabs\Payment\Gateway\Http
 */
class RequestFactory
{
    /**
     * @var TransferBuilder Builder for constructing TransferInterface objects.
     */
    protected TransferBuilder $transferBuilder;

    /**
     * @var Config Configuration class to fetch base URL and other settings.
     */
    protected Config $config;

    /**
     * RequestFactory constructor.
     *
     * @param TransferBuilder $transferBuilder The builder used to create TransferInterface objects.
     * @param Config $config The configuration class containing API and other module-related settings.
     */
    public function __construct(
        TransferBuilder $transferBuilder,
        StoreManagerInterface $storeManager,
        Config $config
    ) {
        $this->transferBuilder = $transferBuilder;
        $this->config = $config;
    }

    /**
     * Creates and returns a TransferInterface object configured with the given request details.
     *
     * @param string $method The HTTP method (e.g., POST, GET) for the request.
     * @param string $apiEndpoint The API endpoint to send the request to.
     * @param array $request The body of the request, typically as key-value pairs.
     * @return TransferInterface The configured TransferInterface object.
     */
    public function create(string $method, string $apiEndpoint, array $request): TransferInterface
    {
        // Fetch configuration values
        $baseUrl = $this->config->getBaseUrl();
        $apiKey = $this->config->getModuleConfig()->getApiSecretKey();

        // Build the TransferInterface object with necessary details
        return $this->transferBuilder
            ->setMethod(method: $method)
            ->setHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic ' . base64_encode($apiKey . ':'),
                'User-Agent' => 'Paylabs-Magento-Module',
                'Request-Business-Id' => $this->config->getModuleConfig()->getMerchantId(),
                'Module-Version' => '1.0.0',
            ])
            ->setUri($baseUrl . $apiEndpoint)
            ->setBody($request)
            ->build();
    }
}
