<?php

namespace Apog\Core\Payment;

use Apog\Core\ApogExtensionBase;

/**
 * Class PaymentValidator
 *
 * Handles all business rules that determine whether
 * a payment method is available during checkout.
 *
 * Validation rules:
 * - Module enabled status
 * - Minimum order total
 * - Store restriction
 * - Customer group restriction
 * - Shipping method restriction
 * - Geo zone restriction
 *
 * This class acts as the single source of truth
 * for payment availability logic.
 * 
 * @extends ApogExtensionBase to inherit registry, module code, config helper, and logging functionality.
 *
 * @package Apog\Core\Payment
 */
class PaymentValidator extends ApogExtensionBase {

    /**
     * PaymentValidator constructor.
     *
     * @param \Registry $registry OpenCart registry
     * @param string $moduleCode Unique payment module code (e.g. 'apog_cod')
     */
    public function __construct($registry, string $moduleCode) {
        parent::__construct($registry, $moduleCode, 'payment');
    }

    /**
     * Determines whether the payment method is available
     * for the given checkout context.
     *
     * @param array $address Customer address
     *                       Expected keys:
     *                       - country_id (int)
     *                       - zone_id (int)
     * @param float $total   Order total
     *
     * @return bool True if available, false otherwise
     */
    public function isAvailable(array $address, float $total): bool {
        if (!$this->isEnabled()) return false;

        if (!$this->checkMinTotal($total)) return false;

        if (!$this->isStoreAllowed()) return false;

        if (!$this->isCustomerGroupAllowed()) return false;

        if (!$this->isShippingAllowed()) return false;

        if (!$this->isGeoZoneAllowed($address)) return false;
        
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
            $this->log("Payment method '{$this->moduleCode}' is disabled", 'debug');
        }

        return $isEnabled;
    }

    /**
     * Validates minimum order total requirement.
     *
     * @param float $total Order total
     * @return bool True if minimum total is met, false otherwise
     */
    protected function checkMinTotal(float $total): bool {
        $min = (float)$this->cfg('min_total', 0);

        return !($min > 0 && $total < $min);
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
     * Checks if the current shipping method is allowed for this payment method
     *
     * @return bool
     */
    private function isShippingAllowed(): bool {
        $excluded_shipping_methods = array_filter((array)$this->cfg("excluded_shipping_methods", []));

        // If no restrictions > allow
        if (empty($excluded_shipping_methods)) {
            return true;
        }

        $currentShippingMethod = isset($this->session->data['shipping_method']['code']) 
            ? $this->session->data['shipping_method']['code'] 
            : null;

        // If checkout not initialized yet > allow
        if (!$currentShippingMethod) {
            return true;
        }

        // Get base code for comparison (e.g., 'flat' from 'flat.flat')
        $baseShippingCode = $this->getBaseMethodCode($currentShippingMethod);

        // Check BOTH formats
        $isExcluded = in_array($currentShippingMethod, $excluded_shipping_methods, true)
            || in_array($baseShippingCode, $excluded_shipping_methods, true);

        if ($isExcluded) {
            $this->log(
                "Shipping '{$currentShippingMethod}' (base: '{$baseShippingCode}') IS excluded: [" . implode(',', $excluded_shipping_methods) . "] for payment '{$this->moduleCode}'",
                'debug'
            );
        }

        return !$isExcluded;
    }

    private function isGeoZoneAllowed(array $address): bool {
        $geoZoneId = (int)$this->cfg('geo_zone_id', 0);

        if (!$geoZoneId) return true;

        $countryId = (int)($address['country_id'] ?? 0);
        $zoneId    = (int)($address['zone_id'] ?? 0);

        $query = $this->db->query("
            SELECT *
            FROM " . DB_PREFIX . "zone_to_geo_zone
            WHERE geo_zone_id = '{$geoZoneId}'
              AND country_id = '{$countryId}'
              AND (zone_id = '{$zoneId}' OR zone_id = '0')
        ");

        $allowed = (bool)$query->num_rows;

        if (!$allowed) {
            $this->log("Geo zone {$geoZoneId} not matched for country {$countryId}, zone {$zoneId}", 'debug');
        }

        return $allowed;
    }

}
