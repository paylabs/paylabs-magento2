<?php

namespace Paylabs\Payment\Logger;

use Paylabs\Payment\Logger\Handler\ApiRequestHandler;
use Paylabs\Payment\Logger\Handler\CallbackRequestHandler;
use Paylabs\Payment\Logger\Handler\DebugHandler;
use Paylabs\Payment\Logger\Handler\ErrorHandler;
use Paylabs\Payment\Model\Config\Payment\ModuleConfig;
use Monolog\Logger;

/**
 * Class PaylabsLogger
 *
 * Custom logger for the Paylabs Payment module, extending Monolog's Logger class.
 * Provides methods for logging API requests, callbacks, debug messages, and errors.
 *
 * @package Paylabs\Payment\Logger
 */
class PaylabsLogger extends Logger
{
    private ModuleConfig $moduleConfig;
    private ApiRequestHandler $apiRequestLogHandler;
    private CallbackRequestHandler $callbackLogHandler;
    private DebugHandler $debugLogHandler;
    private ErrorHandler $errorHandler;

    /**
     * PaylabsLogger constructor.
     *
     * @param string $name Name of the logger instance.
     * @param ModuleConfig $moduleConfig Configuration for enabling/disabling logging.
     * @param ApiRequestHandler $apiRequestLogHandler Handler for API request logs.
     * @param CallbackRequestHandler $callbackLogHandler Handler for callback request logs.
     * @param DebugHandler $debugLogHandler Handler for debug logs.
     * @param ErrorHandler $errorHandler Handler for error logs.
     */
    public function __construct(
        string $name,
        ModuleConfig $moduleConfig,
        ApiRequestHandler $apiRequestLogHandler,
        CallbackRequestHandler $callbackLogHandler,
        DebugHandler $debugLogHandler,
        ErrorHandler $errorHandler
    ) {
        parent::__construct($name);

        $this->moduleConfig = $moduleConfig;
        $this->apiRequestLogHandler = $apiRequestLogHandler;
        $this->callbackLogHandler = $callbackLogHandler;
        $this->debugLogHandler = $debugLogHandler;
        $this->errorHandler = $errorHandler;
    }

    /**
     * Log an API request message.
     *
     * If API request logging is enabled in the configuration, this method logs the provided message.
     *
     * @param string $message The message to log.
     */
    public function logApiRequest(string $message): void
    {
        if ($this->moduleConfig->isRequestLogEnable()) {
            $this->pushHandler($this->apiRequestLogHandler);
            $this->info($message);
            $this->popHandler();
        }
    }

    /**
     * Log a debug message.
     *
     * If debug logging is enabled in the configuration, this method logs the provided message.
     *
     * @param string $message The message to log.
     */
    public function logDebug(string $message): void
    {
        if ($this->moduleConfig->isDebugLogEnable()) {
            $this->pushHandler($this->debugLogHandler);
            $this->debug($message);
            $this->popHandler();
        }
    }

    /**
     * Log a callback message.
     *
     * If callback logging is enabled in the configuration, this method logs the provided message.
     *
     * @param string $message The message to log.
     */
    public function logCallback(string $message): void
    {
        if ($this->moduleConfig->isCallbackLogEnable()) {
            $this->pushHandler($this->callbackLogHandler);
            $this->info($message);
            $this->popHandler();
        }
    }

    /**
     * Log an error or exception message.
     *
     * If error logging is enabled in the configuration, this method logs the provided message.
     * If an exception is provided, it includes detailed information about the exception in the log.
     *
     * @param string $message The base error message to log.
     * @param \Throwable|null $exception Optional exception to include in the log.
     */
    public function logErrorException(string $message, \Throwable $exception = null): void
    {
        $errorMessage = $exception !== null ? sprintf(
            "Exception: %s\nCode: %d\nFile: %s\nLine: %d\nStack Trace:\n%s",
            $message . ': ' . $exception->getMessage(),
            $exception->getCode(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        ) : $message;

        if ($this->moduleConfig->isErrorLogEnable()) {
            $this->pushHandler($this->errorHandler);
            $this->error($errorMessage);
            $this->popHandler();
        }
    }
}
