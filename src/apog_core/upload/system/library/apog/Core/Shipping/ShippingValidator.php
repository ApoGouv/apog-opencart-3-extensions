<?php

namespace Apog\Core\Shipping;

use Apog\Core\Shipping\ApogShippingBase;

/**
 * Class ShippingValidator
 *
 * Validates whether a specific shipping module is available based on:
 * - Global module status
 * - Store restrictions
 * - Customer group restrictions
 * - Payment method restrictions
 * 
 * Extends ApogShippingBase to inherit registry, module code, config helper, and logging functionality.
 *
 * @package Apog\Core\Shipping
 */
class ShippingValidator extends ApogShippingBase {

    /**
     * ShippingValidator constructor.
     *
     * @param \Registry $registry OpenCart registry
     * @param string $code Unique shipping module code
     */
    public function __construct($registry, string $code) {
        parent::__construct($registry, $code);
    }

    /**
     * Returns true if the shipping module is available for the current context
     *
     * @return bool
     */
    public function isAvailable(): bool {
        return $this->isEnabled()
            && $this->isStoreAllowed()
            && $this->isCustomerGroupAllowed()
            && $this->isPaymentAllowed();
    }

    /**
     * Checks if the module is globally enabled
     *
     * @return bool
     */
    private function isEnabled(): bool {
        $isEnabled = (bool)$this->cfg("status");

        if (!$isEnabled) {
            $this->log("Shipping method '{$this->code}' is disabled", 'debug');
        }

        return $isEnabled;
    }

    /**
     * Checks if the current store is allowed
     *
     * @return bool
     */
    private function isStoreAllowed(): bool {
        $excluded_stores = array_map('intval', (array)$this->cfg("excluded_stores"));
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
        $excluded_groups = array_map('intval', (array)$this->cfg("excluded_customer_groups"));

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
    private function isPaymentAllowed(): bool {
        $excluded_payments = (array)$this->cfg("excluded_payments");

        // If no restrictions, it's allowed
        if (empty($excluded_payments)) return true;

        $currentPaymentMethod = isset($this->session->data['payment_method']['code']) 
            ? $this->session->data['payment_method']['code'] 
            : null;

        // If payment method not set yet (checkout not started), allow
        if (!$currentPaymentMethod) return true;

        $allowed = !in_array($currentPaymentMethod, $excluded_payments, true);

        if (!$allowed) {
            $this->log("Payment method '{$currentPaymentMethod}' is excluded: [" . implode(',', $excluded_payments) . "]", 'debug');
        }

        return $allowed;
    }

}
