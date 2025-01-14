<?php

namespace Paylabs\Payment\Logger\Handler;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class ErrorHandler extends StreamHandler
{
    public function __construct()
    {
        parent::__construct(BP . '/var/log/paylabs_error.log', Logger::ERROR);
        $this->setLevel(Logger::ERROR); // Ensures only error-level logs are recorded
    }
}
