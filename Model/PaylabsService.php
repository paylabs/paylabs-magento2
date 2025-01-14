<?php

namespace Paylabs\Payment\Model;

date_default_timezone_set('Asia/Jakarta');

use Magento\Framework\HTTP\Client\Curl;
use Paylabs\Payment\Model\Config\Payment\ModuleConfig;
use Psr\Log\LoggerInterface;

class PaylabsService
{

    /**
     * @var ModuleConfig
     */
    private ModuleConfig $moduleConfig;

    private $server = "SIT";
    private $mid;
    private $version = "v2.1";
    private $endpoint = "/payment/";
    private $urlProd = "https://pay.paylabs.co.id/payment/";
    private $urlSit = "https://sit-pay.paylabs.co.id/payment/";
    private $log = false;
    private $privateKey;
    private $publicKey;
    private $date;
    private $idRequest;
    private $merchantTradeNo;
    private $notifyUrl;
    private $signature;
    private $path;
    private $headers;
    private $body;

    private $curl;
    private $logger;

    public function __construct(
        Curl $curl,
        LoggerInterface $logger,
        ModuleConfig $moduleConfig
    ) {
        $this->curl = $curl;
        $this->logger = $logger;
        $this->date = date("Y-m-d") . "T" . date("H:i:s.B") . "+07:00";
        $this->idRequest = strval(date("YmdHis") . rand(11111, 99999));
        $this->merchantTradeNo = $this->idRequest;

        $this->moduleConfig = $moduleConfig;

        // Load configuration from ModuleConfig
        $this->mid = $moduleConfig->getMerchantId();
        $this->privateKey = $moduleConfig->getMerchantPrivateKey();
        $this->publicKey = $moduleConfig->getPaylabsPublicKey();
        $this->server = $moduleConfig->isProd();

        $this->logger->info("[PAYLABS] MID", ['mid' => $this->mid]);
        $this->logger->info("[PAYLABS] Private Key", ['private_key' => $this->privateKey]);
        $this->logger->info("[PAYLABS] Public Key", ['public_key' => $this->publicKey]);
    }

    public function setNotifyUrl($url)
    {
        $this->notifyUrl = $url;
    }

    private function getUrl()
    {
        return ($this->server === true ? $this->urlProd : $this->urlSit) . $this->version;
    }

    private function getEndpoint()
    {
        return $this->endpoint . $this->version;
    }

    private function setHeaders()
    {
        $this->headers = [
            'X-TIMESTAMP' => $this->date,
            'X-SIGNATURE' => $this->signature,
            'X-PARTNER-ID' => $this->mid,
            'X-REQUEST-ID' => $this->idRequest,
            'Content-Type' => 'application/json'
        ];

        return $this->headers;
    }

    public function setH5($amount, $phoneNumber, $product, $redirectUrl, $payer = "Testing", $storeId = null, $notifyUrl = null)
    {
        $this->path = "/h5/createLink";
        $this->body = [
            'merchantId' => $this->mid,
            'merchantTradeNo' => $this->merchantTradeNo,
            'requestId' => $this->idRequest,
            'amount' => $amount,
            'phoneNumber' => $phoneNumber,
            'productName' => $product,
            'redirectUrl' => $redirectUrl,
            'payer' => $payer
        ];

        if (!is_null($storeId)) {
            $this->body['storeId'] = strval($storeId);
        }
        if (!is_null($notifyUrl)) {
            $this->body['notifyUrl'] = $notifyUrl;
        }

        return $this->body;
    }

    public function generateSign()
    {
        $shaJson = strtolower(hash('sha256', json_encode($this->body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
        $signatureBefore = "POST:" . $this->getEndpoint() . $this->path . ":" . $shaJson . ":" . $this->date;
        $binarySignature = "";

        openssl_sign($signatureBefore, $binarySignature, $this->privateKey, OPENSSL_ALGO_SHA256);

        $this->signature = base64_encode($binarySignature);
        return $this->signature;
    }

    public function request()
    {
        $this->generateSign();
        $this->setHeaders();
        return $this->post();
    }

    public function post()
    {
        $this->logger->info("[PAYLABS] Full URL: " . $this->getUrl() . $this->path);
        $this->logger->info("[PAYLABS] Headers: " . json_encode($this->headers));
        $this->logger->info("[PAYLABS] Body: " . json_encode($this->body));

        $this->curl->setHeaders($this->headers);
        $this->curl->post($this->getUrl() . $this->path, json_encode($this->body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $response = $this->curl->getBody();
        $this->logger->info("[PAYLABS] Response: " . $response);

        return json_decode($response, true);
    }
}
