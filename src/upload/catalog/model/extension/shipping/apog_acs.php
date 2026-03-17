<?php
/**
 * ModelExtensionShippingApogAcs
 * Storefront model for ACS Courier shipping.
 */

// Manually include the helper since OC3 doesn't autoload custom library namespaces
if (!class_exists('\Apog\ShippingHelper')) {
    require_once(DIR_SYSTEM . 'library/apog/shipping_helper.php');
}

class ModelExtensionShippingApogAcs extends Model {
    /**
     * getQuote
     * Called by OpenCart checkout to retrieve the shipping rate.
     * 
     * @param array $address The shipping address of the customer
     * 
     * @return array The quote data or empty array if not applicable
     */
    public function getQuote($address) {
        $code = 'apog_acs';
        $this->load->language('extension/shipping/' . $code);

        // Utilize the Apog Shipping Helper for core logic
        $shippingHelper = new \Apog\ShippingHelper($this->registry);
        $result = $shippingHelper->getMethodData($code, $address);

        if (!$result) {
            return [];
        }

        $quote_data = [];
        $quote_data[$code] = [
            'code'         => $code . '.' . $code,
            'title'        => $this->language->get('text_description'),
            'cost'         => $result['cost'],
            'tax_class_id' => $result['tax_class_id'],
            'text'         => $this->currency->format(
                $this->tax->calculate($result['cost'], $result['tax_class_id'], $this->config->get('config_tax')), 
                $this->session->data['currency']
            )
        ];

        $method_data = [
            'code'       => $code,
            'title'      => $this->language->get('text_title'),
            'quote'      => $quote_data,
            'sort_order' => $result['sort_order'],
            'error'      => false
        ];

        return $method_data;
    }
}