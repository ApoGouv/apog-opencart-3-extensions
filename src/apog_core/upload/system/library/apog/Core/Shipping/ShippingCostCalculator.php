<?php

namespace Apog\Core\Shipping;

use Apog\Core\ApogExtensionBase;

/**
 * Class ShippingCostCalculator
 *
 * Responsible for calculating the final shipping cost
 * based on applicable geo zones and configuration rules.
 *
 * Features:
 * - Supports multiple geo zones
 * - Applies per-zone enable/disable logic
 * - Applies free shipping thresholds
 * - Selects the lowest applicable cost (OpenCart standard behavior)
 * 
 * @extends ApogExtensionBase to inherit registry, module code, config helper, and logging functionality.
 *
 * @package Apog\Core\Shipping
 */
class ShippingCostCalculator extends ApogExtensionBase {

    /**
     * ShippingCostCalculator constructor.
     *
     * @param \Registry $registry OpenCart registry
     * @param string $moduleCode Unique shipping module code (e.g. 'apog_same_day')
     */
    public function __construct($registry, string $moduleCode) {
        parent::__construct($registry, $moduleCode, 'shipping');
    }

    /**
     * Calculates shipping cost based on matching geo zones.
     *
     * @param array $geoZones Array of geo zones (expects 'geo_zone_id' key)
     * @param float $subTotal Cart subtotal
     *
     * @return float|null Returns lowest cost or null if unavailable
     */
    public function calculate(array $geoZones, float $subTotal): ?float {

        $finalCost = null;

        foreach ($geoZones as $zone) {

            if (!isset($zone['geo_zone_id'])) {
                $this->log("Invalid geo zone structure: " . json_encode($zone), 'error');
                continue;
            }

            $geoZoneId = (int)$zone['geo_zone_id'];

            if (!(bool)$this->cfg("{$geoZoneId}_status", 0)) {
                $this->log("Geo Zone {$geoZoneId} is disabled, skipping.", 'debug');
                continue;
            }

            $cost          = (float)$this->cfg("{$geoZoneId}_cost", 0);
            $freeThreshold = (float)$this->cfg("{$geoZoneId}_total_free", 0);

            if ($freeThreshold > 0 && $subTotal >= $freeThreshold) {
                $this->log(
                    "Free shipping applied (zone {$geoZoneId}): subtotal {$subTotal} >= {$freeThreshold}",
                    'debug'
                );
                $cost = 0.0;
            } else {
                $this->log(
                    "Zone {$geoZoneId} cost={$cost}, subtotal={$subTotal}, free_threshold={$freeThreshold}",
                    'debug'
                );
            }

            // If multiple geo zones apply, we take the one with the lowest cost (standard OC logic)
            if ($finalCost === null || $cost < $finalCost) {
                $finalCost = $cost;
                $this->log("Final cost updated to {$finalCost} based on Geo Zone {$geoZoneId}.", 'debug');
            }
        }

        if ($finalCost === null) {
            $this->log("No matching Geo Zone found or all disabled for module {$this->moduleCode}.", 'debug');
        }

        return $finalCost;
    }

}
