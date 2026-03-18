<?php
namespace Apog;

/**
 * ShippingHelper
 * Provides shared calculation logic for Apog shipping modules.
 * Handles exclusions for customer groups and payment methods.
 */
class ShippingHelper {
    private $registry;

    public function __construct($registry) {
        $this->registry = $registry;
    }

    /**
     * Get instance of registry objects easily
     */
    public function __get($key) {
        return $this->registry->get($key);
    }

    /**
     * Main validation logic for shipping availability
     */
    public function getMethodData($code, $address) {
        // Check Global Module Status
        if (!$this->config->get('shipping_' . $code . '_status')) {
            return false;
        }

        // Excluded Stores
        $excluded_stores = (array)$this->config->get('shipping_' . $code . '_excluded_stores');

        $current_store_id = (int)$this->config->get('config_store_id');

        if (!empty($excluded_stores) && in_array($current_store_id, $excluded_stores)) {
            return false;
        }

        // Excluded Customer Groups
        $excluded_groups = (array)$this->config->get('shipping_' . $code . '_excluded_customer_groups');

        $current_group_id = $this->customer->isLogged() ? $this->customer->getGroupId() : $this->config->get('config_customer_group_id');
        
        if (!empty($excluded_groups) && in_array($current_group_id, $excluded_groups)) {
            return false;
        }


        // Excluded Payment Methods
        $excluded_payments = (array)$this->config->get('shipping_' . $code . '_excluded_payments');
        if (!empty($excluded_payments) && isset($this->session->data['payment_method']['code'])) {
            if (in_array($this->session->data['payment_method']['code'], $excluded_payments)) {
                return false;
            }
        }

        // Geo Zone Matching & Cost Calculation
        $query = $this->db->query("SELECT geo_zone_id FROM " . DB_PREFIX . "zone_to_geo_zone WHERE country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

        if (!$query->num_rows) {
            return false;
        }

        $sub_total = $this->cart->getSubTotal();
        $final_cost = null;
        $matched = false;

        foreach ($query->rows as $result) {
            $gz_id = $result['geo_zone_id'];
            $prefix = 'shipping_' . $code . '_' . $gz_id;
            
            // If this specific geo zone is enabled in settings
            if ($this->config->get($prefix . '_status')) {
                $matched = true;
                $cost = (float)$this->config->get($prefix . '_cost');
                $free_threshold = (float)$this->config->get($prefix . '_total_free');

                // Apply Free Shipping if threshold reached
                if ($free_threshold > 0 && $sub_total >= $free_threshold) {
                    $cost = 0.00;
                }

                // If multiple geo zones apply, we take the one with the lowest cost (standard OC logic)
                if ($final_cost === null || $cost < $final_cost) {
                    $final_cost = $cost;
                }
            }
        }

        // If no geo zone matched or all applicable were disabled
        if (!$matched || $final_cost === null) {
            return false;
        }

        return [
            'cost'         => $final_cost,
            'tax_class_id' => $this->config->get('shipping_' . $code . '_tax_class_id'),
            'sort_order'   => $this->config->get('shipping_' . $code . '_sort_order')
        ];
    }
}
