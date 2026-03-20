<?php

namespace Apog\Core\Shipping;

use Apog\Core\Shipping\ApogShippingBase;

/**
 * Class ShippingCostCalculator
 *
 * Calculates the shipping cost for a given set of Geo Zones.
 * Handles multiple zones, applies free shipping thresholds, and can log detailed calculation steps.
 * 
 * Extends ApogShippingBase to inherit registry, module code, config helper, and logging functionality.
 *
 * @package Apog\Core\Shipping
 */
class ShippingCostCalculator extends ApogShippingBase {

    /**
     * ShippingCostCalculator constructor.
     *
     * @param \Registry $registry OpenCart registry
     * @param string $code Unique shipping module code
     */
    public function __construct($registry, $code) {
        parent::__construct($registry, $code);
    }

    /**
     * Calculate the shipping cost for the provided Geo Zones
     *
     * Applies:
     * - Geo zone enable/disable status
     * - Base cost per zone
     * - Free shipping thresholds
     * - Returns the lowest applicable cost across zones
     *
     * @param array $geoZones Array of geo zones with 'geo_zone_id' keys
     *
     * @return float|null The calculated cost, or null if no zones matched
     */
    public function calculate($geoZones) {

        $sub_total = $this->cart->getSubTotal();
        $final_cost = null;

        foreach ($geoZones as $zone) {

            $gz_id = $zone['geo_zone_id'];
            $prefix = "shipping_{$this->code}_{$gz_id}";

            if (!$this->cfg("{$gz_id}_status")) {
                $this->log("Geo Zone {$gz_id} is disabled, skipping.", 'debug');
                continue;
            }

            $cost           = (float)$this->cfg("{$gz_id}_cost");
            $free_threshold = (float)$this->cfg("{$gz_id}_total_free");

            if ($free_threshold > 0 && $sub_total >= $free_threshold) {
                $this->log("Sub-total {$sub_total} >= free threshold {$free_threshold} for Geo Zone {$gz_id}, cost set to 0.", 'debug');
                $cost = 0.0;
            } else {
                $this->log("Geo Zone {$gz_id} cost: {$cost}, sub-total: {$sub_total}, free threshold: {$free_threshold}", 'debug');
            }

            // If multiple geo zones apply, we take the one with the lowest cost (standard OC logic)
            if ($final_cost === null || $cost < $final_cost) {
                $final_cost = $cost;
                $this->log("Final cost updated to {$final_cost} based on Geo Zone {$gz_id}.", 'debug');
            }
        }

        if ($final_cost === null) {
            $this->log("No matching Geo Zone found or all disabled for module {$this->code}.", 'debug');
        }

        return $final_cost;
    }

}
