<?php

namespace Apog\Core\Shipping;

use Apog\Core\ApogExtensionBase;
use Apog\Core\Shipping\ShippingValidator;
use Apog\Core\Shipping\ShippingCostCalculator;

/**
 * Class ShippingService
 *
 * High-level orchestrator for shipping module logic.
 *
 * Responsibilities:
 * - Delegates validation to ShippingValidator
 * - Resolves geo zones
 * - Calculates shipping cost
 * - Returns structured shipping quote
 *
 * @extends ApogExtensionBase to inherit registry, module code, config helper, and logging functionality.
 *
 * @package Apog\Core\Shipping
 */
class ShippingService extends ApogExtensionBase {

    /**
     * @var ShippingValidator Handles availability checks (status, store, customer group, payment method)
     */
    private $validator;
    
    /**
     * @var ShippingCostCalculator Calculates shipping cost based on matched geo zones
     */
    private $calculator;

    /**
     * ShippingService constructor.
     *
     * @param \Registry $registry OpenCart registry object
     * @param string $moduleCode Unique shipping module code (e.g. 'apog_same_day')
     */
    public function __construct($registry, string $moduleCode) {
        parent::__construct($registry, $moduleCode, 'shipping');

        $this->validator   = new ShippingValidator($registry, $moduleCode);
        $this->calculator  = new ShippingCostCalculator($registry, $moduleCode);
    }

    /**
     * Returns a shipping quote for the given address.
     *
     * @param array $address Customer shipping address (expects 'country_id' and 'zone_id')
     * 
     * @return array Array with 'cost', 'tax_class_id', and 'sort_order' or empty array if unavailable
     */
    public function getQuote($address): array {

        // Check if the shipping method is globally available
        if (!$this->validator->isAvailable()) {
            return [];
        }

        // Retrieve geo zones that match the address
        $geoZones = $this->getMatchingGeoZones($address);

        if (empty($geoZones)) {
            // No matching geo zones => shipping unavailable
            $this->log("No matching geo zones found for address: " . json_encode($address), 'debug');
            return [];
        }

        $sub_total = $this->cart->getSubTotal();

        // Calculate the final shipping cost
        $cost = $this->calculator->calculate($geoZones, $sub_total);

        if ($cost === null) {
            // No cost calculated (all zones disabled)
            return [];
        }

        // Construct shipping method data
        $quote = [
            'cost'         => $cost,
            'tax_class_id' => $this->cfg("tax_class_id", 0),
            'sort_order'   => $this->cfg("sort_order", 0)
        ];

        $this->log("Shipping quote calculated: " . json_encode($quote), 'info');

        // Return structured shipping method data
        return $quote;
    }

    private function getMatchingGeoZones(array $address): array {
        $query = $this->db->query("
            SELECT geo_zone_id
            FROM " . DB_PREFIX . "zone_to_geo_zone
            WHERE country_id = '" . (int)$address['country_id'] . "'
            AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')
        ");

        return $query->num_rows ? $query->rows : [];
    }
}
