<?php
/**
 * @package   Apog OpenCart 3 Extensions
 * @author    Apostolos Gouvalas
 * @copyright 2026 Apostolos Gouvalas
 * @license   Apache-2.0
 */

namespace Apog\Core\Total;

use Apog\Core\ApogExtensionBase;
use Apog\Core\Total\TotalValidator;
use Apog\Core\Total\TotalCostCalculator;

/**
 * Class TotalService
 *
 * High-level orchestrator for total module logic.
 *
 * Responsibilities:
 * - Builds runtime context (customer group, shipping, payment)
 * - Delegates validation to TotalValidator
 * - Resolves matching geo zones
 * - Calculates fee using TotalCostCalculator
 * - Returns structured result for model consumption
 *
 * @package Apog\Core\Total
 */
class TotalService extends ApogExtensionBase {

    /**
     * @var TotalValidator Handles availability checks (status, store, customer group, payment methods/ bound payment method, shipping methods)
     */
    private $validator;
    
    /**
     * @var TotalCostCalculator Calculates Total fee cost based on matched geo zones
     */
    private $calculator;

    /**
     * TotalService constructor.
     *
     * @param \Registry $registry OpenCart registry object
     * @param string $moduleCode Unique total module code (e.g. 'apog_cod_fee')
     */
    public function __construct($registry, string $moduleCode) {
        parent::__construct($registry, $moduleCode, 'total');

        $this->validator   = new TotalValidator($registry, $moduleCode);
        $this->calculator  = new TotalCostCalculator($registry, $moduleCode);
    }

    /**
     * Returns total calculation result or empty array if not applicable.
     *
     * @return array {
     *   fee: float,
     *   tax_class_id: int,
     *   sort_order: int
     * }
     */
    public function getTotal(): ?array {

        // --- Step 1: Build context ---
        $context = $this->buildContext();

        // 🔥 DEBUG LOG
        $this->log('Total context: ' . json_encode($context), 'debug');

        // --- Step 2: Validate ---
        if (!$this->validator->isValid($context)) {
            $this->log("Total '{$this->moduleCode}' not applicable due to validation rules.", 'debug');
            return [];
        }

        // --- Binding logic ---
        $bindingPaymentCode = $this->cfg('binding_payment_code', null);

        if (!empty($bindingPaymentCode)) {

            $currentPayment = $context['payment_method'] ?? null;

            // Payment not selected yet, so don't apply
            if (!$currentPayment) {
                $this->log("Binding exists but no payment selected yet → skip", 'debug');
                return [];
            }

            $baseCurrentPayment = $this->getBaseMethodCode($currentPayment);

            if ($currentPayment !== $bindingPaymentCode && $baseCurrentPayment !== $bindingPaymentCode) {
                $this->log(
                    "Payment '{$currentPayment}' does not match binding '{$bindingPaymentCode}' > skip",
                    'debug'
                );
                return [];
            }
        }

        // --- Step 3: Resolve geo zones ---
        $geoZones = $this->getMatchingGeoZones();

        if (empty($geoZones)) {
            $this->log("No matching geo zones for total '{$this->moduleCode}'", 'debug');
            return [];
        }

        // --- Step 4: Calculate fee ---
        $subTotal = (float)$this->cart->getSubTotal();

        $fee = $this->calculator->calculate($geoZones, $subTotal);

        if ($fee === null) {
            $this->log("No fee applied (fee={$fee})", 'debug');
            return [];
        }

        // --- Step 5: Return structured result ---
        $result = [
            'value'        => $fee,
            'tax_class_id' => (int)$this->cfg('tax_class_id', 0),
            'sort_order'   => (int)$this->cfg('sort_order', 0),
        ];

        $this->log("Total calculated: " . json_encode($result), 'info');

        return $result;
    }

    /**
     * Builds runtime context for validation.
     *
     * @return array
     */
    private function buildContext(): array {

        return [
            'customer_group_id' => $this->getCustomerGroupId(),
            'shipping_method'   => $this->getShippingMethod(),
            'payment_method'    => $this->getPaymentMethod(),
        ];
    }

    /**
     * Returns current customer group ID.
     *
     * @return int
     */
    private function getCustomerGroupId(): int {
        return $this->customer->isLogged()
            ? (int)$this->customer->getGroupId()
            : (int)$this->config->get('config_customer_group_id');
    }

    /**
     * Returns current shipping method code (if selected).
     *
     * @return string|null
     */
    private function getShippingMethod(): ?string {
        return $this->session->data['shipping_method']['code'] ?? null;
    }

    /**
     * Returns current payment method code (if selected).
     *
     * @return string|null
     */
    private function getPaymentMethod(): ?string {
        return $this->session->data['payment_method']['code'] ?? null;
    }

    /**
     * Retrieves geo zones that match current shipping address.
     *
     * @return array
     */
    private function getMatchingGeoZones(): array {

        if (empty($this->session->data['shipping_address'])) {
            $this->log("No shipping address found in session.", 'debug');
            return [];
        }

        $address = $this->session->data['shipping_address'];

        $query = $this->db->query("
            SELECT geo_zone_id
            FROM " . DB_PREFIX . "zone_to_geo_zone
            WHERE country_id = '" . (int)$address['country_id'] . "'
            AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')
        ");

        return $query->num_rows ? $query->rows : [];
    }
}

