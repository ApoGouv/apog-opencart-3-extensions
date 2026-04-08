<?php
/**
 * Include our library bootstrap to set up autoloading and any necessary initialization.
 * This ensures that all Apog traits and helper classes are available for use in this controller.
 */
require_once(DIR_SYSTEM . 'library/apog/bootstrap.php');

/**
 * Class ControllerExtensionShippingApogSameDay
 *
 * Administrative controller for the Same Day Delivery shipping module.
 *
 * Responsibilities:
 * - Handles module configuration form rendering and submission
 * - Loads and prepares configuration data for the admin view
 * - Integrates shared logic via reusable Apog traits
 *
 * Traits used:
 * - ControllerHelperTrait: UI helpers (breadcrumbs, actions, errors)
 * - ConfigHelperTrait: Configuration key/value handling
 * - FormOptionsTrait: Dropdown and auxiliary data (stores, geo zones, etc.)
 */
class ControllerExtensionShippingApogSameDay extends Controller {
    use Apog\Traits\Back\Controller\ControllerHelperTrait;
    use Apog\Traits\Back\Controller\ConfigHelperTrait;
    use Apog\Traits\Back\Controller\FormOptionsTrait;

    /**
     * @var string $ext_type The OpenCart extension type (e.g. 'shipping', 'payment', 'total').
     */
    protected $ext_type = 'shipping';

    /**
     * @var string $module_code Unique identifier for this shipping module.
     * Used as part of configuration keys and database settings: {ext_type}_apog_{module_code}_*
     */
    protected $module_code = 'apog_same_day';

    /** @var array Holds validation errors */
    private $error = array();

    /**
     * Primary entry point for the module settings page.
     * Handles both the initial page load (GET) and settings updates (POST).
     * 
     * @return void
     */
    public function index() {
        if (!isset($this->ext_type, $this->module_code)) {
            throw new \Exception('Module context not properly defined.');
        }

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
            'excluded_payment_methods' => [],
            'tax_class_id'             => 0,
            'status'                   => 0,
            'sort_order'               => 0,
            'enable_logging'           => 0,
        ];

        // --- Phase 2: Request Handling ---
        $this->load->language('extension/' . $this->ext_type . '/' . $this->module_code);
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        // Check if form was submitted and user has modify permissions
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validatePermission()) {
            $this->model_setting_setting->editSetting(
                $this->ext_type . '_' . $this->module_code, 
                $this->request->post
            );

            $this->session->data['success'] = $this->language->get('text_success');

            // Redirect back to the extension list after saving
            $this->response->redirect(
                $this->url->link(
                    'marketplace/extension',
                    'user_token=' . $this->session->data['user_token'] . '&type=' . $this->ext_type,
                    true
                )
            );
        }

        // --- Phase 3: Data Assembly ---

        /**
         * Load breadcrumbs, action URLs, and basic language strings via Trait.
         * Also initializes the $data array.
         */
        $data = $this->getStandardData();
        $data['module_code'] = $this->module_code;
        $data['error_warning'] = $this->getErrorWarning();

        // Load Geo Zone settings using the dynamic field schema defined in Phase 1
        $data['geo_zones'] = $this->loadGeoZones();
        $data['geo_zone_settings'] = $this->getGeoZoneSettings($data['geo_zones'], $zone_fields);

        // Fetch all global settings into $data (shipping_apog_same_day_status, etc.)
        foreach ($global_fields as $field => $default) {
            $field_key = $this->getConfigKey($field);
            $data[$field_key] = $this->getConfigValue($field, $default);
        }

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
        $this->response->setOutput(
            $this->load->view('extension/' . $this->ext_type . '/' . $this->module_code, $data)
        );
    }
}
