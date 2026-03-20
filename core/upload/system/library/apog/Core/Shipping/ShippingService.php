<?php

namespace Apog\Core\Shipping;

use Apog\Core\Shipping\ApogShippingBase;
use Apog\Core\Shipping\ShippingValidator;
use Apog\Core\Shipping\GeoZoneResolver;
use Apog\Core\Shipping\ShippingCostCalculator;

 /**
 * Class ShippingService
 *
 * Orchestrates shipping module logic:
 * - Validates module availability
 * - Resolves applicable geo zones
 * - Calculates final shipping cost
 * - Returns structured shipping quote
 *
 * Extends ApogShippingBase to inherit registry, module code, config helper, and logging functionality.
 *
 * @package Apog\Core\Shipping
 */
class ShippingService extends ApogShippingBase {

    /**
     * @var ShippingValidator Handles availability checks (status, store, customer group, payment method)
     */
    private $validator;

    /**
     * @var GeoZoneResolver Resolves applicable geo zones for a given address
     */
    private $geoResolver;

    /**
     * @var ShippingCostCalculator Calculates shipping cost based on matched geo zones
     */
    private $calculator;

    /**
     * ShippingService constructor.
     *
     * @param \Registry $registry OpenCart registry object
     * @param string $code Unique shipping module code
     */
    public function __construct($registry, string $code) {
        parent::__construct($registry, $code);

        $this->validator   = new ShippingValidator($registry, $code);
        $this->geoResolver = new GeoZoneResolver($registry);
        $this->calculator  = new ShippingCostCalculator($registry, $code);
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
        $geoZones = $this->geoResolver->getMatchingZones($address);

        if (empty($geoZones)) {
            // No matching geo zones => shipping unavailable
            $this->log("No matching geo zones found for address: " . json_encode($address), 'debug');
            return [];
        }

        // Calculate the final shipping cost
        $cost = $this->calculator->calculate($geoZones);

        if ($cost === null) {
            // No cost calculated (all zones disabled)
            return [];
        }

        // Construct shipping method data
        $quote = [
            'cost'         => $cost,
            'tax_class_id' => $this->cfg("tax_class_id"),
            'sort_order'   => $this->cfg("sort_order")
        ];

        $this->log("Shipping quote calculated: " . json_encode($quote), 'info');

        // Return structured shipping method data
        return $quote;
    }
}
