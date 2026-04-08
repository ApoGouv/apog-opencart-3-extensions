<?php
require_once(DIR_SYSTEM . 'library/apog/bootstrap.php');

class ControllerExtensionTotalApog{{ClassName}} extends Controller {
    use Apog\Traits\Back\Controller\ControllerHelperTrait;
    use Apog\Traits\Back\Controller\ConfigHelperTrait;
    use Apog\Traits\Back\Controller\FormOptionsTrait;

    protected $ext_type = 'total';
    protected $module_code = 'apog_{{module_code}}';

    protected const BINDING_PAYMENT_CODE = {{binding_payment_code}};

    private $error = array();

    public function index() {
        if (!isset($this->ext_type, $this->module_code)) {
            throw new \Exception('Module context not properly defined.');
        }

        // --- Phase 1: Configuration Schema ---
        
        /** @var array $zone_fields Fields applied specifically to each Geo Zone */
        $zone_fields = [
            'fee'          => 0.00,
            'total_free'   => 0.00,
            'status'       => 0
        ];

        /** @var array $global_fields General module settings and restriction rules */
        $global_fields = [
            // Core
            'tax_class_id'   => 0,
            'status'         => 0,
            'sort_order'     => 0,
            'enable_logging' => 0,

            // Restrictions
            'excluded_stores'           => [],
            'excluded_customer_groups'  => [],
            'excluded_shipping_methods' => [],
            'excluded_payment_methods'  => [],
        ];

        // --- Phase 2: Request Handling ---
        $this->load->language('extension/' . $this->ext_type . '/' . $this->module_code);
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        // Check if form was submitted and user has modify permissions
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validatePermission()) {

            $bindingpaymentkey = $this->getConfigKey('binding_payment_code');

            // Prevent posted override of binding_payment_code
            if (isset($this->request->post[$bindingpaymentkey])) {
                unset($this->request->post[$bindingpaymentkey]);
            }

            // Inject binding_payment_code (immutable, from generator)
            $this->request->post[$bindingpaymentkey] = static::BINDING_PAYMENT_CODE;

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

        // Fetch all global settings into $data (total_apog_{{module_code}}_status, etc.)
        foreach ($global_fields as $field => $default) {
            $field_key = $this->getConfigKey($field);
            $data[$field_key] = $this->getConfigValue($field, $default);
        }

        // Binding logic
        $bindingPayment = $this->resolveBindingPaymentCode();

        $data['binding_payment_code'] = $bindingPayment;
        $data['has_binding_payment']  = $this->isBindingActive($bindingPayment);

        /**
         * Load external data required for the form dropdowns/checkboxes 
         * using helper methods from the trait.
         */
        $data['stores']           = $this->loadStores();
        $data['customer_groups']  = $this->loadCustomerGroups();
        $data['shipping_methods'] = $this->loadShippingMethods();
        $data['tax_classes']      = $this->loadTaxClasses();

        // Only load payment methods if NOT bound
        if (empty($bindingPayment)) {
            $data['payment_methods'] = $this->loadPaymentMethods();
        } else {
            $data['payment_methods'] = [];
        }

        // Standard OpenCart controller components
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        // Render the view template
        $this->response->setOutput(
            $this->load->view('extension/' . $this->ext_type . '/' . $this->module_code, $data)
        );
    }

    private function resolveBindingPaymentCode(): ?string {
        $value = $this->config->get($this->getConfigKey('binding_payment_code'));

        if ($value === null) {
            $value = static::BINDING_PAYMENT_CODE;
        }

        return $value ?: null;
    }

    private function isBindingActive(?string $value): bool {
        return !empty($value);
    }

}
