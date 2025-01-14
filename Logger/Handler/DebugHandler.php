<?php

namespace Paylabs\Payment\Logger\Handler;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;

/**
 * Class DebugHandler
 * Custom log handler for Paylabs payment module that writes debug-level log messages to the `paylabs_debug.log` file.
 *
 * This class extends Magento's `BaseHandler` and sets up a custom file (`paylabs_debug.log`) for logging debug messages.
 * It uses Monolog's `Logger::DEBUG` log level to capture and store debug-level logs.
 *
 * @package Paylabs\Payment\Logger\Handler
 */
class DebugHandler extends StreamHandler
{
    public function __construct()
    {
        parent::__construct(BP . '/var/log/paylabs_debug.log', \Monolog\Logger::DEBUG);
        $this->setFormatter(new LineFormatter(null, null, true, true));
    }
}
