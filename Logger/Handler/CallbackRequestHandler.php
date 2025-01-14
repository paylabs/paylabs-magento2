<?php

namespace Paylabs\Payment\Logger\Handler;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;

/**
 * Class CallbackRequestHandler
 * Custom log handler for Paylabs payment module that logs callback request messages to the `paylabs_callback_requests.log` file.
 *
 * This class extends Magento's `BaseHandler` and sets up a custom file (`paylabs_callback_requests.log`) for logging
 * callback request messages at the INFO log level. This helps track incoming callback requests from external services.
 *
 * @package Paylabs\Payment\Logger\Handler
 */
class CallbackRequestHandler extends StreamHandler
{
    public function __construct()
    {
        parent::__construct(BP . '/var/log/paylabs_callback.log', \Monolog\Logger::INFO);
        $this->setFormatter(new LineFormatter(null, null, true, true));
    }
}
