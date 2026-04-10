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
 * Class TotalCostCalculator
 *
 * Responsible for calculating the final total fee
 * based on applicable geo zones and configuration rules.
 *
 * Features:
 * - Supports multiple geo zones
 * - Applies per-zone enable/disable logic
 * - Applies free threshold logic
 * - Selects the lowest applicable fee (OC-compatible behavior)
 *
 * @package Apog\Core\Total
 */
class TotalCostCalculator extends ApogExtensionBase {

    /**
     * TotalCostCalculator constructor.
     *
     * @param \Registry $registry OpenCart registry
     * @param string $moduleCode Unique module code (e.g. 'apog_cod_fee')
     */
    public function __construct($registry, string $moduleCode) {
        parent::__construct($registry, $moduleCode, 'total');
    }

    /**
     * Calculates total fee based on matching geo zones.
     *
     * @param array $geoZones Array of geo zones (expects 'geo_zone_id')
     * @param float $subTotal Cart subtotal
     *
     * @return float|null Returns fee or null if not applicable
     */
    public function calculate(array $geoZones, float $subTotal): ?float {

        $finalFee = null;

        foreach ($geoZones as $zone) {

            if (!isset($zone['geo_zone_id'])) {
                $this->log("Invalid geo zone structure: " . json_encode($zone), 'error');
                continue;
            }

            $geoZoneId = (int)$zone['geo_zone_id'];

            // Check if this zone is enabled
            if (!(bool)$this->cfg("{$geoZoneId}_status", 0)) {
                $this->log("Geo Zone {$geoZoneId} is disabled, skipping.", 'debug');
                continue;
            }

            $fee           = (float)$this->cfg("{$geoZoneId}_fee", 0);
            $freeThreshold = (float)$this->cfg("{$geoZoneId}_total_free", 0);

            // Apply free threshold
            if ($freeThreshold > 0 && $subTotal >= $freeThreshold) {
                $this->log(
                    "Free total applied (zone {$geoZoneId}): subtotal {$subTotal} >= {$freeThreshold}",
                    'debug'
                );
                $fee = 0.0;
            } else {
                $this->log(
                    "Zone {$geoZoneId}: fee={$fee}, subtotal={$subTotal}, free_threshold={$freeThreshold}",
                    'debug'
                );
            }

            // Choose lowest fee if multiple zones match
            if ($finalFee === null || $fee < $finalFee) {
                $finalFee = $fee;
                $this->log("Final fee updated to {$finalFee} (Geo Zone {$geoZoneId})", 'debug');
            }
        }

        if ($finalFee === null) {
            $this->log("No applicable geo zones found for {$this->moduleCode}", 'debug');
        }

        return $finalFee;
    }
}
