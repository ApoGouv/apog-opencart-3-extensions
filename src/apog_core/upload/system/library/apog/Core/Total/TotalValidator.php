<?php
/**
 * @package   Apog OpenCart 3 Extensions
 * @author    Apostolos Gouvalas
 * @copyright 2026 Apostolos Gouvalas
 * @license   Apache-2.0
 */

namespace Apog\Core\Total;

use Apog\Core\ApogExtensionBase;

/**
 * Class TotalValidator
 *
 * Handles all business rules for total availability.
 *
 * Validation rules:
 * - Module enabled status
 * - Store restrictions
 * - Customer group restrictions
 * - Shipping method restrictions
 * - Payment method restrictions
 */
class TotalValidator extends ApogExtensionBase {

    public function __construct($registry, string $moduleCode) {
        parent::__construct($registry, $moduleCode, 'total');
    }

    /**
     * Determines whether total module is applicable.
     *
     * @param array $context Expected:
     *        - customer_group_id
     *        - shipping_method (optional code)
     *        - payment_method (optional code)
     *
     * @return bool
     */
    public function isValid(array $context): bool {

        if (!$this->isEnabled()) {
            return false;
        }

        if (!$this->isStoreAllowed()) {
            return false;
        }

        if (!$this->isCustomerGroupAllowed($context)) {
            return false;
        }

        if (!$this->isShippingAllowed($context)) {
            return false;
        }

        if (!$this->isPaymentAllowed($context)) {
            return false;
        }

        return true;
    }

    private function isEnabled(): bool {
        $enabled = (bool)$this->cfg('status', 0);

        if (!$enabled) {
            $this->log("Total '{$this->moduleCode}' is disabled", 'debug');
        }

        return $enabled;
    }

    private function isStoreAllowed(): bool {
        $excluded = array_map('intval', (array)$this->cfg('excluded_stores', []));
        $storeId = (int)$this->config->get('config_store_id');

        $allowed = empty($excluded) || !in_array($storeId, $excluded, true);

        if (!$allowed) {
            $this->log("Store {$storeId} excluded", 'debug');
        }

        return $allowed;
    }

    private function isCustomerGroupAllowed(array $context): bool {
        $excluded = array_map('intval', (array)$this->cfg('excluded_customer_groups', []));

        $groupId = $context['customer_group_id']
            ?? (int)$this->config->get('config_customer_group_id');

        $allowed = empty($excluded) || !in_array($groupId, $excluded, true);

        if (!$allowed) {
            $this->log("Customer group {$groupId} excluded", 'debug');
        }

        return $allowed;
    }

    private function isShippingAllowed(array $context): bool {
        $excluded = (array)$this->cfg('excluded_shipping_methods', []);

        if (empty($excluded)) {
            return true;
        }

        $shipping = $context['shipping_method'] ?? null;

        if (!$shipping) {
            return true; // checkout not yet selected
        }

        $base = $this->getBaseMethodCode($shipping);

        $isExcluded = in_array($shipping, $excluded, true)
            || in_array($base, $excluded, true);

        if ($isExcluded) {
            $this->log("Shipping '{$shipping}' excluded", 'debug');
        }

        return !$isExcluded;
    }

    private function isPaymentAllowed(array $context): bool {
    
        $currentPayment = $context['payment_method'] ?? null;

        if (!$currentPayment) {
            return true;
        }

        $baseCurrentPayment = $this->getBaseMethodCode($currentPayment);
        
        // --- Exclusion logic (fallback) ---
        $excluded = (array)$this->cfg('excluded_payment_methods', []);

        if (empty($excluded)) {
            return true;
        }

        $isExcluded = in_array($currentPayment, $excluded, true)
            || in_array($baseCurrentPayment, $excluded, true);

        if ($isExcluded) {
            $this->log("Payment '{$currentPayment}' excluded", 'debug');
        }

        return !$isExcluded;
    }
}
