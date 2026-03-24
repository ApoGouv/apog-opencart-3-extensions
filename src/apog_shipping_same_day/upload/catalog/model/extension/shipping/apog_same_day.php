<?php
/**
 * Class ModelExtensionShippingApogSameDay
 *
 * Storefront model for the Same Day Delivery shipping method.
 * Responsible for returning shipping quotes during checkout.
 */

/**
 * Include our library bootstrap to set up autoloading and any necessary initialization.
 */
require_once(DIR_SYSTEM . 'library/apog/bootstrap.php');

use Apog\Core\Shipping\ShippingService;

class ModelExtensionShippingApogSameDay extends Model {

    /**
     * @var string Unique module code
     */
    protected $module_code = 'apog_same_day';

    /**
     * @var int|null Cached country_id for Greece (GR)
     *
     * Uses in-memory caching per request to avoid repeated lookups.
     * Null means "not yet resolved".
     */
    private $greeceCountryId = null;

    /**
     * getQuote
     * Called by OpenCart checkout to retrieve the shipping rate.
     * 
     * @param array $address The shipping address of the customer
     * 
     * @return array The shipping method quote data or empty array if not applicable
     */
    public function getQuote($address) {
        $this->load->language('extension/shipping/' . $this->module_code);

        // 🔒 Custom Same Day rules
        if (!$this->isSameDayAvailable($address)) {
            return [];
        }

        // Utilize the Apog Shipping Helper for core logic
        $shippingService = new ShippingService($this->registry, $this->module_code);
        $result = $shippingService->getQuote($address);

        if (empty($result)) {
            return [];
        }

        $quote_data = [];
        $quote_data[$this->module_code] = [
            'code'         => $this->module_code . '.' . $this->module_code,
            'title'        => $this->language->get('text_description'),
            'cost'         => $result['cost'],
            'tax_class_id' => $result['tax_class_id'],
            'text'         => $this->currency->format(
                $this->tax->calculate($result['cost'], $result['tax_class_id'], $this->config->get('config_tax')), 
                $this->session->data['currency']
            ),
            'desc'         => $this->language->get('text_same_day_info')
        ];

        $method_data = [
            'code'       => $this->module_code,
            'title'      => $this->language->get('text_title'),
            'quote'      => $quote_data,
            'sort_order' => $result['sort_order'],
            'error'      => false
        ];

        return $method_data;
    }

    /**
     * Determines whether Same Day Delivery is available for the given address.
     *
     * Current active rules:
     * - Only available for Greece (GR)
     *
     * Optional rules (disabled for now, can be enabled later):
     * - Weekdays only (Mon–Fri)
     * - Before a cutoff time (e.g., 13:00)
     *
     * @param array $address Customer shipping address (expects 'country_id')
     *
     * @return bool True if Same Day Delivery is allowed, false otherwise
     */
    private function isSameDayAvailable(array $address): bool {
        $countryId = (int)$address['country_id'];
        $greeceId = $this->getGreeceCountryId();

        // 1. Only Greece (GR)
        if (!$greeceId || $countryId !== $greeceId) {
            return false;
        }

        // 2. Weekdays only (Mon–Fri)
        // $dayOfWeek = (int)date('N'); // 1=Mon ... 7=Sun
        // if ($dayOfWeek > 5) {
        //     return false;
        // }

        // 3. Cutoff time (e.g., 13:00)
        // $currentTime = new DateTime();
        // $cutoffTime  = new DateTime('13:00');

        // if ($currentTime >= $cutoffTime) {
        //     return false;
        // }

        return true;
    }

    /**
     * Retrieves the country_id for Greece (ISO code: GR).
     *
     * Uses OpenCart's country model (which is internally cached)
     * and adds an additional in-memory cache for the current request.
     *
     * @return int|null Country ID if found, null otherwise
     */
    private function getGreeceCountryId(): ?int {
        if ($this->greeceCountryId !== null) {
            return $this->greeceCountryId;
        }

        $this->load->model('localisation/country');

        $countriesData = $this->model_localisation_country->getCountries();

        foreach ($countriesData as $country) {
            if ($country['iso_code_2'] === 'GR') {
                return $this->greeceCountryId = (int)$country['country_id'];
            }
        }

        return null;
    }

}