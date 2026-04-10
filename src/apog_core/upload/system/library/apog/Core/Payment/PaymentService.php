<?php
/**
 * @package   Apog OpenCart 3 Extensions
 * @author    Apostolos Gouvalas
 * @copyright 2026 Apostolos Gouvalas
 * @license   Apache-2.0
 */

namespace Apog\Core\Payment;

use Apog\Core\ApogExtensionBase;
use Apog\Core\Payment\PaymentValidator;

/**
 * Class PaymentService
 *
 * High-level orchestrator for payment module logic.
 *
 * Responsibilities:
 * - Delegates availability checks to PaymentValidator
 * - Acts as the public entry point for payment method evaluation
 * 
 * This class is deigned to be used by payment method models (e.g. ModelExtensionPaymentApogCod) 
 * to determine if the payment method should be offered during checkout.
 *
 * @extends ApogExtensionBase to inherit registry, module code, config helper, and logging functionality.
 *
 * @package Apog\Core\Payment
 */
class PaymentService extends ApogExtensionBase {

    /**
     * @var PaymentValidator Handles all payment availability validation rules
     */
    private $validator;

    /**
     * PaymentService constructor.
     *
     * @param \Registry $registry OpenCart registry instance
     * @param string $moduleCode  Unique payment module code (e.g. 'apog_cod')
     */
    public function __construct($registry, string $moduleCode) {
        parent::__construct($registry, $moduleCode, 'payment');

        $this->validator = new PaymentValidator($registry, $moduleCode);
    }

    /**
     * Determines whether the payment method is available
     * for the current checkout context.
     *
     * Delegates all validation logic to PaymentValidator.
     *
     * @param array $address Customer payment address
     *                       Expected keys:
     *                       - country_id (int)
     *                       - zone_id (int)
     * @param float $total   Current order total
     *
     * @return bool True if the payment method is available, false otherwise
     */
    public function isAvailable(array $address, float $total): bool {
        return $this->validator->isAvailable($address, $total);
    }
}
