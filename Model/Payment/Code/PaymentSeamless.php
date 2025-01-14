<?php

namespace Paylabs\Payment\Model\Payment\Code;

use Paylabs\Payment\Model\Payment\AbstractPayment;

/**
 * Class PaymentSeamless
 * Represents the seamless checkout payment method for Paylabs.
 *
 * This class is used for handling the payment integration for seamless checkout via Paylabs.
 * It extends the `AbstractPayment` class and defines the payment method code.
 *
 * @package Paylabs\Payment\Model\Payment\Code
 */
class PaymentSeamless extends AbstractPayment
{
    /**
     * Payment method code for the seamless checkout payment.
     */
    const CODE = 'paylabs_payment_seamless';

    /**
     * @var string The payment method code.
     */
    public string $code = self::CODE;
}
