<?php

namespace Paylabs\Payment\Logger\Handler;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;

/**
 * Class ApiRequestHandler
 * Custom log handler for Paylabs payment module that logs API request messages to the `paylabs_api_requests.log` file.
 *
 * This class extends Magento's `BaseHandler` and sets up a custom file (`paylabs_api_requests.log`) for logging
 * API request messages at the INFO log level. This helps track outgoing API requests to external services.
 *
 * @package Paylabs\Payment\Logger\Handler
 */
class ApiRequestHandler extends StreamHandler
{
    public function __construct()
    {
        parent::__construct(BP . '/var/log/paylabs_api_requests.log', \Monolog\Logger::INFO);
        $this->setFormatter(new LineFormatter(null, null, true, true));
    }
}
