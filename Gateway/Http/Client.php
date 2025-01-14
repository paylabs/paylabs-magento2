<?php

namespace Paylabs\Payment\Gateway\Http;

use Paylabs\Payment\Logger\PaylabsLogger;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Framework\HTTP\Client\Curl;

/**
 * Class Client
 * Implements the ClientInterface for sending HTTP requests to an API using cURL.
 *
 * This class is responsible for sending HTTP POST requests to an API using the provided `Curl` client.
 * It logs detailed information about the request and response for debugging purposes using the `PaylabsLogger`.
 *
 * @package Paylabs\Payment\Gateway\Http
 */
class Client implements ClientInterface
{
    /**
     * @var Curl The cURL client used to send HTTP requests.
     */
    protected Curl $curl;

    /**
     * @var PaylabsLogger Logger for logging API requests and responses.
     */
    private PaylabsLogger $logger;

    /**
     * Client constructor.
     *
     * @param Curl $curl The cURL client used for sending requests.
     * @param PaylabsLogger $logger The logger to capture debug information.
     */
    public function __construct(Curl $curl, PaylabsLogger $logger)
    {
        $this->curl = $curl;
        $this->logger = $logger;
    }

    /**
     * Sends an HTTP request to the specified API endpoint using cURL.
     * Logs the request and response details for debugging.
     *
     * @param TransferInterface $transferObject Contains the request data such as URI, headers, and body.
     * @return array The API response decoded from JSON format.
     */
    public function placeRequest(TransferInterface $transferObject): array
    {
        $url = $transferObject->getUri();
        $headers = $transferObject->getHeaders();
        $body = $transferObject->getBody();

        // Set the request headers and send the POST request
        $this->curl->setHeaders($headers);
        $this->curl->post($url, $body);

        // Get the response and decode the JSON
        $response = json_decode($this->curl->getBody(), true);

        // Log the request and response details for debugging
        $this->logger->logApiRequest(
            "API Call Details:\n" .
                "======================================== REQUEST ========================================\n" .
                "Request URL: $url\n" .
                "Request Headers: " . json_encode($headers, JSON_PRETTY_PRINT) . "\n" .
                "Request Body: " . json_encode($body, JSON_PRETTY_PRINT) . "\n" .
                "======================================= RESPONSE ========================================\n" .
                "API Response: " . print_r($response, true) . "\n" .
                "========================================================================================="
        );

        // Return the API response
        return $response;
    }
}
