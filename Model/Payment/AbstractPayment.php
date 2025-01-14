<?php

namespace Paylabs\Payment\Model\Payment;

use Paylabs\Payment\Logger\PaylabsLogger;
use Magento\Framework\Event\ManagerInterface;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Validator\ValidatorPoolInterface;
use Magento\Payment\Model\Method\Adapter;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Class AbstractPayment
 * Provides a base payment method implementation for Paylabs payment methods.
 *
 * This class extends the `Adapter` class from Magento's payment gateway module
 * and provides basic functionality for Paylabs's payment integration, including
 * logging, checking availability, and handling configuration settings.
 *
 * @package Paylabs\Payment\Model\Payment
 */
class AbstractPayment extends Adapter
{
    /**
     * @var string The payment method code.
     */
    public string $code;

    /**
     * @var bool Flag indicating whether the gateway is enabled.
     */
    protected bool $isGateway = true;

    /**
     * @var PaylabsLogger Logger for logging payment-related events.
     */
    private PaylabsLogger $logger;

    /**
     * AbstractPayment constructor.
     *
     * Initializes the payment method with the necessary dependencies, including
     * the event manager, value handler pool, payment data object factory, and others.
     *
     * @param ManagerInterface $eventManager Event manager for handling Magento events.
     * @param ValueHandlerPoolInterface $valueHandlerPool Pool of value handlers for payment gateway configuration.
     * @param PaymentDataObjectFactory $paymentDataObjectFactory Factory for creating payment data objects.
     * @param PaylabsLogger $logger Logger instance for logging events and errors.
     * @param string $code Payment method code.
     * @param string $formBlockType Block type for the payment method form.
     * @param string $infoBlockType Block type for the payment method info.
     * @param CommandPoolInterface|null $commandPool Command pool for gateway commands (optional).
     * @param ValidatorPoolInterface|null $validatorPool Validator pool for gateway command validation (optional).
     * @param CommandManagerInterface|null $commandExecutor Command executor for running commands (optional).
     */
    public function __construct(
        ManagerInterface $eventManager,
        ValueHandlerPoolInterface $valueHandlerPool,
        PaymentDataObjectFactory $paymentDataObjectFactory,
        PaylabsLogger $logger,
        string $code,
        string $formBlockType,
        string $infoBlockType,
        CommandPoolInterface $commandPool = null,
        ValidatorPoolInterface $validatorPool = null,
        CommandManagerInterface $commandExecutor = null,
    ) {
        parent::__construct(
            $eventManager,
            $valueHandlerPool,
            $paymentDataObjectFactory,
            $code,
            $formBlockType,
            $infoBlockType,
            $commandPool,
            $validatorPool,
            $commandExecutor,
        );
        $this->logger = $logger;
    }

    /**
     * Checks if the payment method is available.
     *
     * This method checks if the payment method is active in the configuration and
     * if it is available for the given cart (quote). It overrides the `isAvailable`
     * method to add custom availability checks for Paylabs's payment gateway.
     *
     * @param CartInterface|null $quote The current cart/quote object.
     * @return bool True if the payment method is available, false otherwise.
     */
    public function isAvailable(CartInterface $quote = null): bool
    {
        return $this->getConfigData('active') && parent::isAvailable($quote);
    }
}
