<?php
/**
 * @package   Apog OpenCart 3 Extensions
 * @author    Apostolos Gouvalas
 * @copyright 2026 Apostolos Gouvalas
 * @license   Apache-2.0
 */

namespace Apog\Core\Shipping;

use Apog\Core\ApogExtensionBase;

/**
 * Class ShippingValidator
 *
 * Handles all business rules for shipping availability.
 *
 * Validation rules:
 * - Module enabled status
 * - Store restrictions
 * - Customer group restrictions
 * - Payment method restrictions
 * 
 * @extends ApogExtensionBase to inherit registry, module code, config helper, and logging functionality.
 *
 * @package Apog\Core\Shipping
 */
class ShippingValidator extends ApogExtensionBase {

    /**
     * ShippingValidator constructor.
     *
     * @param \Registry $registry OpenCart registry
     * @param string $moduleCode Unique shipping module code (e.g. 'apog_same_day')
     */
    public function __construct($registry, string $moduleCode) {
        parent::__construct($registry, $moduleCode, 'shipping');
    }

    /**
     * Determines whether shipping method is available.
     *
     * @return bool
     */
    public function isAvailable(): bool {
        if (!$this->isEnabled()) return false;

        if (!$this->isStoreAllowed()) return false;

        if (!$this->isCustomerGroupAllowed()) return false;

        if (!$this->isPaymentMethodAllowed()) return false;

        return true;
    }

    /**
     * Checks if the module is globally enabled
     *
     * @return bool
     */
    private function isEnabled(): bool {
        $isEnabled = (bool)$this->cfg("status", 0);

        if (!$isEnabled) {
            $this->log("Shipping method '{$this->moduleCode}' is disabled", 'debug');
        }

        return $isEnabled;
    }

    /**
     * Checks if the current store is allowed
     *
     * @return bool
     */
    private function isStoreAllowed(): bool {
        $excluded_stores = array_map('intval', (array)$this->cfg("excluded_stores", []));
        $store_id = (int)$this->config->get('config_store_id');

        // Allowed if no exclusions OR current store is not excluded
        $allowed = empty($excluded_stores) || !in_array($store_id, $excluded_stores, true);

        if (!$allowed) {
            $this->log("Store {$store_id} is excluded: [" . implode(',', $excluded_stores) . "]", 'debug');
        }

        return $allowed;
    }

    /**
     * Checks if the current customer group is allowed
     *
     * @return bool
     */
    private function isCustomerGroupAllowed(): bool {
        $excluded_groups = array_map('intval', (array)$this->cfg("excluded_customer_groups", []));

        $group_id = $this->customer->isLogged()
            ? $this->customer->getGroupId()
            : $this->config->get('config_customer_group_id');

        $allowed = empty($excluded_groups) || !in_array($group_id, $excluded_groups, true);

        if (!$allowed) {
            $this->log("Customer group {$group_id} is excluded: [" . implode(',', $excluded_groups) . "]", 'debug');
        }

        return $allowed;
    }

    /**
     * Checks if the current payment method is allowed
     *
     * @return bool
     */
    private function isPaymentMethodAllowed(): bool {
        $excluded_payment_methods = (array)$this->cfg("excluded_payment_methods", []);

        // If no restrictions, it's allowed
        if (empty($excluded_payment_methods)) return true;

        $currentPaymentMethod = isset($this->session->data['payment_method']['code']) 
            ? $this->session->data['payment_method']['code'] 
            : null;

        // If payment method not set yet (checkout not started), allow
        if (!$currentPaymentMethod) return true;

        $basePaymentCode = $this->getBaseMethodCode($currentPaymentMethod);

        $isExcluded = in_array($currentPaymentMethod, $excluded_payment_methods, true)
            || in_array($basePaymentCode, $excluded_payment_methods, true);

        if ($isExcluded) {
            $this->log(
                "Payment '{$currentPaymentMethod}' (base: '{$basePaymentCode}') is excluded: [" . implode(',', $excluded_payment_methods) . "]",
                'debug'
            );
        }

        return !$isExcluded;
    }

}
