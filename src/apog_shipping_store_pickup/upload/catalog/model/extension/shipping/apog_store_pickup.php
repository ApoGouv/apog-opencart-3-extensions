<?php
/**
 * Class ModelExtensionShippingApogStorePickup
 *
 * Storefront model for the Store Pickup shipping method.
 * Responsible for returning shipping quotes during checkout.
 */

/**
 * Include our library bootstrap to set up autoloading and any necessary initialization.
 */
require_once(DIR_SYSTEM . 'library/apog/bootstrap.php');

use Apog\Core\Shipping\ShippingService;

class ModelExtensionShippingApogStorePickup extends Model {


    /**
     * @var string Unique module code
     */
    protected $module_code = 'apog_store_pickup';

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

        // Utilize the Apog Shipping Helper for core logic
        $shippingService = new ShippingService($this->registry, $this->module_code);
        $result = $shippingService->getQuote($address);

        if (empty($result)) {
            return [];
        }

        // Inject dynamic store name
        $storeName = $this->config->get('config_name');

        $quote_data = [];
        $quote_data[$this->module_code] = [
            'code'         => $this->module_code . '.' . $this->module_code,
            'title'        => sprintf($this->language->get('text_description'), $storeName),
            'cost'         => $result['cost'],
            'tax_class_id' => $result['tax_class_id'],
            'text'         => $this->currency->format(
                $this->tax->calculate(
                    $result['cost'], 
                    $result['tax_class_id'], 
                    $this->config->get('config_tax')
                ), 
                $this->session->data['currency']
            )
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
}