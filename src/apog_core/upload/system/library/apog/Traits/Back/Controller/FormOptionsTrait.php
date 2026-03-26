<?php

namespace Apog\Traits\Back\Controller;

/**
 * Trait FormOptionsTrait
 *
 */
trait FormOptionsTrait
{
    /**
     * Loads and returns all geo zones.
     *
     * @return array List of geo zones.
     */
    protected function loadGeoZones()
    {
        $this->load->model('localisation/geo_zone');
        return $this->model_localisation_geo_zone->getGeoZones();
    }

    /**
     * Loads and returns all customer groups.
     *
     * @return array List of customer groups.
     */
    protected function loadCustomerGroups()
    {
        $this->load->model('customer/customer_group');
        return $this->model_customer_customer_group->getCustomerGroups();
    }

    /**
     * Loads and returns all tax classes.
     *
     * @return array List of tax classes.
     */
    protected function loadTaxClasses()
    {
        $this->load->model('localisation/tax_class');
        return $this->model_localisation_tax_class->getTaxClasses();
    }

    /**
     * Fetches all installed payment methods and their localized titles.
     *
     * @return array Array of payment methods with 'code' and 'name'.
     */
    protected function loadPaymentMethods()
    {
        $this->load->model('setting/extension');
        $methods = [];

        $extensions = $this->model_setting_extension->getInstalled('payment');
        foreach ($extensions as $extension) {
            $this->load->language('extension/payment/' . $extension);

            $name = $this->language->get('heading_title');

            if (!$name || $name === 'heading_title') {
                $name = ucfirst($extension);
            }

            $methods[] = [
                'code' => $extension,
                'name' => $name
            ];
        }

        return $methods;
    }

    /**
     * Load all stores for the store restriction setting.
     *
     * Returns an array of stores including the default store (store_id = 0).
     * Each store entry contains:
     *  - store_id
     *  - name
     *  - domain (parsed from url/ssl)
     *
     * The url and ssl fields from the original OpenCart store record are removed
     * because they are not needed in the admin template.
     *
     * @return array
     */
    protected function loadStores()
    {
        $this->load->model('setting/store');

        $stores = $this->model_setting_store->getStores();
        $stores = array_map(function ($store) {
            $store['domain'] = $this->getStoreDomain($store);

            unset($store['url']);
            unset($store['ssl']);

            return $store;
        }, $stores);

        // Add Default Store (store_id = 0)
        $url = HTTPS_CATALOG ? HTTPS_CATALOG : HTTP_CATALOG;

        array_unshift($stores, array(
            'store_id'       => 0,
            'name'           => $this->config->get('config_name') . ' (' . $this->language->get('text_default') . ')',
            'domain'         => parse_url($url, PHP_URL_HOST),
        ));

        return $stores;
    }

    /**
     * Maps specific settings per Geo Zone.
     *
     * @param array  $geo_zones List of geo zones from the localization model.
     * @param array  $fields    Key-value pair of fields and their default values.
     *
     * @return array Matrix of settings indexed by [geo_zone_id][field].
     */
    protected function getGeoZoneSettings($geo_zones, $fields = [])
    {
        $settings = [];
        foreach ($geo_zones as $geo_zone) {
            $geoZoneId = $geo_zone['geo_zone_id'];
            $settings[$geoZoneId] = [];

            foreach ($fields as $field => $default) {
                $settings[$geoZoneId][$field] = $this->getConfigValue($geoZoneId . '_' . $field, $default);
            }
        }

        return $settings;
    }

    /**
     * Extract the domain from a store configuration.
     *
     * Prefers the SSL URL if available, otherwise falls back to the normal URL.
     *
     * @param array $store Store data from model_setting_store->getStores()
     *
     * @return string Domain name or empty string if not available
     */
    private function getStoreDomain($store)
    {

        if (!empty($store['ssl'])) {
            $domain = parse_url($store['ssl'], PHP_URL_HOST);

            if ($domain) {
                return $domain;
            }
        }

        if (!empty($store['url'])) {
            $domain = parse_url($store['url'], PHP_URL_HOST);

            if ($domain) {
                return $domain;
            }
        }

        return '';
    }
}
