<?php

namespace Apog\Traits\Back\Controller;

/**
 * Trait ShippingCommon
 * 
 * Provides shared logic for OpenCart 3.x Admin Shipping controllers.
 * Handles breadcrumbs, configuration retrieval, and data loading for UI elements.
 */
trait ShippingCommon {
    /**
     * Prepares standard view data including language, breadcrumbs, and form actions.
     *
     * @param string $code The module identifier (e.g., 'apog_acs').
     * 
     * @return array Standard data array for the view.
     */
    protected function getStandardData($code) {
        $this->load->language('extension/shipping/' . $code);

        $data['heading_title'] = $this->language->get('heading_title');
        $data['user_token'] = $this->session->data['user_token'];

        // Breadcrumbs setup
        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $data['user_token'], true)
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $data['user_token'] . '&type=shipping', true)
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/shipping/' . $code, 'user_token=' . $data['user_token'], true)
        ];

        // Form actions
        $data['action'] = $this->url->link('extension/shipping/' . $code, 'user_token=' . $data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $data['user_token'] . '&type=shipping', true);

        return $data;
    }

    /**
     * Loads and returns all geo zones.
     *
     * @return array List of geo zones.
     */
    protected function loadGeoZones() {
        $this->load->model('localisation/geo_zone');
        return $this->model_localisation_geo_zone->getGeoZones();
    }

    /**
     * Loads and returns all customer groups.
     *
     * @return array List of customer groups.
     */
    protected function loadCustomerGroups() {
        $this->load->model('customer/customer_group');
        return $this->model_customer_customer_group->getCustomerGroups();
    }

    /**
     * Loads and returns all tax classes.
     *
     * @return array List of tax classes.
     */
    protected function loadTaxClasses() {
        $this->load->model('localisation/tax_class');
        return $this->model_localisation_tax_class->getTaxClasses();
    }

    /**
     * Fetches all installed payment methods and their localized titles.
     *
     * @return array Array of payment methods with 'code' and 'name'.
     */
    protected function loadPaymentMethods() {
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
    protected function loadStores() {
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
			'name'           => $this->config->get('config_name'),
			'domain'         => parse_url($url, PHP_URL_HOST),
		));

        return $stores;
    }

    /**
     * Maps specific settings per Geo Zone.
     *
     * @param string $code      The module identifier.
     * @param array  $geo_zones List of geo zones from the localization model.
     * @param array  $fields    Key-value pair of fields and their default values.
     * 
     * @return array Matrix of settings indexed by [geo_zone_id][field].
     */
    protected function getGeoZoneSettings($code, $geo_zones, $fields = []) {
        $settings = [];
        foreach ($geo_zones as $geo_zone) {
            $gz_id = $geo_zone['geo_zone_id'];
            $settings[$gz_id] = [];
            
            foreach ($fields as $field => $default) {
                $settings[$gz_id][$field] = $this->getConfigValue($code, $gz_id . '_' . $field, $default);
            }
        }

        return $settings;
    }

    /**
     * Generates an array of global configuration fields with full OpenCart keys.
     *
     * @param string $code   The module identifier.
     * @param array  $fields Key-value pair of global fields and defaults.
     * 
     * @return array Array formatted as ['shipping_code_field' => value].
     */
    protected function getGlobalFields($code, $fields = []) {
        $data = [];
        foreach ($fields as $field => $default) {
            $data['shipping_' . $code . '_' . $field] = $this->getConfigValue($code, $field, $default);
        }

        return $data;
    }

    /**
     * Retrieves a configuration value prioritizing POST data, then DB config.
     *
     * @param string $code    The module identifier.
     * @param string $key     The specific field key (without prefix).
     * @param mixed  $default Fallback value.
     * @return mixed
     */
    protected function getConfigValue($code, $key, $default = '') {
        $full_key = 'shipping_' . $code . '_' . $key;

        if (isset($this->request->post[$full_key])) {
            return $this->request->post[$full_key];
        }

        $value = $this->config->get($full_key);

        return ($value !== null) ? $value : $default;
    }

    /**
     * Helper to return the warning error if it exists.
     *
     * @return string
     */
    protected function getErrorWarning() {
        return isset($this->error['warning']) ? $this->error['warning'] : '';
    }

    /**
     * Validates if the current user has modify permissions for the extension.
     *
     * @param string $code The module identifier.
     * 
     * @return bool True if valid.
     */
    protected function validatePermission($code) {
        if (!$this->user->hasPermission('modify', 'extension/shipping/' . $code)) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return !$this->error;
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
    private function getStoreDomain($store) {

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
