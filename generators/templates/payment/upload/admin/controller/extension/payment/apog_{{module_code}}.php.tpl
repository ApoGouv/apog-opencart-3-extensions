<?php
/**
 * Include our library bootstrap to set up autoloading and any necessary initialization.
 * This ensures that all Apog traits and helper classes are available for use in this controller.
 */
require_once(DIR_SYSTEM . 'library/apog/bootstrap.php');

/**
 * Class ControllerExtensionPaymentApog{{ClassName}}
 *
 * Administrative controller for the {{module_name}} payment module.
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
class ControllerExtensionPaymentApog{{ClassName}} extends Controller {
    use Apog\Traits\Back\Controller\ControllerHelperTrait;
    use Apog\Traits\Back\Controller\ConfigHelperTrait;
    use Apog\Traits\Back\Controller\FormOptionsTrait;

    /**
     * @var string $ext_type The OpenCart extension type (e.g. 'shipping', 'payment', 'total').
     */
    protected $ext_type = 'payment';

    /**
     * @var string $module_code Unique identifier for this payment module.
     * Used as part of configuration keys and database settings: {ext_type}_apog_{module_code}_*
     */
    protected $module_code = 'apog_{{module_code}}';

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

        // --- Phase 1: Config Schema ---

        $global_fields = [
            'excluded_stores'           => [],
            'excluded_customer_groups'  => [],
            'excluded_shipping_methods' => [],
            'status'                    => 0,
            'sort_order'                => 0,
            'order_status_id'           => 0,
            'geo_zone_id'               => 0,
            'min_total'                 => 0.00,
            'enable_logging'            => 0,
        ];

        // --- Phase 2: Request Handling ---

        $this->load->language('extension/' . $this->ext_type . '/' . $this->module_code);
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validatePermission()) {
            $this->model_setting_setting->editSetting(
                $this->ext_type . '_' . $this->module_code,
                $this->request->post
            );

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect(
                $this->url->link(
                    'marketplace/extension',
                    'user_token=' . $this->session->data['user_token'] . '&type=' . $this->ext_type,
                    true
                )
            );
        }

        // --- Phase 3: Data Assembly ---

        $data = $this->getStandardData();
        $data['module_code'] = $this->module_code;
        $data['error_warning'] = $this->getErrorWarning();

        // Global config values
        foreach ($global_fields as $field => $default) {
            $field_key = $this->getConfigKey($field);
            $data[$field_key] = $this->getConfigValue($field, $default);
        }

        // --- Instructions (multilingual, optional) ---
        $this->load->model('localisation/language');
        $data['languages'] = $this->model_localisation_language->getLanguages();

        foreach ($data['languages'] as $language) {
            $lang_id = $language['language_id'];
            $instructions_field = 'instructions_' . $lang_id;
            $instructions_field_key = $this->getConfigKey($instructions_field);
            $data[$instructions_field_key] = $this->getConfigValue($instructions_field, '');
        }

        // --- Phase 4: UI Data ---

        $data['geo_zones']        = $this->loadGeoZones();
        $data['stores']           = $this->loadStores();
        $data['customer_groups']  = $this->loadCustomerGroups();
        $data['shipping_methods'] = $this->loadShippingMethods();
        $data['order_statuses']   = $this->loadOrderStatuses();

        // Standard layout
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput(
            $this->load->view('extension/' . $this->ext_type . '/' . $this->module_code, $data)
        );
    }
}
