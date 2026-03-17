<?php
/**
 * Include the shared shipping trait manually.
 * OC3 does not natively autoload custom namespaces from the library folder
 * without extra configuration or an OCMOD.
 */
require_once(DIR_SYSTEM . 'library/apog/traits/back/controller/shipping_common.php');

/**
 * Class ControllerExtensionShippingApogAcs
 * 
 * Manages the Administrative interface for the ACS Courier shipping module.
 * Leverages the ShippingCommon trait for repetitive OpenCart shipping tasks.
 */
class ControllerExtensionShippingApogAcs extends Controller {
    use \Apog\Traits\Back\Controller\ShippingCommon;

    /** @var array Holds validation errors */
    private $error = array();

    /**
     * Primary entry point for the module settings page.
     * Handles both the initial page load (GET) and settings updates (POST).
     * 
     * @return void
     */
    public function index() {
        /**
         * @var string $code Unique identifier for this specific shipping module.
         * Used to prefix database settings (shipping_apog_acs_...)
         */
        $code = 'apog_acs';

        // --- Phase 1: Configuration Schema ---
        
        /** @var array $zone_fields Fields applied specifically to each Geo Zone */
        $zone_fields = [
            'cost'       => 0.00,
            'total_free' => 0.00,
            'status'     => 0
        ];

        /** @var array $global_fields General module settings and restriction rules */
        $global_fields = [
            'excluded_stores'          => [],
            'excluded_customer_groups' => [],
            'excluded_payments'        => [],
            'tax_class_id'             => 0,
            'status'                   => 0,
            'sort_order'               => 0
        ];

        // --- Phase 2: Request Handling ---

        $this->load->language('extension/shipping/' . $code);
        $this->document->setTitle($this->language->get('heading_title'));

        // Add custom admin CSS
        $this->document->addStyle('view/stylesheet/apog_shipping.css');

        $this->load->model('setting/setting');

        // Check if form was submitted and user has modify permissions
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validatePermission($code)) {
            $this->model_setting_setting->editSetting('shipping_' . $code, $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            // Redirect back to the extension list after saving
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true));
        }

        // --- Phase 3: Data Assembly ---

        /**
         * Load breadcrumbs, action URLs, and basic language strings via Trait.
         * Also initializes the $data array.
         */
        $data = $this->getStandardData($code);
        $data['module_code'] = $code;
        $data['error_warning'] = $this->getErrorWarning();

        // Load Geo Zone settings using the dynamic field schema defined in Phase 1
        $data['geo_zones'] = $this->loadGeoZones();
        $data['geo_zone_settings'] = $this->getGeoZoneSettings($code, $data['geo_zones'], $zone_fields);

        // Fetch all global settings into $data (shipping_apog_acs_status, etc.)
        $data = array_merge($data, $this->getGlobalFields($code, $global_fields));

        // --- Phase 4: UI/Dropdown Data Loading ---

        /**
         * Load external data required for the form dropdowns/checkboxes 
         * using helper methods from the trait.
         */
        $data['stores']          = $this->loadStores();
        $data['customer_groups'] = $this->loadCustomerGroups();
        $data['payment_methods'] = $this->loadPaymentMethods();
        $data['tax_classes']     = $this->loadTaxClasses();

        // Standard OpenCart controller components
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        // Render the view template
        $this->response->setOutput($this->load->view('extension/shipping/' . $code, $data));
    }
}
